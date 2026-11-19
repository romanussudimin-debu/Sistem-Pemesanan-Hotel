<?php

// app/Enums/RoomStatus.php

namespace App\Enums;

enum RoomStatus: string
{
    case Available = 'available';
    case Maintenance = 'maintenance';
    case Inactive = 'inactive';

    public function label(): string
    {
        return __("enums.room_status.{$this->value}");
    }

    /** Kelas Tailwind untuk badge (lihat Tahap 4, 4.3) */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Available => 'bg-emerald-100 text-emerald-800',
            self::Maintenance => 'bg-amber-100 text-amber-800',
            self::Inactive => 'bg-zinc-200 text-zinc-700',
        };
    }
}
