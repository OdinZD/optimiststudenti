<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Charge;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Student;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Records membership payments, write-offs and reversals. Every money operation runs in
 * a DB transaction and writes an audit entry. Allocations never exceed the payment nor
 * the charge; overpayment stays unallocated (the student's credit / neraspoređeni saldo).
 */
final class PaymentRecorder
{
    /**
     * Record a payment. Allocates to the student's open charges, oldest first (FIFO),
     * or to $chargeIds when given. Any amount beyond the covered charges stays unallocated.
     *
     * @param  list<int>|null  $chargeIds
     */
    public static function record(
        Student $student,
        int $amountCents,
        string $method,
        string $source,
        string $paidAt,
        ?string $bankReference,
        ?string $note,
        ?int $recordedBy,
        ?array $chargeIds = null,
        ?string $receivedBy = null,
    ): Payment {
        return DB::transaction(function () use ($student, $amountCents, $method, $source, $paidAt, $bankReference, $note, $recordedBy, $chargeIds, $receivedBy): Payment {
            $payment = Payment::create([
                'student_id' => $student->id,
                'amount_cents' => $amountCents,
                'paid_at' => $paidAt,
                'method' => $method,
                'source' => $source,
                'bank_reference' => $bankReference,
                'note' => $note,
                'recorded_by' => $recordedBy,
                'received_by' => $receivedBy,
            ]);

            $remaining = $amountCents;

            foreach (self::openCharges($student->id, $chargeIds) as $charge) {
                if ($remaining <= 0) {
                    break;
                }

                $allocate = min($remaining, $charge->outstandingCents());

                if ($allocate <= 0) {
                    continue;
                }

                PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'charge_id' => $charge->id,
                    'amount_cents' => $allocate,
                ]);

                $remaining -= $allocate;
            }

            Auditer::log('payment.recorded', $payment, null, [
                'amount_cents' => $amountCents,
                'allocated' => $amountCents - $remaining,
                'unallocated' => $remaining,
            ]);

            return $payment;
        });
    }

    /** Write off a whole charge (reason required). */
    public static function writeOff(Charge $charge, string $reason, ?int $userId): void
    {
        if (trim($reason) === '') {
            throw new RuntimeException('Razlog otpisa je obavezan.');
        }

        DB::transaction(function () use ($charge, $reason, $userId): void {
            $old = $charge->only(['written_off_at', 'written_off_by', 'written_off_reason']);

            $charge->update([
                'written_off_at' => now(),
                'written_off_by' => $userId,
                'written_off_reason' => $reason,
            ]);

            Auditer::log('charge.written_off', $charge, $old, $charge->only(['written_off_at', 'written_off_by', 'written_off_reason']));
        });
    }

    /** Reverse (storno) a payment: a negative mirror payment + mirror allocations (charges reopen). */
    public static function reverse(Payment $payment, ?int $userId): Payment
    {
        if ($payment->isReversal()) {
            throw new RuntimeException('Storno zapis se ne može stornirati.');
        }

        if (Payment::where('reversed_payment_id', $payment->id)->exists()) {
            throw new RuntimeException('Uplata je već stornirana.');
        }

        return DB::transaction(function () use ($payment, $userId): Payment {
            $reversal = Payment::create([
                'student_id' => $payment->student_id,
                'amount_cents' => -$payment->amount_cents,
                'paid_at' => now()->toDateString(),
                'method' => $payment->method,
                'source' => $payment->source,
                'bank_reference' => $payment->bank_reference,
                'note' => 'Storno uplate #'.$payment->id,
                'recorded_by' => $userId,
                'reversed_payment_id' => $payment->id,
            ]);

            foreach ($payment->allocations as $allocation) {
                PaymentAllocation::create([
                    'payment_id' => $reversal->id,
                    'charge_id' => $allocation->charge_id,
                    'amount_cents' => -$allocation->amount_cents,
                ]);
            }

            Auditer::log('payment.reversed', $reversal, null, [
                'reversed_payment_id' => $payment->id,
                'amount_cents' => -$payment->amount_cents,
            ]);

            return $reversal;
        });
    }

    /**
     * Student's open charges (not written off, outstanding > 0), oldest first.
     *
     * @param  list<int>|null  $chargeIds
     * @return Collection<int, Charge>
     */
    private static function openCharges(int $studentId, ?array $chargeIds): Collection
    {
        return Charge::query()
            ->where('student_id', $studentId)
            ->whereNull('written_off_at')
            ->when($chargeIds !== null, fn ($q) => $q->whereIn('id', $chargeIds))
            ->with('allocations')
            ->orderBy('period')
            ->get()
            ->filter(fn (Charge $c): bool => $c->outstandingCents() > 0)
            ->values();
    }
}
