<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Add issue_date if it doesn't exist
            if (!Schema::hasColumn('invoices', 'issue_date')) {
                $table->date('issue_date')->after('customer_id')->default(now());
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'issue_date')) {
                $table->dropColumn('issue_date');
            }
        });
    }
};
