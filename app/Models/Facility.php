<?php

// app/Models/Facility.php

namespace App\Models;

use App\Models\Concerns\HasLocalizedColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Facility extends Model
{
    use HasLocalizedColumns;

    protected $fillable = ['name_id', 'name_en', 'icon'];

    public function roomTypes(): BelongsToMany
    {
        return $this->belongsToMany(RoomType::class);
    }

    public function getNameAttribute(): ?string
    {
        return $this->localized('name');
    }
}
