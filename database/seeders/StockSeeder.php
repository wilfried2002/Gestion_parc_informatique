<?php

namespace Database\Seeders;

use App\Models\Stock;
use Illuminate\Database\Seeder;

class StockSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'name'          => 'Dell OptiPlex 7090',
                'reference'     => 'DELL-OPT-7090',
                'category'      => 'ordinateur',
                'description'   => 'PC bureau Core i7 16GB RAM 512GB SSD',
                'quantity'      => 15,
                'quantity_min'  => 3,
                'status'        => 'disponible',
                'location'      => 'Salle serveurs - Armoire A',
                'brand'         => 'Dell',
                'model'         => 'OptiPlex 7090',
                'purchase_price'=> 899.00,
                'warranty_end'  => now()->addYears(2)->toDateString(),
            ],
            [
                'name'          => 'HP LaserJet Pro M404dn',
                'reference'     => 'HP-LJ-M404DN',
                'category'      => 'imprimante',
                'description'   => 'Imprimante laser monochrome réseau',
                'quantity'      => 5,
                'quantity_min'  => 1,
                'status'        => 'disponible',
                'location'      => 'Bureau 2ème étage',
                'brand'         => 'HP',
                'model'         => 'LaserJet Pro M404dn',
                'purchase_price'=> 349.00,
            ],
            [
                'name'          => 'Cisco Switch 48 ports',
                'reference'     => 'CISCO-SG350-48',
                'category'      => 'reseau',
                'description'   => 'Switch manageable 48 ports Gigabit',
                'quantity'      => 3,
                'quantity_min'  => 1,
                'status'        => 'disponible',
                'location'      => 'Baie réseau - Salle serveurs',
                'brand'         => 'Cisco',
                'model'         => 'SG350-48',
                'purchase_price'=> 1200.00,
            ],
            [
                'name'          => 'Cartouche Toner HP CF258A',
                'reference'     => 'HP-CF258A',
                'category'      => 'consommable',
                'description'   => 'Toner compatible HP LaserJet',
                'quantity'      => 2,
                'quantity_min'  => 5,
                'status'        => 'disponible',
                'location'      => 'Armoire fournitures',
                'brand'         => 'HP',
                'model'         => 'CF258A',
                'purchase_price'=> 45.00,
            ],
        ];

        foreach ($items as $item) {
            Stock::firstOrCreate(['reference' => $item['reference']], $item);
        }

        $this->command->info('Stock initial créé avec succès.');
    }
}
