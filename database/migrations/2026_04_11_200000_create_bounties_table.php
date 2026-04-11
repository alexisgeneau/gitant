<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bounties', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('issue_url');
            $table->string('issue_platform'); // github | gitlab
            $table->string('issue_repo_owner');
            $table->string('issue_repo_name');
            $table->unsignedBigInteger('issue_number');
            $table->string('issue_title');
            $table->text('issue_description')->nullable();
            $table->json('issue_labels')->nullable();
            $table->string('issue_language')->nullable();
            $table->string('status')->default('open'); // open, claimed, in_review, completed, disputed, expired, cancelled
            $table->unsignedBigInteger('total_amount_cents')->default(0);
            $table->unsignedBigInteger('claimed_by_user_id')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('claim_expires_at')->nullable();
            $table->string('linked_pr_url')->nullable();
            $table->string('linked_pr_platform')->nullable();
            $table->unsignedBigInteger('linked_pr_number')->nullable();
            $table->timestamp('pr_submitted_at')->nullable();
            $table->timestamp('auto_validate_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('public_message')->nullable();
            $table->timestamps();

            $table->unique(['issue_platform', 'issue_repo_owner', 'issue_repo_name', 'issue_number']);
            $table->foreign('claimed_by_user_id')->references('id')->on('users')->nullOnDelete();

            $table->index('status');
            $table->index('issue_language');
            $table->index('total_amount_cents');
            $table->index('created_at');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bounties');
    }
};
