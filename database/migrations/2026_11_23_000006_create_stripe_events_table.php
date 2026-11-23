<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per Stripe event delivery we accepted: makes webhook handling
        // idempotent per event, lets processing happen on a queue, and keeps an
        // audit trail of what Stripe told us (and when) for refunds and disputes.
        Schema::create('stripe_events', function (Blueprint $table) {
            $table->string('id')->primary(); // Stripe's event ID, e.g. evt_1Q...
            $table->string('type');
            $table->string('object_id')->nullable()->index(); // e.g. the checkout session ID
            $table->json('payload');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['processed_at', 'created_at']);
        });

        // PostgreSQL doesn't index foreign keys automatically (MySQL does).
        Schema::table('order_items', fn (Blueprint $table) => $table->index('order_id'));
        Schema::table('orders', fn (Blueprint $table) => $table->index('user_id'));
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropIndex(['user_id']));
        Schema::table('order_items', fn (Blueprint $table) => $table->dropIndex(['order_id']));
        Schema::dropIfExists('stripe_events');
    }
};
