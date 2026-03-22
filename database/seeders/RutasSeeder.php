<?php

namespace Database\Seeders;

use App\Models\Ruta;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RutasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Ruta::factory()->count(30)->create();
    }
}
