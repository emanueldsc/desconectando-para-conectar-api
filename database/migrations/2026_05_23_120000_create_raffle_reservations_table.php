<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raffle_reservations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('raffle_id')->constrained('raffles')->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->enum('status', ['reserved', 'paid', 'cancelled'])->default('reserved');
            $table->timestamps();
            $table->unique(['raffle_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raffle_reservations');
    }
};
