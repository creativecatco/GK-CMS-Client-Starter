<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('content')->nullable();
                $table->text('excerpt')->nullable();
                $table->string('featured_image')->nullable();
                $table->json('gallery')->nullable();
                $table->decimal('price', 10, 2)->nullable();
                $table->decimal('sale_price', 10, 2)->nullable();
                $table->string('sku')->nullable();
                $table->string('product_url')->nullable();
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

        // Product categories pivot
        if (!Schema::hasTable('product_category')) {
            Schema::create('product_category', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('category_id')->constrained()->cascadeOnDelete();
                $table->unique(['product_id', 'category_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_category');
        Schema::dropIfExists('products');
    }
};
