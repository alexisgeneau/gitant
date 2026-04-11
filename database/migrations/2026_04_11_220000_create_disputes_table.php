<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disputes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('bounty_id');
            $table->unsignedBigInteger('opened_by_user_id');

            // Type and description
            $table->string('type'); // unjustified_rejection | non_conforming_pr | ambiguous_specs | timing | other
            $table->text('opener_summary');
            $table->json('opener_evidence')->nullable(); // max 5 URLs
            $table->string('opener_demand'); // full_payment | full_refund | split_75_25 | split_50_50 | split_25_75

            // Respondent reply
            $table->text('respondent_position')->nullable();
            $table->json('respondent_evidence')->nullable();
            $table->timestamp('respondent_replied_at')->nullable();

            // Lifecycle
            $table->string('status')->default('open'); // open | awaiting_response | mediation | arbitration | resolved
            $table->string('resolution')->nullable(); // paid_full | refunded_full | split_75_25 | split_50_50 | split_25_75 | mutual
            $table->unsignedBigInteger('resolved_by_user_id')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('response_deadline_at')->nullable(); // 5 business days from opening
            $table->timestamps();

            $table->foreign('bounty_id')->references('id')->on('bounties')->cascadeOnDelete();
            $table->foreign('opened_by_user_id')->references('id')->on('users');
            $table->foreign('resolved_by_user_id')->references('id')->on('users')->nullOnDelete();

            $table->index('status');
            $table->index('bounty_id');
            $table->index('opened_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disputes');
    }
};
