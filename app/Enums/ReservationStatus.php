<?php

// app/Enums/ReservationStatus.php

namespace App\Enums;

/**
 * Status reservasi beserta mesin status (state machine) sesuai BR-18 / state diagram Tahap 2.
 */
enum ReservationStatus: string
{
    case PendingPayment = 'pending_payment';
    case AwaitingVerification = 'awaiting_verification';
    case Paid = 'paid';
    case CheckedIn = 'checked_in';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    /** Transisi yang diizinkan dari status ini. */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PendingPayment => [self::AwaitingVerification, self::Paid, self::Expired, self::Cancelled],
            self::AwaitingVerification => [self::Paid, self::PendingPayment, self::Cancelled],
            self::Paid => [self::CheckedIn, self::Cancelled],
            self::CheckedIn => [self::Completed],
            // Expired -> Paid hanya untuk kasus callback gateway terlambat yang berhasil dialokasikan ulang (Tahap 3, 3.3.4)
            self::Expired => [self::Paid],
            self::Completed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /** Status yang menahan inventaris kamar (baris room_nights ada). BR-05 */
    public static function holdingInventory(): array
    {
        return [self::PendingPayment, self::AwaitingVerification, self::Paid, self::CheckedIn, self::Completed];
    }

    /** Status yang dihitung sebagai malam-kamar terjual dalam laporan okupansi. */
    public static function sold(): array
    {
        return [self::Paid, self::CheckedIn, self::Completed];
    }

    /** BR-15: status yang masih boleh dibatalkan. */
    public function isCancellable(): bool
    {
        return in_array($this, [self::PendingPayment, self::AwaitingVerification, self::Paid], true);
    }

    public function label(): string
    {
        return __("enums.reservation_status.{$this->value}");
    }

    /** Warna badge (Tahap 4, 4.3.2): teks + warna, tidak hanya warna. */
    public function badgeClass(): string
    {
        return match ($this) {
            self::PendingPayment => 'bg-amber-100 text-amber-800',
            self::AwaitingVerification => 'bg-sky-100 text-sky-800',
            self::Paid => 'bg-emerald-100 text-emerald-800',
            self::CheckedIn => 'bg-indigo-100 text-indigo-800',
            self::Completed => 'bg-slate-100 text-slate-700',
            self::Cancelled => 'bg-red-100 text-red-800',
            self::Expired => 'bg-zinc-200 text-zinc-700',
        };
    }

    /** @return list<string> */
    public static function values(array $cases): array
    {
        return array_map(fn (self $c) => $c->value, $cases);
    }
}
