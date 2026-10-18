<?php

declare(strict_types=1);

namespace App\Services\Eracuni;

use App\Support\Diacritics;

/**
 * Proposes the best eRačuni partner for a payer: exact e-mail first, then an exact
 * diacritic-insensitive name match. Returns null when nothing matches — the admin then
 * confirms, rejects, or creates a new partner. (OIB tier is intentionally omitted for
 * now: the parent's OIB is not stored.)
 */
final class PartnerMatcher
{
    /**
     * @param  iterable<EracuniPartner>  $partners
     */
    public static function propose(PayerCandidate $payer, iterable $partners): ?MatchProposal
    {
        $partners = is_array($partners) ? $partners : iterator_to_array($partners);

        $email = mb_strtolower(trim($payer->email));
        if ($email !== '') {
            foreach ($partners as $partner) {
                if ($partner->email !== null && mb_strtolower(trim($partner->email)) === $email) {
                    return new MatchProposal($partner, 'email');
                }
            }
        }

        $name = Diacritics::normalize($payer->name);
        if (trim($name) !== '') {
            foreach ($partners as $partner) {
                if (Diacritics::normalize($partner->name) === $name) {
                    return new MatchProposal($partner, 'name');
                }
            }
        }

        return null;
    }
}
