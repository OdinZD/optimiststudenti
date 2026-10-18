<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Jobs\CreateEracuniPartnerJob;
use App\Jobs\FetchEracuniPartnersJob;
use App\Models\ClubBillingSettings;
use App\Models\EracuniPartnerLink;
use App\Models\StudentFeePlan;
use App\Services\Eracuni\EracuniPartner;
use App\Services\Eracuni\PartnerMatcher;
use App\Services\Eracuni\PayerCandidate;
use App\Support\Auditer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Admin screen "Povezivanje roditelja" (Članarine). Shows payers (parents) who pay by
 * e-račun and proposes a matching eRačuni partner (by e-mail, then name) from the cached
 * PartnerList snapshot. The admin confirms, rejects, or queues "create new partner".
 * Fetching/creating go through rate-limited queue jobs; confirming/rejecting are local writes.
 */
#[Layout('components.layouts.app')]
#[Title('Povezivanje roditelja')]
final class EracuniPartnerLinks extends Component
{
    /** @var list<string> e-mailovi čiji je prijedlog odbijen (samo za ovu sesiju) */
    public array $dismissed = [];

    public function mount(): void
    {
        abort_unless(Gate::allows('manage-fee-plans'), 403);
    }

    /** Queue a refresh of the partner list from eRačuni (rate-limited job). */
    public function refreshPartners(): void
    {
        abort_unless(Gate::allows('manage-fee-plans'), 403);

        FetchEracuniPartnersJob::dispatch();
        Auditer::log('eracuni.partners_fetch_queued');

        $this->dispatch('toast', message: 'Dohvaćanje partnera pokrenuto — osvježi za koji trenutak.');
    }

    /** Confirm a proposed/chosen match: create the local link (no API call). */
    public function link(string $email, string $partnerId): void
    {
        abort_unless(Gate::allows('manage-fee-plans'), 403);

        $candidate = $this->candidates()->firstWhere('email', $email);
        $partner = $this->partners()->firstWhere('id', $partnerId);

        if ($candidate === null || $partner === null) {
            return;
        }

        $link = EracuniPartnerLink::updateOrCreate(
            ['payer_email' => $email],
            [
                'payer_name' => $candidate->name,
                'eracuni_partner_id' => $partner->id,
                'eracuni_partner_name' => $partner->name,
                'linked_by' => Auth::user()?->name,
                'confirmed_at' => now(),
            ],
        );

        Auditer::log('eracuni.partner_linked', $link, null, ['payer_email' => $email, 'eracuni_partner_id' => $partner->id]);
        $this->dispatch('toast', message: 'Roditelj povezan s partnerom.');
    }

    public function unlink(int $linkId): void
    {
        abort_unless(Gate::allows('manage-fee-plans'), 403);

        $link = EracuniPartnerLink::find($linkId);

        if ($link !== null) {
            Auditer::log('eracuni.partner_unlinked', $link, $link->only(['payer_email', 'eracuni_partner_id']), null);
            $link->delete();
            $this->dispatch('toast', message: 'Veza uklonjena.');
        }
    }

    /** Reject the proposed auto-match for this payer (session-only; reveals manual options). */
    public function reject(string $email): void
    {
        abort_unless(Gate::allows('manage-fee-plans'), 403);

        if (! in_array($email, $this->dismissed, true)) {
            $this->dismissed[] = $email;
        }
    }

    /** Queue creation of a brand-new eRačuni partner for this payer (never automatic). */
    public function createPartner(string $email): void
    {
        abort_unless(Gate::allows('manage-fee-plans'), 403);

        $candidate = $this->candidates()->firstWhere('email', $email);

        if ($candidate === null) {
            return;
        }

        CreateEracuniPartnerJob::dispatch($email, $candidate->name, [
            'companyName' => $candidate->name,
            'eMail' => $candidate->email,
        ]);

        Auditer::log('eracuni.partner_create_queued', null, null, ['payer_email' => $email]);
        $this->dispatch('toast', message: 'Stvaranje partnera pokrenuto.');
    }

    /** Distinct payers (parents) with an active e-račun assignment. */
    private function candidates(): Collection
    {
        $today = CarbonImmutable::now()->toDateString();

        return StudentFeePlan::query()
            ->with('student:id,parent_name,parent_email')
            ->where('nacin_placanja', 'racun_email')
            ->whereNotNull('payer_email')
            ->whereDate('valid_from', '<=', $today)
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', $today))
            ->get()
            ->map(fn (StudentFeePlan $p): PayerCandidate => new PayerCandidate(
                email: (string) $p->payer_email,
                name: (string) ($p->payer_name ?: $p->student?->parent_name ?: $p->payer_email),
            ))
            ->unique('email')
            ->values();
    }

    /** The cached PartnerList snapshot, as EracuniPartner value objects. */
    private function partners(): Collection
    {
        $cached = Cache::get(FetchEracuniPartnersJob::CACHE_KEY);
        $rows = is_array($cached['partners'] ?? null) ? $cached['partners'] : [];

        return collect($rows)
            ->map(fn (array $row): EracuniPartner => EracuniPartner::fromArray($row))
            ->values();
    }

    public function render()
    {
        $partners = $this->partners();
        $links = EracuniPartnerLink::query()->get()->keyBy('payer_email');

        $rows = $this->candidates()->map(function (PayerCandidate $candidate) use ($partners, $links): array {
            $link = $links->get($candidate->email);
            $rejected = in_array($candidate->email, $this->dismissed, true);

            return [
                'candidate' => $candidate,
                'link' => $link,
                'proposal' => ($link === null && ! $rejected) ? PartnerMatcher::propose($candidate, $partners) : null,
            ];
        });

        $cached = Cache::get(FetchEracuniPartnersJob::CACHE_KEY);

        return view('livewire.eracuni-partner-links', [
            'rows' => $rows,
            'linkedCount' => $rows->filter(fn (array $r): bool => $r['link'] !== null)->count(),
            'unlinkedCount' => $rows->filter(fn (array $r): bool => $r['link'] === null)->count(),
            'partnerCount' => $partners->count(),
            'fetchedAt' => isset($cached['fetched_at']) ? CarbonImmutable::parse($cached['fetched_at']) : null,
            'hasCredentials' => ClubBillingSettings::current()?->hasEracuniCredentials() ?? false,
        ]);
    }
}
