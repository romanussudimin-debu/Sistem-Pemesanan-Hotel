<?php

// database/seeders/FacilitySeeder.php

namespace Database\Seeders;

use App\Models\Facility;
use Illuminate\Database\Seeder;

class FacilitySeeder extends Seeder
{
    public function run(): void
    {
        $facilities = [
            ['ac', 'AC', 'Air Conditioning'],
            ['wifi', 'Wi-Fi Gratis', 'Free Wi-Fi'],
            ['tv', 'TV Layar Datar', 'Flat-screen TV'],
            ['shower', 'Shower Air Panas', 'Hot Shower'],
            ['bathtub', 'Bathtub', 'Bathtub'],
            ['balcony', 'Balkon Pribadi', 'Private Balcony'],
            ['coffee', 'Teh & Kopi', 'Tea & Coffee Maker'],
            ['safe', 'Brankas', 'In-room Safe'],
            ['pool', 'Kolam Renang Pribadi', 'Private Pool'],
            ['kitchen', 'Dapur Kecil', 'Kitchenette'],
            ['breakfast', 'Sarapan', 'Breakfast Included'],
            ['view', 'Pemandangan Laut', 'Ocean View'],
        ];

        foreach ($facilities as [$icon, $id, $en]) {
            Facility::create(['icon' => $icon, 'name_id' => $id, 'name_en' => $en]);
        }
    }
}
