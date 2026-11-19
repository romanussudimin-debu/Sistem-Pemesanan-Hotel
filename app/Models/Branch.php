<?php

// app/Models/Branch.php

namespace App\Models;

use App\Models\Concerns\HasLocalizedColumns;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory, HasLocalizedColumns, SoftDeletes;

    protected $fillable = [
        'code', 'name', 'slug', 'city', 'address', 'phone', 'email',
        'description_id', 'description_en', 'cover_image',
        'bank_name', 'bank_account_no', 'bank_account_name', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function roomTypes(): HasMany
    {
        return $this->hasMany(RoomType::class);
    }

    public function rooms(): HasManyThrough
    {
        return $this->hasManyThrough(Room::class, RoomType::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->localized('description');
    }
}
