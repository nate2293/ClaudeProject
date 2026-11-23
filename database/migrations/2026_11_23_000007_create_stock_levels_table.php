<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Stock per variant (product × colour × size). `available` = on_hand - reserved:
        // units held by orders whose checkout is open or whose payment is in flight.
        Schema::create('stock_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('colour');
            $table->string('size');
            $table->integer('on_hand')->default(0); // Can dip below zero only if an admin lowers it under paid demand.
            $table->integer('reserved')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'colour', 'size']);
        });

        // Which stock level an order line reserved from (null for lines placed before stock tracking).
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('stock_level_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stock_level_id');
        });
        Schema::dropIfExists('stock_levels');
    }
};
