<?php

// app/Support/PriceBreakdown.php

namespace App\Support;

/**
 * Hasil perhitungan harga (value object, tidak dapat diubah).
 */
final readonly class PriceBreakdown
{
    /**
     * @param  list<array{date: string, price: float, source: string, label: ?string}>  $nights  tarif per malam untuk 1 kamar
     */
    public function __construct(
        public array $nights,
        public int $rooms,
        public float $perRoom,
        public float $subtotal,
        public float $taxRate,
        public float $tax,
        public float $total,
    ) {}

    public function nightCount(): int
    {
        return count($this->nights);
    }

    public function hasSeasonalRate(): bool
    {
        return collect($this->nights)->contains('source', 'seasonal');
    }
}
