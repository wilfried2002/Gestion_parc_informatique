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
        $adminRole       = Role::where('name', Role::ADMIN)->firstOrFail();
        $techRole        = Role::where('name', Role::TECHNICIEN)->firstOrFail();
        $utilisateurRole = Role::where('name', Role::UTILISATEUR)->firstOrFail();

        // ── Administrateur principal ────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'role_id'    => $adminRole->id,
                'name'       => 'Administrateur',
                'password'   => Hash::make('Admin1234@'),
                'phone'      => '+225 07 00 00 00',
                'department' => 'Direction IT',
                'is_active'  => true,
            ]
        );

        // ── Administrateur système (legacy) ─────────────────────────────────
        User::firstOrCreate(
            ['email' => 'admin@gestion-it.local'],
            [
                'role_id'    => $adminRole->id,
                'name'       => 'Administrateur Système',
                'password'   => Hash::make('Admin@123!'),
                'phone'      => '+33 1 23 45 67 89',
                'department' => 'DSI',
                'is_active'  => true,
            ]
        );

        // ── Techniciens ─────────────────────────────────────────────────────
        $technicians = [
            ['name' => 'Jean Dupont',   'email' => 'jean.dupont@gestion-it.local',   'phone' => '+33 6 11 22 33 44'],
            ['name' => 'Marie Martin',  'email' => 'marie.martin@gestion-it.local',  'phone' => '+33 6 55 66 77 88'],
            ['name' => 'Pierre Bernard','email' => 'pierre.bernard@gestion-it.local','phone' => '+33 6 99 88 77 66'],
        ];

        foreach ($technicians as $techData) {
            User::firstOrCreate(
                ['email' => $techData['email']],
                array_merge($techData, [
                    'role_id'    => $techRole->id,
                    'password'   => Hash::make('Tech@123!'),
                    'department' => 'Support IT',
                    'is_active'  => true,
                ])
            );
        }

        // ── Utilisateurs ────────────────────────────────────────────────────
        $utilisateurs = [
            ['name' => 'Alice Durand',   'email' => 'alice.durand@gestion-it.local',   'department' => 'Comptabilité'],
            ['name' => 'Bob Leroy',      'email' => 'bob.leroy@gestion-it.local',      'department' => 'RH'],
            ['name' => 'Claire Moreau',  'email' => 'claire.moreau@gestion-it.local',  'department' => 'Commercial'],
        ];

        foreach ($utilisateurs as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                array_merge($userData, [
                    'role_id'   => $utilisateurRole->id,
                    'password'  => Hash::make('User@123!'),
                    'is_active' => true,
                ])
            );
        }

        $this->command->info('Utilisateurs créés avec succès.');
        $this->command->line('  Admin :     admin@gestion-it.local     / Admin@123!');
        $this->command->line('  Technicien: jean.dupont@gestion-it.local / Tech@123!');
        $this->command->line('  Utilisateur:alice.durand@gestion-it.local / User@123!');
    }
}
