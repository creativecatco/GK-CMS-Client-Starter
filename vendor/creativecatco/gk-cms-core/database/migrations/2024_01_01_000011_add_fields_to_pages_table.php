<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            // JSON column to store the editable field values (key => value pairs)
            $table->json('fields')->nullable()->after('blocks');

            // JSON column to store field definitions (schema for the admin form)
            // Each field: { key, label, type, group, options, placeholder, order }
            $table->json('field_definitions')->nullable()->after('fields');

            // Stores the raw Blade template content written by AI
            // When set, this is used instead of the template file on disk
            $table->longText('custom_template')->nullable()->after('field_definitions');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['fields', 'field_definitions', 'custom_template']);
        });
    }
};
