<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('diet_plans', function (Blueprint $table) {
            $table->string('review_status')->default('pending_review')->after('status');
            $table->foreignId('reviewed_by')->nullable()->after('review_status')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('review_notes')->nullable()->after('reviewed_at');
        });

        DB::table('diet_plans')->where('status', 'active')->update(['review_status' => 'approved']);
    }

    public function down(): void
    {
        Schema::table('diet_plans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['review_status', 'reviewed_at', 'review_notes']);
        });
    }
};
