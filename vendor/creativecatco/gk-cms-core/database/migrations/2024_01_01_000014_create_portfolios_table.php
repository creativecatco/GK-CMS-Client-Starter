<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('portfolios')) {
            Schema::create('portfolios', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('content')->nullable();
                $table->text('excerpt')->nullable();
                $table->string('featured_image')->nullable();
                $table->json('gallery')->nullable();
                $table->string('client')->nullable();
                $table->string('project_url')->nullable();
                $table->date('project_date')->nullable();
                $table->string('status')->default('draft'); // draft, published
                $table->timestamp('published_at')->nullable();
                $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('seo_title')->nullable();
                $table->text('seo_description')->nullable();
                $table->string('og_image')->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // Portfolio categories pivot
        if (!Schema::hasTable('portfolio_category')) {
            Schema::create('portfolio_category', function (Blueprint $table) {
                $table->id();
                $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
                $table->foreignId('category_id')->constrained()->cascadeOnDelete();
                $table->unique(['portfolio_id', 'category_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_category');
        Schema::dropIfExists('portfolios');
    }
};
