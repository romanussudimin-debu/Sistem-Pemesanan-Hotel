<?php

// tests/Concerns/BookingFixtures.php

namespace Tests\Concerns;

use App\Enums\UserRole;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Models\User;
use App\Services\ReservationService;
use Carbon\CarbonImmutable;
use Database\Seeders\BranchSeeder;
use Database\Seeders\FacilitySeeder;

trait BookingFixtures
{
    protected function seedHotel(): void
    {
        $this->seed([FacilitySeeder::class, BranchSeeder::class]);
    }

    protected function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => UserRole::Admin])->save();

        return $admin;
    }

    protected function roomType(string $name): RoomType
    {
        return RoomType::where('name', $name)->firstOrFail();
    }

    /** Menyisakan hanya $keep kamar bookable pada tipe kamar (kamar lain dinonaktifkan). */
    protected function limitRooms(RoomType $type, int $keep): void
    {
        $type->rooms()->orderBy('number')->get()->slice($keep)->each->update(['status' => 'inactive']);
    }

    protected function book(User $user, RoomType $type, int $daysFromToday, int $nights, array $guests = [1]): Reservation
    {
        $checkIn = CarbonImmutable::today()->addDays($daysFromToday);

        return app(ReservationService::class)->create($user, [
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkIn->addDays($nights)->toDateString(),
            'guest_name' => $user->name,
            'guest_phone' => '+62 812 0000 0000',
            'items' => [['room_type_id' => $type->id, 'guests' => $guests]],
        ]);
    }
}
