<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('reference')->unique()->nullable(); // Référence constructeur
            $table->string('serial_number')->unique()->nullable();
            $table->enum('category', [
                'ordinateur', 'imprimante', 'serveur',
                'reseau', 'peripherique', 'consommable', 'autre'
            ])->default('autre');
            $table->text('description')->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('quantity_min')->default(1); // Seuil d'alerte
            $table->enum('status', ['disponible', 'affecte', 'maintenance', 'hors_service'])->default('disponible');
            $table->string('location')->nullable(); // Emplacement physique
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 10, 2)->nullable();
            $table->date('warranty_end')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category', 'status']);
            $table->index('quantity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
