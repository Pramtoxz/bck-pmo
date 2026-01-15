<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pmov2.carts', function (Blueprint $table) {
            $table->dropForeign(['shop_id']);
            $table->dropUnique(['shop_id', 'status']);
            $table->renameColumn('shop_id', 'user_id');
        });
        
        Schema::table('pmov2.carts', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('pmov2.carts', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id', 'status']);
            $table->renameColumn('user_id', 'shop_id');
        });
        
        Schema::table('pmov2.carts', function (Blueprint $table) {
            $table->foreign('shop_id')->references('id')->on('pmov2.shops')->onDelete('cascade');
            $table->unique(['shop_id', 'status']);
        });
    }
};
