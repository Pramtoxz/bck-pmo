<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pmov2.cart_items', function (Blueprint $table) {
            $table->dropColumn('product_id');
            
            $table->string('part_number', 50)->after('cart_id');
            $table->foreign('part_number')->references('part_number')->on('pmov2.part_images')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('pmov2.cart_items', function (Blueprint $table) {
            $table->dropForeign(['part_number']);
            $table->dropColumn('part_number');
            
            $table->foreignId('product_id')->after('cart_id')->constrained('pmov2.products')->onDelete('cascade');
        });
    }
};
