<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $total = (int) env('ORDER_SEED_COUNT', 100000);
        $chunkSize = (int) env('ORDER_SEED_CHUNK', 1000);
        $statuses = ['pending', 'paid', 'shipped', 'completed', 'cancelled'];
        $now = now();

        for ($offset = 0; $offset < $total; $offset += $chunkSize) {
            $rows = [];
            $limit = min($chunkSize, $total - $offset);

            for ($index = 1; $index <= $limit; $index++) {
                $number = $offset + $index;

                $rows[] = [
                    'invoice_number' => 'INV-'.str_pad((string) $number, 8, '0', STR_PAD_LEFT),
                    'customer_name' => 'Customer '.$number,
                    'customer_email' => 'customer'.$number.'@example.test',
                    'status' => $statuses[$number % count($statuses)],
                    'total' => random_int(2500, 50000000) / 100,
                    'ordered_at' => $now->copy()->subMinutes($number),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('orders')->insert($rows);
        }
    }
}
