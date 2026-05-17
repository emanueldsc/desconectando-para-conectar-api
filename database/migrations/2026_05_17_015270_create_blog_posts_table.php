<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content');
            $table->text('excerpt')->nullable();
            $table->string('featured_image');
            $table->string('image_alt')->nullable();
            $table->string('eyebrow')->nullable();
            $table->string('category');
            $table->json('tags')->nullable();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();
            $table->unsignedInteger('views')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['category', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};