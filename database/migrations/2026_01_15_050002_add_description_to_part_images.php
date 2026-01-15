<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pmov2.part_images', function (Blueprint $table) {
            $table->string('name')->nullable()->after('part_number')->comment('Override part name');
            $table->text('description')->nullable()->after('name')->comment('Part description');
        });
    }

    public function down(): void
    {
        Schema::table('pmov2.part_images', function (Blueprint $table) {
            $table->dropColumn(['name', 'description']);
        });
    }
};
