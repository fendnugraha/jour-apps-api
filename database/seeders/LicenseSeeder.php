<?php

namespace Database\Seeders;

use App\Models\License;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LicenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        License::create([
            'client_name' => 'GEMILANG SUKSES MUAMALAH',
            'period_start' => now(),
            'period_end' => now()->addMonth(),
            'is_active' => true,
            'is_paid' => false,
            'last_checked_at' => now(),
        ]);
    }
}
