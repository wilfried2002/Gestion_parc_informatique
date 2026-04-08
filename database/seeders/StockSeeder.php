<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class StockSeeder extends Seeder
{
    public function run(): void
    {
        // Le stock est géré entièrement par l'administrateur via l'interface
        $this->command->info('Aucun stock pré-rempli — à créer via l\'interface administrateur.');
    }
}
