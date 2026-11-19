<?php

// app/Console/Commands/ConcurrencyTest.php

namespace App\Console\Commands;

use App\Exceptions\RoomNotAvailableException;
use App\Models\Reservation;
use App\Models\RoomNight;
use App\Models\RoomType;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\ReservationService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * TC-AV-07 / NFR-07: uji konkurensi sungguhan. N proses paralel memesan tipe kamar yang sama pada malam yang sama
 * secara serentak. Hasil benar: jumlah yang berhasil = jumlah kamar bebas, sisanya ditolak, tanpa malam ganda.
 * Membutuhkan MySQL/MariaDB (SQLite mengunci seluruh file sehingga tidak menguji penguncian baris) dan ekstensi pcntl.
 */
#[Signature('hotel:concurrency-test {--workers=10 : Jumlah proses paralel} {--type=Pool Villa : Nama tipe kamar} {--keep : Jangan hapus data uji}')]
#[Description('Uji konkurensi anti-overbooking (TC-AV-07): banyak proses memperebutkan kamar terakhir secara bersamaan')]
class ConcurrencyTest extends Command
{
    public function handle(AvailabilityService $availability): int
    {
        if (! function_exists('pcntl_fork') || DB::connection()->getDriverName() === 'sqlite') {
            $this->error('Perintah ini membutuhkan ekstensi pcntl dan koneksi MySQL/MariaDB.');

            return self::FAILURE;
        }

        Notification::fake(); // jangan kirim email saat uji
        $workers = max(2, (int) $this->option('workers'));
        $type = RoomType::where('name', $this->option('type'))->firstOrFail();

        // Cari malam di masa depan yang seluruh kamarnya masih bebas
        $date = CarbonImmutable::today()->addDays(200);
        while ($availability->availableCount($type, $date, $date->addDay()) < $type->rooms()->bookable()->count()) {
            $date = $date->addDay();
        }
        $free = $availability->availableCount($type, $date, $date->addDay());

        $user = User::factory()->create(['name' => 'Uji Konkurensi', 'email' => 'concurrency-'.uniqid().'@contoh.test']);
        $this->info("Tipe: {$type->name} | Malam: {$date->toDateString()} | Kamar bebas: {$free} | Proses paralel: {$workers}");

        DB::disconnect(); // setiap proses anak wajib membuka koneksinya sendiri
        $startAt = microtime(true) + 1.0; // titik start bersama agar permintaan benar-benar serentak
        $pids = [];

        for ($i = 0; $i < $workers; $i++) {
            $pid = pcntl_fork();
            if ($pid === 0) {
                DB::reconnect();
                time_sleep_until($startAt);
                try {
                    app(ReservationService::class)->create($user, [
                        'check_in' => $date->toDateString(),
                        'check_out' => $date->addDay()->toDateString(),
                        'guest_name' => "Pekerja #{$i}",
                        'guest_phone' => '+62 800 0000 0000',
                        'items' => [['room_type_id' => $type->id, 'guests' => [1]]],
                    ]);
                    exit(0);  // berhasil
                } catch (RoomNotAvailableException) {
                    exit(1);  // ditolak dengan benar
                } catch (\Throwable $e) {
                    fwrite(STDERR, $e->getMessage().PHP_EOL);
                    exit(2);  // galat tak terduga
                }
            }
            $pids[] = $pid;
        }

        $codes = [0 => 0, 1 => 0, 2 => 0];
        foreach ($pids as $pid) {
            pcntl_waitpid($pid, $status);
            $codes[pcntl_wexitstatus($status)] = ($codes[pcntl_wexitstatus($status)] ?? 0) + 1;
        }

        DB::reconnect();
        $duplicates = RoomNight::where('stay_date', $date->toDateString())
            ->select('room_id')->groupBy('room_id')->havingRaw('COUNT(*) > 1')->count();
        $saved = Reservation::where('user_id', $user->id)->count();

        $this->table(['Berhasil', 'Ditolak (kamar habis)', 'Galat lain', 'Reservasi tersimpan', 'Malam ganda'],
            [[$codes[0], $codes[1], $codes[2], $saved, $duplicates]]);

        $passed = $codes[0] === min($free, $workers) && $saved === $codes[0] && $duplicates === 0 && $codes[2] === 0;
        $passed ? $this->info('LULUS: tidak terjadi overbooking.') : $this->error('GAGAL: periksa hasil di atas.');

        if (! $this->option('keep')) {
            Reservation::where('user_id', $user->id)->get()->each->delete(); // cascade: item, malam, log
            $user->delete();
        }

        return $passed ? self::SUCCESS : self::FAILURE;
    }
}
