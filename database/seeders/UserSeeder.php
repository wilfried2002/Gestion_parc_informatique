<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', Role::ADMIN)->firstOrFail();

        // Seul l'administrateur est créé — les autres comptes sont gérés par l'admin
        User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'role_id'    => $adminRole->id,
                'name'       => 'Administrateur',
                'password'   => Hash::make('Admin1234@'),
                'phone'      => null,
                'department' => 'Direction IT',
                'is_active'  => true,
            ]
        );

        $this->command->info('Administrateur créé : admin@gmail.com / Admin1234@');
    }
}
