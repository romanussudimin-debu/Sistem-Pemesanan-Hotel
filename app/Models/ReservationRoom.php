<?php

// app/Models/ReservationRoom.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReservationRoom extends Model
{
    protected $fillable = ['reservation_id', 'room_id', 'room_type_id', 'guests', 'rate_breakdown', 'line_total'];

    protected function casts(): array
    {
        return [
            'guests' => 'integer',
            'rate_breakdown' => 'array', // [{date, price, source, label}, ...]
            'line_total' => 'decimal:2',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class)->withTrashed();
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class)->withTrashed();
    }

    public function nights(): HasMany
    {
        return $this->hasMany(RoomNight::class);
    }
}
