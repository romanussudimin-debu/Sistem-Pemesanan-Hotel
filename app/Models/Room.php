<?php

// app/Models/Room.php

namespace App\Models;

use App\Enums\RoomStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['room_type_id', 'number', 'floor', 'status', 'notes'];

    protected function casts(): array
    {
        return [
            'floor' => 'integer',
            'status' => RoomStatus::class,
        ];
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function nights(): HasMany
    {
        return $this->hasMany(RoomNight::class);
    }

    public function reservationRooms(): HasMany
    {
        return $this->hasMany(ReservationRoom::class);
    }

    /** BR-06: hanya kamar berstatus available yang dapat dipesan. */
    public function isBookable(): bool
    {
        return $this->status === RoomStatus::Available;
    }

    public function scopeBookable(Builder $query): Builder
    {
        return $query->where('status', RoomStatus::Available);
    }

    /** Kamar yang tidak punya malam terpakai pada [checkIn, checkOut) — BR-05 (rentang setengah terbuka). */
    public function scopeFreeBetween(Builder $query, string $checkIn, string $checkOut): Builder
    {
        return $query->whereDoesntHave('nights', fn (Builder $q) => $q
            ->where('stay_date', '>=', $checkIn)
            ->where('stay_date', '<', $checkOut));
    }

    /** FR-27: kamar yang pernah dipesan tidak boleh dihapus permanen. */
    public function hasBookingHistory(): bool
    {
        return $this->reservationRooms()->exists();
    }
}
