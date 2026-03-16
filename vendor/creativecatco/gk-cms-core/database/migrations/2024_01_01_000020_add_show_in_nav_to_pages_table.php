<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pages') && !Schema::hasColumn('pages', 'show_in_nav')) {
            Schema::table('pages', function (Blueprint $table) {
                $table->boolean('show_in_nav')->default(true)->after('sort_order');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pages') && Schema::hasColumn('pages', 'show_in_nav')) {
            Schema::table('pages', function (Blueprint $table) {
                $table->dropColumn('show_in_nav');
            });
        }
    }
};
