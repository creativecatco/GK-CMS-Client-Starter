<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content')->nullable();
            $table->string('featured_image')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->integer('sort_order')->default(0);
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('og_image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
