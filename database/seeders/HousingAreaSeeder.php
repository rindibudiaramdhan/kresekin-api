<?php

namespace Database\Seeders;

use App\Models\HousingArea;
use Illuminate\Database\Seeder;

class HousingAreaSeeder extends Seeder
{
    public function run(): void
    {
        $antapaniWetanAreas = [
            'Mitra Dago Parahyangan',
            'Tanjungsari Asri Residence (TSA)',
            'Sweet Antapani Residence',
            'Antapani Residence',
            'Antapani City',
            'Antapani City Indah',
            'Setra Dago',
            'Setra Dago Indah',
            'Setra Dago Utara',
            'Puri Dago',
            'Puri Dago Mas',
            'Bougenville Estate',
            'Daichi Antapani',
            'Citra Antapani',
            'The Florence Residence',
            'Pelangi Antapani',
            'Bunisari Asri',
            'Komplek Kuningan Antapani',
            'Komplek Pratista',
            'Komplek Sulaksana',
            'Komplek Sulaksana Makmur',
            'Komplek Sulaksana Baru',
            'Komplek Banjarsari',
            'Komplek Banjarsari Indah',
            'Komplek Tanjungsari',
            'Komplek Indramayu',
            'Komplek Cibatu Raya',
            'Komplek Cibodas',
            'Komplek Cikajang',
            'Komplek Atlas',
            'Komplek Jalan Depok',
            'Komplek Jalan Denpasar',
            'Komplek Jalan Subang',
            'Komplek Jalan Banyuwangi',
            'Komplek Jalan Jakarta',
            'Komplek Terusan Jakarta',
        ];

        foreach ($antapaniWetanAreas as $area) {
            HousingArea::query()->updateOrCreate(
                ['name' => $area, 'city' => 'Kota Bandung'],
                [
                    'code' => str('antapani-wetan-'.$area)->slug('-')->toString(),
                    'district' => 'Antapani',
                    'subdistrict' => 'Antapani Wetan',
                    'village_code' => '3273141003',
                ]
            );
        }
    }
}
