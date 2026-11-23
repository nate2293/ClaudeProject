<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The staff admin panel was removed, so accounts no longer have an admin flag.
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_admin'));
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->boolean('is_admin')->default(false)->after('email'));
    }
};
