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
        Schema::create('pmov2.carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('pmov2.shops')->onDelete('cascade');
            $table->string('status', 20)->default('active');
            $table->timestamps();
            
            $table->unique(['shop_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pmov2.carts');
    }
};
