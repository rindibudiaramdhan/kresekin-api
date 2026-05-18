<?php

namespace Tests\Feature;

use App\Models\HousingArea;
use App\Models\User;
use App\Models\UserSessionToken;
use Database\Seeders\HousingAreaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HousingAreaApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_get_housing_areas_with_village_code(): void
    {
        HousingArea::query()->create([
            'name' => 'Mitra Dago Parahyangan',
            'code' => 'antapani-wetan-mitra-dago-parahyangan',
            'city' => 'Kota Bandung',
            'district' => 'Antapani',
            'subdistrict' => 'Antapani Wetan',
            'village_code' => '3273141003',
        ]);
        HousingArea::query()->create([
            'name' => 'Komplek Luar Antapani Wetan',
            'code' => 'komplek-luar-antapani-wetan',
            'city' => 'Kota Bandung',
            'district' => 'Antapani',
            'subdistrict' => 'Antapani Kulon',
            'village_code' => '3273141002',
        ]);

        $user = User::query()->create([
            'name' => 'Budi',
            'email' => null,
            'phone' => '+6281234567890',
            'type' => 'phone',
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        $plainTextToken = 'housing-area-token';

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/housing-areas');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Mitra Dago Parahyangan')
            ->assertJsonPath('data.0.code', 'antapani-wetan-mitra-dago-parahyangan')
            ->assertJsonPath('data.0.city', 'Kota Bandung')
            ->assertJsonPath('data.0.district', 'Antapani')
            ->assertJsonPath('data.0.subdistrict', 'Antapani Wetan')
            ->assertJsonPath('data.0.village_code', '3273141003');

        $this->assertCount(1, $response->json('data'));
    }

    public function test_housing_areas_requires_authentication(): void
    {
        $this->getJson('/api/housing-areas')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Tidak terautentikasi.');
    }

    public function test_seeded_antapani_wetan_housing_areas_are_returned_by_endpoint(): void
    {
        $this->seed(HousingAreaSeeder::class);

        $plainTextToken = $this->createToken();

        $response = $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/housing-areas')
            ->assertOk();

        $expectedNames = [
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

        $antapaniWetanAreas = collect($response->json('data'))
            ->where('village_code', '3273141003')
            ->values();

        $this->assertCount(36, $antapaniWetanAreas);
        $this->assertEqualsCanonicalizing($expectedNames, $antapaniWetanAreas->pluck('name')->all());
        $this->assertTrue($antapaniWetanAreas->every(
            fn (array $area): bool => $area['city'] === 'Kota Bandung'
                && $area['district'] === 'Antapani'
                && $area['subdistrict'] === 'Antapani Wetan'
        ));
    }

    private function createToken(): string
    {
        $user = User::query()->create([
            'name' => 'Budi',
            'email' => null,
            'phone' => '+6281234567890',
            'type' => 'phone',
            'password' => null,
            'otp_code' => null,
            'otp_sent_at' => null,
        ]);

        $plainTextToken = 'housing-area-token-'.str()->random(8);

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        return $plainTextToken;
    }
}
