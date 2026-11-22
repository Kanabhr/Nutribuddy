<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Issue 17: let admins reply to product reviews.
 *
 * Table: product_reviews. New nullable columns only, so existing rows are untouched.
 * Rollback drops the three columns and therefore any replies saved after deployment.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->text('admin_reply')->nullable()->after('comment');
            $table->timestamp('admin_replied_at')->nullable()->after('admin_reply');
            $table->foreignId('admin_replied_by')
                ->nullable()
                ->after('admin_replied_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->dropConstrainedForeignId('admin_replied_by');
            $table->dropColumn(['admin_reply', 'admin_replied_at']);
        });
    }
};
