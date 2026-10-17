<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Charge;
use App\Models\ClubBillingSettings;
use App\Models\FeePlan;
use App\Models\Payment;
use App\Models\Student;
use App\Support\Auditer;
use App\Support\BillingReference;
use App\Support\Money;
use App\Support\PaymentRecorder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use RuntimeException;

/**
 * A student's billing area (Članarina → Zaduženja): charges with computed status,
 * balance, recording payments (FIFO or manual selection), write-offs and reversals,
 * plus a manual one-time charge. Viewing needs assign-fee-plan OR manage-payments;
 * payments/write-off/storno need manage-payments; manual charge needs assign-fee-plan.
 */
final class StudentCharges extends Component
{
    public int $studentId;

    // Ručno zaduženje (assign-fee-plan)
    public bool $mcSubmitted = false;

    public string $mcFeePlanId = '';

    public string $mcPeriod = '';

    public string $mcAmount = '';

    public string $mcDueDate = '';

    // Evidentiranje uplate (manage-payments)
    public bool $showPayment = false;

    public bool $paySubmitted = false;

    public string $payAmount = '';

    public string $payMethod = 'bank';

    public string $payPaidAt = '';

    public string $payBankReference = '';

    public string $payNote = '';

    /** @var list<int> */
    public array $paySelected = [];

    // Otpis (manage-payments)
    public ?int $writeOffChargeId = null;

    public string $writeOffReason = '';

    public function mount(int $studentId): void
    {
        abort_unless(Gate::any(['assign-fee-plan', 'manage-payments']), 403);
        $this->studentId = $studentId;
        $this->mcPeriod = now()->format('Y-m');
        $this->payPaidAt = now()->toDateString();
    }

    // --- Ručno zaduženje -----------------------------------------------------

    public function updatedMcFeePlanId(string $value): void
    {
        $plan = FeePlan::find((int) $value);

        if ($plan !== null) {
            $this->mcAmount = number_format($plan->amount_cents / 100, 2, ',', '');
        }
    }

    public function addCharge(): void
    {
        abort_unless(Gate::allows('assign-fee-plan'), 403);
        $this->mcSubmitted = true;
        $this->validate([
            'mcFeePlanId' => ['required', 'exists:fee_plans,id'],
            'mcPeriod' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'mcAmount' => ['required', 'string', 'regex:/^[0-9.,\s€]+$/'],
            'mcDueDate' => ['nullable', 'date'],
        ], attributes: ['mcFeePlanId' => 'tarifa', 'mcPeriod' => 'mjesec', 'mcAmount' => 'iznos']);

        $settings = ClubBillingSettings::current();

        try {
            $charge = Charge::create([
                'student_id' => $this->studentId,
                'fee_plan_id' => (int) $this->mcFeePlanId,
                'period' => $this->mcPeriod,
                'amount_cents' => Money::parse($this->mcAmount),
                'due_date' => $this->mcDueDate ?: null,
                'reference' => BillingReference::for($this->studentId, $this->mcPeriod, $settings?->model),
            ]);
        } catch (UniqueConstraintViolationException) {
            $this->addError('mcFeePlanId', 'Za tu tarifu i mjesec zaduženje već postoji.');

            return;
        }

        Auditer::log('charge.created_manually', $charge, null, $charge->only($charge->getFillable()));
        $this->reset(['mcSubmitted', 'mcFeePlanId', 'mcAmount', 'mcDueDate']);
        $this->mcPeriod = now()->format('Y-m');
        $this->dispatch('toast', message: 'Zaduženje dodano');
    }

    // --- Uplata --------------------------------------------------------------

    public function openPayment(): void
    {
        abort_unless(Gate::allows('manage-payments'), 403);
        $open = $this->openCharges();
        $this->paySelected = $open->pluck('id')->all();
        $this->payAmount = number_format($open->sum(fn (Charge $c): int => $c->outstandingCents()) / 100, 2, ',', '');
        $this->payPaidAt = now()->toDateString();
        $this->paySubmitted = false;
        $this->showPayment = true;
    }

