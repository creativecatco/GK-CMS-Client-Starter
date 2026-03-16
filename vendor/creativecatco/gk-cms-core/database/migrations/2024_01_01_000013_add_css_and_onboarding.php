<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add page-specific CSS column to pages table
        if (Schema::hasTable('pages') && !Schema::hasColumn('pages', 'custom_css')) {
            Schema::table('pages', function (Blueprint $table) {
                $table->longText('custom_css')->nullable()->after('fields');
            });
        }

        // Add header/footer template fields to pages
        if (Schema::hasTable('pages') && !Schema::hasColumn('pages', 'page_type')) {
            Schema::table('pages', function (Blueprint $table) {
                $table->string('page_type')->default('page')->after('slug');
                // page_type: 'page', 'header', 'footer' — allows header/footer to be stored as special pages
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pages') && Schema::hasColumn('pages', 'custom_css')) {
            Schema::table('pages', function (Blueprint $table) {
                $table->dropColumn('custom_css');
            });
        }

        if (Schema::hasTable('pages') && Schema::hasColumn('pages', 'page_type')) {
            Schema::table('pages', function (Blueprint $table) {
                $table->dropColumn('page_type');
            });
        }
    }
};
