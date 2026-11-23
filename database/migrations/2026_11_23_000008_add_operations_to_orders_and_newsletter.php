<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Staff who can open the admin area at /admin.
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('email');
        });

        Schema::table('orders', function (Blueprint $table) {
            // VAT included in the total, fixed when the order is placed (rate in basis points: 2000 = 20%).
            $table->unsignedSmallInteger('vat_rate')->nullable()->after('total');
            $table->unsignedInteger('vat_amount')->default(0)->after('vat_rate');

            // Refunds, cumulative, as reported by the payment provider.
            $table->unsignedInteger('amount_refunded')->default(0)->after('amount_paid');
            $table->timestamp('refunded_at')->nullable()->after('paid_at');

            // Fulfilment, tracked separately from payment.
            $table->string('fulfilment_status')->default('unfulfilled')->after('status');
            $table->timestamp('packed_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->string('carrier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('tracking_url', 500)->nullable();

            $table->index(['status', 'fulfilment_status']);
        });

        // Units of each line put back into stock (returns), so they can't be restocked twice.
        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedSmallInteger('restocked_quantity')->default(0)->after('quantity');
        });

        // Card disputes (chargebacks) reported by the payment provider.
        Schema::create('payment_disputes', function (Blueprint $table) {
            $table->string('id')->primary(); // e.g. Stripe's dp_...
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_reference')->nullable()->index();
            $table->unsignedInteger('amount');
            $table->string('currency', 3);
            $table->string('reason')->nullable();
            $table->string('status');
            $table->timestamp('evidence_due_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        // Double opt-in: an address only counts once its owner confirms it.
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->string('token', 64)->nullable()->unique()->after('email');
            $table->timestamp('confirmed_at')->nullable()->after('token');
            $table->timestamp('unsubscribed_at')->nullable()->after('confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->dropUnique(['token']);
            $table->dropColumn(['token', 'confirmed_at', 'unsubscribed_at']);
        });
        Schema::dropIfExists('payment_disputes');
        Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn('restocked_quantity'));
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status', 'fulfilment_status']);
            $table->dropColumn([
                'vat_rate', 'vat_amount', 'amount_refunded', 'refunded_at', 'fulfilment_status',
                'packed_at', 'shipped_at', 'carrier', 'tracking_number', 'tracking_url',
            ]);
        });
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_admin'));
    }
};
