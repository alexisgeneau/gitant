<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bounty_contributions', function (Blueprint $table) {
            $table->string('stripe_checkout_session_id')->nullable()->after('commission_cents');
        });
    }

    public function down(): void
    {
        Schema::table('bounty_contributions', function (Blueprint $table) {
            $table->dropColumn('stripe_checkout_session_id');
        });
    }
};
