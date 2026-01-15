<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop foreign keys first
        Schema::table('pmov2.cart_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });
        
        Schema::table('pmov2.order_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });
        
        // Drop old products table
        Schema::dropIfExists('pmov2.products');
        
        // Create new part_images table
        Schema::create('pmov2.part_images', function (Blueprint $table) {
            $table->string('part_number', 50)->primary()->comment('FK to public.tblpart_id.kd_part');
            $table->string('image')->nullable()->comment('Image URL');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pmov2.part_images');
        
        // Recreate old products table
        Schema::create('pmov2.products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->string('category', 50)->nullable();
            $table->decimal('price', 15, 2);
            $table->string('image')->nullable();
            $table->integer('ref_part_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
};
