<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop password-based columns (OAuth-only auth)
            $table->dropColumn(['name', 'password', 'email_verified_at', 'remember_token']);
        });

        Schema::table('users', function (Blueprint $table) {
            // OAuth identity
            $table->string('username')->unique()->after('id');
            $table->string('github_id')->nullable()->unique()->after('username');
            $table->string('gitlab_id')->nullable()->unique()->after('github_id');
            $table->string('avatar_url')->nullable()->after('gitlab_id');

            // Stripe (in addition to Cashier's stripe_id column)
            $table->string('stripe_connect_account_id')->nullable()->after('stripe_id');
            $table->string('stripe_connect_status')->nullable()->after('stripe_connect_account_id');

            // Preferences
            $table->string('preferred_locale', 5)->default('en')->after('stripe_connect_status');
            $table->boolean('is_admin')->default(false)->after('preferred_locale');

            // Soft deletes
            $table->softDeletes();
        });

        // Make email nullable (some GitLab accounts may not expose it)
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'username',
                'github_id',
                'gitlab_id',
                'avatar_url',
                'stripe_connect_account_id',
                'stripe_connect_status',
                'preferred_locale',
                'is_admin',
            ]);
            $table->string('name')->nullable();
            $table->string('password')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->string('email')->nullable(false)->change();
        });
    }
};
