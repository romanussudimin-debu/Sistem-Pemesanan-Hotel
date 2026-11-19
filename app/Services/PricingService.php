<?php

// app/Services/PricingService.php

namespace App\Services;

use App\Models\RoomType;
use App\Support\PriceBreakdown;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;

class PricingService
{
    /**
     * FR-08: tarif per malam (BR-07, tarif musiman menimpa tarif dasar) × jumlah kamar + pajak & layanan (BR-08).
     * Tanggal check-out tidak dihitung sebagai malam.
     */
    public function quote(RoomType $type, CarbonInterface $checkIn, CarbonInterface $checkOut, int $rooms = 1): PriceBreakdown
    {
        $type->loadMissing('rates');

        $nights = [];
        foreach (CarbonPeriod::create($checkIn->toDateString(), CarbonImmutable::parse($checkOut)->subDay()->toDateString()) as $date) {
            $nights[] = $type->rateFor(CarbonImmutable::instance($date));
        }

        $perRoom = array_sum(array_column($nights, 'price'));
        $subtotal = $perRoom * $rooms;
        $taxRate = (float) config('hotel.tax_rate');
        $tax = round($subtotal * $taxRate / 100); // dibulatkan ke rupiah penuh

        return new PriceBreakdown($nights, $rooms, $perRoom, $subtotal, $taxRate, $tax, $subtotal + $tax);
    }
}
