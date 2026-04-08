<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interventions', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // INT-2024-0001
            $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
            $table->foreignId('technician_id')->constrained('users')->onDelete('restrict');
            $table->text('description');
            $table->text('report')->nullable();
            $table->enum('status', ['planned', 'in_progress', 'completed', 'cancelled'])->default('planned');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->integer('duration_minutes')->nullable(); // Durée calculée
            $table->timestamps();
            $table->softDeletes();

            $table->index(['ticket_id', 'status']);
            $table->index('technician_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interventions');
    }
};
