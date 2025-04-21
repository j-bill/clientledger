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
            // Add invoice_number after id
            $table->string('invoice_number')->unique()->after('id');

            // Modify status enum - Using change() requires doctrine/dbal
            // Consider dropping and recreating if DBAL is not installed or for simplicity
            // $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])->default('draft')->change();
            // Alternative: Raw SQL or separate migrations if needed, for now, let's assume DBAL is available or will be installed.
             $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])->default('draft')->change();


            // Drop issue_date
            $table->dropColumn('issue_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Add issue_date back - default to current date or handle appropriately
            $table->date('issue_date')->after('customer_id')->default(now());

            // Revert status enum change
            // $table->enum('status', ['pending', 'paid'])->default('pending')->change();
             $table->enum('status', ['pending', 'paid'])->default('pending')->change();


            // Drop invoice_number
            $table->dropColumn('invoice_number');
        });
    }
};
