<?php

// app/Models/RoomRate.php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomRate extends Model
{
    protected $fillable = ['room_type_id', 'label', 'start_date', 'end_date', 'price'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'price' => 'decimal:2',
        ];
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    /** Tarif yang berlaku pada tanggal tertentu (start & end inklusif). */
    public function scopeCovering(Builder $query, CarbonInterface $date): Builder
    {
        $day = $date->toDateString();

        return $query->whereDate('start_date', '<=', $day)->whereDate('end_date', '>=', $day);
    }

    /** Dipakai validasi admin: periode tarif per tipe kamar tidak boleh tumpang tindih. */
    public function scopeOverlapping(Builder $query, int $roomTypeId, string $start, string $end, ?int $ignoreId = null): Builder
    {
        return $query->where('room_type_id', $roomTypeId)
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->when($ignoreId, fn (Builder $q) => $q->whereKeyNot($ignoreId));
    }
}
