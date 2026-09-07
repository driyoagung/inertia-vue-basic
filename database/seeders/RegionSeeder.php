<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Province;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jabar = Province::create(['name' => 'Jawa Barat']);
        $bandung = $jabar->cities()->create(['name' => 'Kota Bandung']);
        $bandung->districts()->createMany([
            ['name' => 'Andir'],
            ['name' => 'Astana Anyar'],
            ['name' => 'Babakan Ciparay'],
        ]);
        $cimahi = $jabar->cities()->create(['name' => 'Kota Cimahi']);
        $cimahi->districts()->createMany([
            ['name' => 'Cimahi Selatan'],
            ['name' => 'Cimahi Tengah'],
            ['name' => 'Cimahi Utara'],
        ]);

        $jateng = Province::create(['name' => 'Jawa Tengah']);
        $semarang = $jateng->cities()->create(['name' => 'Kota Semarang']);
        $semarang->districts()->createMany([
            ['name' => 'Banyumanik'],
            ['name' => 'Candisari'],
            ['name' => 'Gajahmungkur'],
        ]);
        $solo = $jateng->cities()->create(['name' => 'Kota Surakarta']);
        $solo->districts()->createMany([
            ['name' => 'Banjarsari'],
            ['name' => 'Jebres'],
            ['name' => 'Laweyan'],
        ]);
    }
}
