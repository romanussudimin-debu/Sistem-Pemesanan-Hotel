<?php

// app/Models/Reservation.php

namespace App\Models;

use App\Enums\RefundStatus;
use App\Enums\ReservationStatus;
use App\Exceptions\InvalidStatusTransitionException;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reservation extends Model
{
    use HasFactory;

    // status, paid_at, dll. sengaja tidak fillable: hanya boleh berubah lewat transitionTo() / service
    protected $fillable = [
        'code', 'user_id', 'branch_id', 'check_in', 'check_out', 'nights', 'total_guests',
        'guest_name', 'guest_phone', 'special_request',
        'subtotal', 'tax_rate', 'tax_amount', 'total_amount', 'payment_deadline',
    ];

    protected function casts(): array
    {
        return [
            'check_in' => 'immutable_date',
            'check_out' => 'immutable_date',
            'nights' => 'integer',
            'total_guests' => 'integer',
            'subtotal' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'refund_amount' => 'decimal:2',
            'status' => ReservationStatus::class,
            'refund_status' => RefundStatus::class,
            'payment_deadline' => 'immutable_datetime',
            'paid_at' => 'immutable_datetime',
            'checked_in_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
            'rejection_count' => 'integer',
        ];
    }

    /** URL memakai kode reservasi, bukan ID berurutan (menyulitkan tebak-tebakan URL). */
    public function getRouteKeyName(): string
    {
        return 'code';
    }

    // ---------------------------------------------------------------- Relasi

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(ReservationRoom::class);
    }

    public function nights(): HasManyThrough
    {
        return $this->hasManyThrough(RoomNight::class, ReservationRoom::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(ReservationStatusLog::class)->orderBy('id');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    // ---------------------------------------------------------------- Scope

    public function scopeStatus(Builder $query, ReservationStatus ...$statuses): Builder
    {
        return $query->whereIn('status', ReservationStatus::values($statuses));
    }

    public function scopeOverdue(Builder $query, ?CarbonInterface $now = null): Builder
    {
        return $query->where('status', ReservationStatus::PendingPayment)
            ->where('payment_deadline', '<', ($now ?? now())->toDateTimeString());
    }

    // ---------------------------------------------------------------- Aturan bisnis

    /**
     * Satu-satunya jalan mengubah status: memvalidasi transisi (state machine) lalu menulis log audit (NFR-14).
     * Pemanggil bertanggung jawab membungkusnya dalam DB::transaction bila ada perubahan data lain.
     */
    public function transitionTo(ReservationStatus $target, ?User $actor = null, ?string $note = null): static
    {
        $from = $this->status;

        // Status awal (reservasi baru) hanya boleh PendingPayment
        $valid = $from === null ? $target === ReservationStatus::PendingPayment : $from->canTransitionTo($target);
        if (! $valid) {
            throw new InvalidStatusTransitionException($from, $target);
        }

        $this->status = $target;
        $now = now();
        match ($target) {
            ReservationStatus::Paid => $this->paid_at ??= $now,
            ReservationStatus::CheckedIn => $this->checked_in_at = $now,
            ReservationStatus::Completed => $this->completed_at = $now,
            ReservationStatus::Cancelled => $this->cancelled_at = $now,
            default => null,
        };
        $this->save();

        $this->statusLogs()->create([
            'from_status' => $from?->value,
            'to_status' => $target->value,
            'changed_by' => $actor?->id,
            'note' => $note,
        ]);

        return $this;
    }

    /** BR-15 */
    public function isCancellable(): bool
    {
        return $this->status->isCancellable();
    }

    public function isOverdue(): bool
    {
        return $this->status === ReservationStatus::PendingPayment && $this->payment_deadline->isPast();
    }

    public function remainingRejections(): int
    {
        return max(0, config('hotel.payment.max_rejections') - $this->rejection_count);
    }

    /**
     * BR-09: batas bayar 24 jam sejak reservasi dibuat, atau 2 jam bila check-in kurang dari 24 jam lagi.
     */
    public static function computeDeadline(CarbonInterface $createdAt, CarbonInterface $checkInDate): CarbonImmutable
    {
        $created = CarbonImmutable::instance($createdAt);
        $checkInAt = CarbonImmutable::parse($checkInDate->toDateString().' '.config('hotel.check_in_time'));

        $hours = $created->diffInHours($checkInAt, false) < 24
            ? config('hotel.payment.short_deadline_hours')
            : config('hotel.payment.deadline_hours');

        return $created->addHours($hours);
    }

    /** FR-11: NSH-{CABANG}-{YYYYMMDD}-{urut 4 digit}, nomor urut per cabang per hari. */
    public static function generateCode(Branch $branch, ?CarbonInterface $date = null): string
    {
        $date ??= now();
        $prefix = sprintf('NSH-%s-%s-', $branch->code, $date->format('Ymd'));

        $last = static::where('code', 'like', $prefix.'%')->orderByDesc('code')->value('code');
        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
