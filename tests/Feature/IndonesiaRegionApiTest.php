<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IndonesiaRegionApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_user_can_get_indonesia_provinces_from_bps(): void
    {
        Http::fake([
            'sig.bps.go.id/rest-bridging/getwilayah*' => Http::response([
                [
                    'kode_bps' => '32',
                    'nama_bps' => 'JAWA BARAT',
                    'kode_dagri' => '32',
                    'nama_dagri' => 'JAWA BARAT',
                ],
            ]),
        ]);

        $response = $this->getJson('/api/indonesia/provinces');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Daftar provinsi berhasil diambil.')
            ->assertJsonPath('data.0.code', '32')
            ->assertJsonPath('data.0.name', 'JAWA BARAT')
            ->assertJsonPath('data.0.bps_code', '32')
            ->assertJsonPath('data.0.bps_name', 'JAWA BARAT')
            ->assertJsonPath('data.0.kemendagri_code', '32')
            ->assertJsonPath('data.0.kemendagri_name', 'JAWA BARAT');

        Http::assertSent(fn ($request): bool => $request->url() === 'https://sig.bps.go.id/rest-bridging/getwilayah?level=provinsi&parent=0');
    }

    public function test_user_can_get_regencies_by_province_code(): void
    {
        Http::fake([
            'sig.bps.go.id/rest-bridging/getwilayah*' => Http::response([
                [
                    'kode_bps' => '3204',
                    'nama_bps' => 'BANDUNG',
                    'kode_dagri' => '32.04',
                    'nama_dagri' => 'KAB. BANDUNG',
                ],
            ]),
        ]);

        $response = $this->getJson('/api/indonesia/regencies?province_code=32');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Daftar kabupaten/kota berhasil diambil.')
            ->assertJsonPath('data.0.code', '3204')
            ->assertJsonPath('data.0.name', 'BANDUNG')
            ->assertJsonPath('data.0.province_code', '32');

        Http::assertSent(fn ($request): bool => $request->url() === 'https://sig.bps.go.id/rest-bridging/getwilayah?level=kabupaten&parent=32');
    }

    public function test_user_can_get_districts_by_regency_code(): void
    {
        Http::fake([
            'sig.bps.go.id/rest-bridging/getwilayah*' => Http::response([
                [
                    'kode_bps' => '3204010',
                    'nama_bps' => 'SOREANG',
                    'kode_dagri' => '32.04.01',
                    'nama_dagri' => 'SOREANG',
                ],
            ]),
        ]);

        $response = $this->getJson('/api/indonesia/districts?regency_code=3204');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Daftar kecamatan berhasil diambil.')
            ->assertJsonPath('data.0.code', '3204010')
            ->assertJsonPath('data.0.name', 'SOREANG')
            ->assertJsonPath('data.0.regency_code', '3204');

        Http::assertSent(fn ($request): bool => $request->url() === 'https://sig.bps.go.id/rest-bridging/getwilayah?level=kecamatan&parent=3204');
    }

    public function test_user_can_get_villages_by_district_code(): void
    {
        Http::fake([
            'sig.bps.go.id/rest-bridging/getwilayah*' => Http::response([
                [
                    'kode_bps' => '3204010001',
                    'nama_bps' => 'SOREANG',
                    'kode_dagri' => '32.04.01.1001',
                    'nama_dagri' => 'SOREANG',
                ],
            ]),
        ]);

        $response = $this->getJson('/api/indonesia/villages?district_code=3204010');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Daftar desa/kelurahan berhasil diambil.')
            ->assertJsonPath('data.0.code', '3204010001')
            ->assertJsonPath('data.0.name', 'SOREANG')
            ->assertJsonPath('data.0.district_code', '3204010');

        Http::assertSent(fn ($request): bool => $request->url() === 'https://sig.bps.go.id/rest-bridging/getwilayah?level=desa&parent=3204010');
    }

    public function test_regencies_requires_valid_province_code(): void
    {
        $response = $this->getJson('/api/indonesia/regencies?province_code=3204');

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('province_code');
    }
}
