<?php

// app/Models/RoomNight.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Buku besar inventaris. Invarian: baris hanya ada untuk reservasi berstatus
 * ReservationStatus::holdingInventory(); dihapus saat dibatalkan/kedaluwarsa (Tahap 3, T11).
 */
class RoomNight extends Model
{
    public $timestamps = false;

    protected $fillable = ['reservation_room_id', 'room_id', 'stay_date'];

    protected function casts(): array
    {
        return ['stay_date' => 'immutable_date'];
    }

    public function reservationRoom(): BelongsTo
    {
        return $this->belongsTo(ReservationRoom::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
