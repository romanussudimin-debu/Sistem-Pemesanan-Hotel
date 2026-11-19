<?php

// app/Services/AvailabilityService.php

namespace App\Services;

use App\Models\Branch;
use App\Models\Room;
use App\Models\RoomType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Logika ketersediaan kamar (Tahap 3, 3.3). Sebuah kamar tersedia pada [checkIn, checkOut) bila
 * berstatus available (BR-06) dan tidak punya baris room_nights pada rentang itu (BR-05).
 */
class AvailabilityService
{
    public function __construct(private readonly PricingService $pricing) {}

    /** Query kamar bebas untuk satu tipe kamar, diurutkan nomor kamar agar alokasi deterministik. */
    public function freeRooms(int $roomTypeId, CarbonInterface $checkIn, CarbonInterface $checkOut): Builder
    {
        return Room::query()
            ->where('room_type_id', $roomTypeId)
            ->bookable()
            ->freeBetween($checkIn->toDateString(), $checkOut->toDateString())
            ->orderBy('number');
    }

    public function availableCount(RoomType $type, CarbonInterface $checkIn, CarbonInterface $checkOut): int
    {
        return $this->freeRooms($type->id, $checkIn, $checkOut)->count();
    }

    /**
     * UC-05: semua tipe kamar aktif di cabang beserta jumlah kamar tersedia, harga, dan status "muat".
     * Tipe yang tidak memenuhi (habis/kapasitas kurang) tetap dikembalikan dengan fits=false agar bisa ditampilkan redup.
     *
     * @return Collection<int, RoomType> dengan atribut tambahan: available_rooms, fits, quote
     */
    public function searchByBranch(Branch $branch, CarbonInterface $checkIn, CarbonInterface $checkOut, int $guests, int $rooms): Collection
    {
        $in = $checkIn->toDateString();
        $out = $checkOut->toDateString();

        return $branch->roomTypes()
            ->active()
            ->with(['facilities', 'images', 'rates'])
            ->withCount(['rooms as available_rooms' => fn (Builder $q) => $q->bookable()->freeBetween($in, $out)])
            ->get()
            ->each(function (RoomType $type) use ($checkIn, $checkOut, $guests, $rooms) {
                $type->fits = $type->available_rooms >= $rooms && $type->capacity * $rooms >= $guests; // BR-04
                $type->quote = $this->pricing->quote($type, $checkIn, $checkOut, $rooms);
            })
            ->sortBy([['fits', 'desc'], ['base_price', 'asc']])
            ->values();
    }

    /** UC-05 A2: saran cabang lain yang masih punya tipe kamar memenuhi syarat pada tanggal yang sama. */
    public function alternativeBranches(Branch $except, CarbonInterface $checkIn, CarbonInterface $checkOut, int $guests, int $rooms): Collection
    {
        return Branch::active()->whereKeyNot($except->id)->orderBy('name')->get()
            ->map(fn (Branch $b) => [
                'branch' => $b,
                'types' => $this->searchByBranch($b, $checkIn, $checkOut, $guests, $rooms)->where('fits', true)->count(),
            ])
            ->where('types', '>', 0)
            ->values();
    }
}
