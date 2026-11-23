<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Google's stable account ID ("sub"): matched first, so a changed Gmail address still signs in.
            $table->string('google_id')->nullable()->unique()->after('email');
            $table->string('avatar_url', 500)->nullable()->after('google_id');
            // Sessions started before this moment are signed out (see EnsureSessionIsCurrent).
            $table->timestamp('sessions_revoked_at')->nullable()->after('remember_token');
            // Customers who only use Google have no password.
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->dropColumn(['google_id', 'avatar_url', 'sessions_revoked_at']);
        });
    }
};
