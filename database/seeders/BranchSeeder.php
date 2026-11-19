<?php

// database/seeders/BranchSeeder.php

namespace Database\Seeders;

use App\Enums\RoomStatus;
use App\Models\Branch;
use App\Models\Facility;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * 3 cabang, 10 tipe kamar, 60 kamar fisik, beserta tarif musiman. Semua data fiktif.
 */
class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $facilityIds = Facility::pluck('id', 'icon'); // ['wifi' => 2, ...]
        $year = CarbonImmutable::today()->year;

        foreach ($this->branches() as $branchData) {
            $types = $branchData['types'];
            $maintenance = $branchData['_maintenance'] ?? [];
            $inactive = $branchData['_inactive'] ?? [];
            unset($branchData['types'], $branchData['_maintenance'], $branchData['_inactive']);

            $branch = Branch::create($branchData + ['slug' => Str::slug($branchData['name'])]);

            foreach ($types as $t) {
                $type = $branch->roomTypes()->create([
                    'name' => $t['name'],
                    'slug' => Str::slug($t['name']),
                    'description_id' => $t['desc_id'],
                    'description_en' => $t['desc_en'],
                    'capacity' => $t['capacity'],
                    'size_sqm' => $t['size'],
                    'base_price' => $t['price'],
                ]);

                $type->facilities()->attach($facilityIds->only($t['facilities'])->values());

                // Kamar fisik: nomor = prefiks lantai + urutan, mis. 201, 202, ...
                for ($i = 1; $i <= $t['rooms']; $i++) {
                    $number = $t['prefix'].str_pad((string) $i, 2, '0', STR_PAD_LEFT);
                    $type->rooms()->create([
                        'number' => $number,
                        'floor' => $t['floor'],
                        // Satu kamar perawatan di Jimbaran dan satu nonaktif di Seminyak untuk uji BR-06 / TC-AV-04
                        'status' => match (true) {
                            in_array($number, $maintenance, true) => RoomStatus::Maintenance,
                            in_array($number, $inactive, true) => RoomStatus::Inactive,
                            default => RoomStatus::Available,
                        },
                    ]);
                }

                // Tarif musiman (BR-07): liburan Juli–Agustus +25%, Natal & Tahun Baru +40%
                $type->rates()->createMany([
                    [
                        'label' => 'Musim Liburan Juli–Agustus',
                        'start_date' => "{$year}-07-01",
                        'end_date' => "{$year}-08-31",
                        'price' => round($t['price'] * 1.25, -3),
                    ],
                    [
                        'label' => 'Natal & Tahun Baru',
                        'start_date' => "{$year}-12-20",
                        'end_date' => ($year + 1).'-01-05',
                        'price' => round($t['price'] * 1.40, -3),
                    ],
                ]);
            }
        }
    }

    private function branches(): array
    {
        $common = ['ac', 'wifi', 'tv', 'shower', 'coffee', 'safe'];

        return [
            [
                'code' => 'JMB', 'name' => 'Nusa Stay Jimbaran', 'city' => 'Jimbaran, Badung',
                'address' => 'Jl. Pantai Kedonganan No. 8 (fiktif), Jimbaran, Bali',
                'phone' => '+62 361 700 101', 'email' => 'jimbaran@nusastay.test',
                'description_id' => 'Resor tepi pantai dengan pemandangan matahari terbenam dan makan malam seafood di atas pasir.',
                'description_en' => 'A beachfront resort with sunset views and seafood dinners on the sand.',
                'bank_name' => 'Bank ABC (contoh)', 'bank_account_no' => '123 456 7890', 'bank_account_name' => 'PT Nusa Stay Jimbaran',
                '_maintenance' => ['102'],
                'types' => [
                    ['name' => 'Superior Garden', 'capacity' => 2, 'size' => 26, 'price' => 850000, 'rooms' => 6, 'prefix' => '1', 'floor' => 1,
                        'facilities' => $common,
                        'desc_id' => 'Kamar nyaman di lantai dasar dengan akses langsung ke taman.',
                        'desc_en' => 'A cosy ground-floor room with direct garden access.'],
                    ['name' => 'Deluxe Garden View', 'capacity' => 2, 'size' => 32, 'price' => 1200000, 'rooms' => 8, 'prefix' => '2', 'floor' => 2,
                        'facilities' => [...$common, 'balcony'],
                        'desc_id' => 'Kamar menghadap taman tropis, tempat tidur king, dan balkon pribadi.',
                        'desc_en' => 'Overlooks a tropical garden, with a king bed and private balcony.'],
                    ['name' => 'Ocean Suite', 'capacity' => 3, 'size' => 48, 'price' => 2100000, 'rooms' => 5, 'prefix' => '3', 'floor' => 3,
                        'facilities' => [...$common, 'balcony', 'bathtub', 'view', 'breakfast'],
                        'desc_id' => 'Suite luas dengan pemandangan Teluk Jimbaran dan bathtub menghadap laut.',
                        'desc_en' => 'A spacious suite overlooking Jimbaran Bay with an ocean-facing bathtub.'],
                    ['name' => 'Pool Villa', 'capacity' => 4, 'size' => 120, 'price' => 3500000, 'rooms' => 3, 'prefix' => 'V', 'floor' => 1,
                        'facilities' => [...$common, 'pool', 'bathtub', 'kitchen', 'breakfast'],
                        'desc_id' => 'Vila satu kamar dengan kolam renang pribadi dan dapur kecil.',
                        'desc_en' => 'A one-bedroom villa with a private pool and kitchenette.'],
                ],
            ],
            [
                'code' => 'UBD', 'name' => 'Nusa Stay Ubud', 'city' => 'Ubud, Gianyar',
                'address' => 'Jl. Raya Sayan No. 21 (fiktif), Ubud, Bali',
                'phone' => '+62 361 700 202', 'email' => 'ubud@nusastay.test',
                'description_id' => 'Penginapan tenang di antara sawah terasering dan hutan, dekat pusat seni Ubud.',
                'description_en' => 'A tranquil retreat among rice terraces and forest, close to Ubud\'s art centre.',
                'bank_name' => 'Bank ABC (contoh)', 'bank_account_no' => '123 456 7891', 'bank_account_name' => 'PT Nusa Stay Ubud',
                'types' => [
                    ['name' => 'Superior Rice Field', 'capacity' => 2, 'size' => 28, 'price' => 650000, 'rooms' => 8, 'prefix' => '1', 'floor' => 1,
                        'facilities' => [...$common, 'balcony'],
                        'desc_id' => 'Kamar dengan teras menghadap hamparan sawah.',
                        'desc_en' => 'A room with a terrace facing the rice fields.'],
                    ['name' => 'Family Suite', 'capacity' => 4, 'size' => 55, 'price' => 1600000, 'rooms' => 4, 'prefix' => '2', 'floor' => 2,
                        'facilities' => [...$common, 'bathtub', 'breakfast'],
                        'desc_id' => 'Suite dua ruang untuk keluarga, dengan sarapan untuk empat orang.',
                        'desc_en' => 'A two-room family suite with breakfast for four.'],
                    ['name' => 'Jungle Villa', 'capacity' => 2, 'size' => 90, 'price' => 2000000, 'rooms' => 6, 'prefix' => 'V', 'floor' => 1,
                        'facilities' => [...$common, 'pool', 'bathtub', 'breakfast'],
                        'desc_id' => 'Vila di tepi lembah hutan dengan kolam rendam pribadi.',
                        'desc_en' => 'A villa on the edge of a jungle valley with a private plunge pool.'],
                ],
            ],
            [
                'code' => 'SMY', 'name' => 'Nusa Stay Seminyak', 'city' => 'Seminyak, Badung',
                'address' => 'Jl. Kayu Aya No. 45 (fiktif), Seminyak, Bali',
                'phone' => '+62 361 700 303', 'email' => 'seminyak@nusastay.test',
                'description_id' => 'Hotel butik di pusat gaya hidup Seminyak, beberapa langkah dari kafe dan butik.',
                'description_en' => 'A boutique hotel in Seminyak\'s lifestyle hub, steps from cafés and boutiques.',
                'bank_name' => 'Bank ABC (contoh)', 'bank_account_no' => '123 456 7892', 'bank_account_name' => 'PT Nusa Stay Seminyak',
                '_inactive' => ['410'],
                'types' => [
                    ['name' => 'Superior Room', 'capacity' => 2, 'size' => 24, 'price' => 750000, 'rooms' => 10, 'prefix' => '4', 'floor' => 4,
                        'facilities' => $common,
                        'desc_id' => 'Kamar modern yang praktis untuk menjelajah Seminyak.',
                        'desc_en' => 'A modern, practical base for exploring Seminyak.'],
                    ['name' => 'Deluxe Pool Access', 'capacity' => 2, 'size' => 34, 'price' => 1300000, 'rooms' => 6, 'prefix' => '1', 'floor' => 1,
                        'facilities' => [...$common, 'balcony'],
                        'desc_id' => 'Kamar lantai dasar dengan akses langsung ke kolam renang laguna.',
                        'desc_en' => 'A ground-floor room with direct access to the lagoon pool.'],
                    ['name' => 'Penthouse Suite', 'capacity' => 4, 'size' => 95, 'price' => 3200000, 'rooms' => 4, 'prefix' => '5', 'floor' => 5,
                        'facilities' => [...$common, 'bathtub', 'kitchen', 'breakfast', 'balcony'],
                        'desc_id' => 'Suite di lantai teratas dengan teras atap dan dapur kecil.',
                        'desc_en' => 'A top-floor suite with a rooftop terrace and kitchenette.'],
                ],
            ],
        ];
    }
}
