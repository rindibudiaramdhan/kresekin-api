<?php

namespace Database\Seeders;

use App\Models\HousingArea;
use Illuminate\Database\Seeder;

class HousingAreaSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            'Andir',
            'Antapani',
            'Arcamanik',
            'Astanaanyar',
            'Babakan Ciparay',
            'Bandung Kidul',
            'Bandung Kulon',
            'Bandung Wetan',
            'Batununggal',
            'Bojongloa Kaler',
            'Bojongloa Kidul',
            'Buahbatu',
            'Cibeunying Kaler',
            'Cibeunying Kidul',
            'Cibiru',
            'Cicendo',
            'Cidadap',
            'Cinambo',
            'Coblong',
            'Gedebage',
            'Kiaracondong',
            'Lengkong',
            'Mandalajati',
            'Panyileukan',
            'Rancasari',
            'Regol',
            'Sukajadi',
            'Sukasari',
            'Sumur Bandung',
            'Ujungberung',
        ];

        foreach ($areas as $area) {
            HousingArea::query()->updateOrCreate(
                ['name' => $area, 'city' => 'Kota Bandung'],
                [
                    'code' => str($area)->slug('-')->toString(),
                    'district' => $area,
                    'subdistrict' => null,
                ]
            );
        }
    }
}
