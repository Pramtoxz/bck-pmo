<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pmov2.campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('badge')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->timestamp('start_date');
            $table->timestamp('end_date');
            $table->string('status', 20)->default('active');
            $table->text('full_description')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->json('parts_included')->nullable();
            $table->json('rewards')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pmov2.campaigns');
    }
};
