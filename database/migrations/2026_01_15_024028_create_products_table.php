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
        Schema::create('pmov2.products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Part number');
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->string('category', 50)->nullable();
            $table->decimal('price', 15, 2);
            $table->string('image')->nullable();
            $table->integer('ref_part_id')->nullable()->comment('FK to data_part.tblpart_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('code');
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pmov2.products');
    }
};
