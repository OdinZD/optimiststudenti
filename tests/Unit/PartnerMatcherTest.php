<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Eracuni\EracuniPartner;
use App\Services\Eracuni\PartnerMatcher;
use App\Services\Eracuni\PayerCandidate;
use PHPUnit\Framework\TestCase;

final class PartnerMatcherTest extends TestCase
{
    public function test_matches_by_email_first_case_insensitive(): void
    {
        $partners = [
            new EracuniPartner('1', 'Netko Drugi', 'ivana.horvat@example.com'),
            new EracuniPartner('2', 'Ivana Horvat', 'drugi@example.com'),
        ];

        $proposal = PartnerMatcher::propose(
            new PayerCandidate('IVANA.HORVAT@example.com', 'Ivana Horvat'),
            $partners,
        );

        $this->assertNotNull($proposal);
        $this->assertSame('1', $proposal->partner->id); // e-mail pobjeđuje nad imenom
        $this->assertSame('email', $proposal->reason);
    }

    public function test_matches_by_name_when_no_email_match_diacritic_insensitive(): void
    {
        $partners = [new EracuniPartner('7', 'Željko Čorić', 'drugi@example.com')];

        $proposal = PartnerMatcher::propose(
            new PayerCandidate('ne-postoji@example.com', 'zeljko coric'),
            $partners,
        );

        $this->assertNotNull($proposal);
        $this->assertSame('7', $proposal->partner->id);
        $this->assertSame('name', $proposal->reason);
    }

    public function test_returns_null_when_nothing_matches(): void
    {
        $partners = [new EracuniPartner('9', 'Netko Drugi', 'netko@example.com')];

        $proposal = PartnerMatcher::propose(
            new PayerCandidate('nema@example.com', 'Marko Marić'),
            $partners,
        );

        $this->assertNull($proposal);
    }
}
