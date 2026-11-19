<?php

// database/seeders/UserSeeder.php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Akun demo (kata sandi semua: "password") — dicantumkan di README
        $admin = User::factory()->create([
            'name' => 'Admin Nusa Stay',
            'email' => 'admin@nusastay.test',
            'phone' => '+62 361 000 0000',
        ]);
        $admin->forceFill(['role' => UserRole::Admin])->save(); // role tidak fillable (mass assignment)

        $demoCustomers = [
            ['name' => 'Budi Santoso', 'email' => 'budi@contoh.test', 'phone' => '+62 812 1111 2222', 'locale' => 'id'],
            ['name' => 'Siti Aminah', 'email' => 'siti@contoh.test', 'phone' => '+62 813 3333 4444', 'locale' => 'id'],
            ['name' => 'Ana Pratiwi', 'email' => 'ana@contoh.test', 'phone' => '+62 857 5555 6666', 'locale' => 'id'],
            ['name' => 'John Carter', 'email' => 'john@example.test', 'phone' => '+61 400 123 456', 'locale' => 'en'],
        ];
        foreach ($demoCustomers as $data) {
            User::factory()->create($data);
        }

        // Pelanggan tambahan untuk meramaikan histori reservasi
        User::factory(16)->create();
    }
}
