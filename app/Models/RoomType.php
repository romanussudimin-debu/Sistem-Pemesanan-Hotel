<?php

// app/Models/RoomType.php

namespace App\Models;

use App\Models\Concerns\HasLocalizedColumns;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoomType extends Model
{
    use HasFactory, HasLocalizedColumns, SoftDeletes;

    protected $fillable = [
        'branch_id', 'name', 'slug', 'description_id', 'description_en',
        'capacity', 'size_sqm', 'base_price', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'size_sqm' => 'integer',
            'base_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(RoomTypeImage::class)->orderBy('sort_order');
    }

    public function rates(): HasMany
    {
        return $this->hasMany(RoomRate::class);
    }

    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(Facility::class);
    }

    public function reservationRooms(): HasMany
    {
        return $this->hasMany(ReservationRoom::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->localized('description');
    }

    /**
     * Tarif untuk satu malam (BR-07): tarif musiman yang mencakup tanggal tsb, jika ada; selain itu base_price.
     * Memakai relasi `rates` yang sudah di-eager-load bila tersedia agar tidak N+1 query.
     */
    public function rateFor(CarbonInterface $date): array
    {
        $day = $date->toDateString();

        $seasonal = $this->relationLoaded('rates')
            ? $this->rates->first(fn (RoomRate $r) => $r->start_date->toDateString() <= $day && $r->end_date->toDateString() >= $day)
            : $this->rates()->covering($date)->first();

        return $seasonal
            ? ['date' => $day, 'price' => (float) $seasonal->price, 'source' => 'seasonal', 'label' => $seasonal->label]
            : ['date' => $day, 'price' => (float) $this->base_price, 'source' => 'base', 'label' => null];
    }
}