    public function cancelPayment(): void
    {
        $this->reset(['showPayment', 'paySubmitted', 'payAmount', 'payBankReference', 'payNote', 'paySelected']);
        $this->payMethod = 'bank';
    }

    public function recordPayment(): void
    {
        abort_unless(Gate::allows('manage-payments'), 403);
        $this->paySubmitted = true;
        $this->validate([
            'payAmount' => ['required', 'string', 'regex:/^[0-9.,\s€]+$/'],
            'payMethod' => ['required', Rule::in(['bank', 'cash', 'other'])],
            'payPaidAt' => ['required', 'date'],
            'payBankReference' => ['nullable', 'string', 'max:255'],
            'payNote' => ['nullable', 'string', 'max:255'],
            'paySelected' => ['array'],
            'paySelected.*' => ['integer'],
        ], attributes: ['payAmount' => 'iznos', 'payPaidAt' => 'datum']);

        PaymentRecorder::record(
            Student::findOrFail($this->studentId),
            Money::parse($this->payAmount),
            $this->payMethod,
            'manual',
            $this->payPaidAt,
            $this->payBankReference ?: null,
            $this->payNote ?: null,
            Auth::id(),
            $this->paySelected !== [] ? array_map('intval', $this->paySelected) : null,
        );

        $this->cancelPayment();
        $this->dispatch('toast', message: 'Uplata evidentirana');
    }

    public function reversePayment(int $paymentId): void
    {
        abort_unless(Gate::allows('manage-payments'), 403);
        $payment = Payment::where('student_id', $this->studentId)->findOrFail($paymentId);

        try {
            PaymentRecorder::reverse($payment, Auth::id());
        } catch (RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage());

            return;
        }

        $this->dispatch('toast', message: 'Uplata stornirana');
    }

    // --- Otpis ---------------------------------------------------------------

    public function startWriteOff(int $chargeId): void
    {
        abort_unless(Gate::allows('manage-payments'), 403);
        $this->writeOffChargeId = $chargeId;
        $this->writeOffReason = '';
    }

    public function cancelWriteOff(): void
    {
        $this->reset(['writeOffChargeId', 'writeOffReason']);
    }

    public function confirmWriteOff(): void
    {
        abort_unless(Gate::allows('manage-payments'), 403);

        if (trim($this->writeOffReason) === '') {
            $this->addError('writeOffReason', 'Razlog je obavezan');

            return;
        }

        $charge = Charge::where('student_id', $this->studentId)->findOrFail($this->writeOffChargeId);
        PaymentRecorder::writeOff($charge, $this->writeOffReason, Auth::id());

        $this->cancelWriteOff();
        $this->dispatch('toast', message: 'Zaduženje otpisano');
    }

    /** @return Collection<int, Charge> */
    private function openCharges()
    {
        return Charge::with('allocations')
            ->where('student_id', $this->studentId)
            ->whereNull('written_off_at')
            ->orderBy('period')
            ->get()
            ->filter(fn (Charge $c): bool => $c->outstandingCents() > 0)
            ->values();
    }

    public function render()
    {
        $charges = Charge::with('allocations')
            ->where('student_id', $this->studentId)
            ->orderByDesc('period')
            ->get();

        $payments = Payment::where('student_id', $this->studentId)
            ->orderByDesc('paid_at')->orderByDesc('id')
            ->get();

        $reversedIds = $payments->whereNotNull('reversed_payment_id')->pluck('reversed_payment_id')->all();

        $owed = $charges->reject(fn (Charge $c): bool => $c->isWrittenOff())->sum(fn (Charge $c): int => $c->outstandingCents());
        $allocated = $charges->sum(fn (Charge $c): int => $c->allocatedCents());
        $unallocated = max(0, (int) $payments->sum('amount_cents') - $allocated);

        return view('livewire.student-charges', [
            'charges' => $charges,
            'payments' => $payments,
            'reversedIds' => $reversedIds,
            'plans' => FeePlan::where('active', true)->orderBy('name')->get(),
            'owedCents' => $owed,
            'unallocatedCents' => $unallocated,
        ]);
    }
}
