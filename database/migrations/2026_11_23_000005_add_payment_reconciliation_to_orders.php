<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // What the payment provider actually charged, kept separate from the
            // order total we calculated so the two can be compared.
            $table->unsignedInteger('amount_paid')->nullable()->after('total');
            // Set when an order needs a person to look at it (e.g. amount mismatch).
            $table->string('review_reason')->nullable()->after('paid_at');

            // Reconciliation and pruning both select by status and age.
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropColumn(['amount_paid', 'review_reason']);
        });
    }
};
