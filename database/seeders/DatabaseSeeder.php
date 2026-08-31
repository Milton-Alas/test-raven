<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Usuario Administrador
    User::firstOrCreate(
        ['email' => 'admin@test.com'],
        [
            'name'     => 'Administrador',
            'password' => Hash::make('password'), 
            'role'     => 'admin',
        ]
    );

    // Usuario Reportador
    User::firstOrCreate(
        ['email' => 'reporter@test.com'],
        [
            'name'     => 'Reportador',
            'password' => Hash::make('password'),
            'role'     => 'reporter',
        ]
    );

        $this->call([
            RavenTestSeeder::class,
            PercentileTableSeeder::class,
            DiagnosticRangeSeeder::class,
            DiscrepancyPatternSeeder::class,
        ]);
    }
}
