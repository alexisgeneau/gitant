<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bounty_contributions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('bounty_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('amount_cents');
            $table->unsignedBigInteger('commission_cents');
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_charge_id')->nullable();
            $table->string('status')->default('pending'); // pending, paid, refunded
            $table->timestamps();

            $table->foreign('bounty_id')->references('id')->on('bounties')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->index('bounty_id');
            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bounty_contributions');
    }
};
