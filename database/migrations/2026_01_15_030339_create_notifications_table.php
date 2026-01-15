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
        Schema::create('pmov2.notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('pmov2.shops')->onDelete('cascade');
            $table->string('title');
            $table->text('message');
            $table->string('type', 20)->default('info');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
            
            $table->index('shop_id');
            $table->index('is_read');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pmov2.notifications');
    }
};
