<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raffles', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('description');
            $table->longText('full_description');
            $table->string('image');
            $table->json('gallery')->nullable();
            $table->decimal('goal', 12, 2);
            $table->decimal('current', 12, 2)->default(0);
            $table->enum('status', ['active', 'coming', 'finished'])->default('coming');
            $table->timestamp('draw_date');
            $table->string('category');
            $table->decimal('ticket_price', 10, 2);
            $table->unsignedInteger('tickets_available');
            $table->unsignedInteger('tickets_sold')->default(0);
            $table->foreignId('organization_id')->constrained('institutions')->cascadeOnDelete();
            $table->text('rules')->nullable();
            $table->json('numbers')->nullable();
            $table->json('winner_info')->nullable();
            $table->boolean('featured')->default(false);
            $table->string('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();
            $table->timestamps();

            $table->index(['status', 'draw_date']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raffles');
    }
};