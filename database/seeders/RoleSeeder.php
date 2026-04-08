<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name'        => Role::ADMIN,
                'label'       => 'Administrateur',
                'description' => 'Accès complet à toutes les fonctionnalités.',
            ],
            [
                'name'        => Role::TECHNICIEN,
                'label'       => 'Technicien',
                'description' => 'Gère les interventions et les tickets assignés.',
            ],
            [
                'name'        => Role::UTILISATEUR,
                'label'       => 'Utilisateur',
                'description' => 'Peut créer et suivre ses propres tickets.',
            ],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(['name' => $roleData['name']], $roleData);
        }

        $this->command->info('Rôles créés avec succès.');
    }
}
