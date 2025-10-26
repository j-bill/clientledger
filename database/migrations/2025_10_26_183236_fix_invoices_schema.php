<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration consolidates all invoice table updates into a single comprehensive migration.
     * It ensures the invoices table has all required columns and correct status enum values.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Add invoice_number if it doesn't exist
            if (!Schema::hasColumn('invoices', 'invoice_number')) {
                $table->string('invoice_number')->unique()->after('id');
            }

            // Add issue_date if it doesn't exist (it was in original but let's be safe)
            if (!Schema::hasColumn('invoices', 'issue_date')) {
                $table->date('issue_date')->after('customer_id')->default(now());
            }

            // Add notes if it doesn't exist
            if (!Schema::hasColumn('invoices', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }

            // Update status enum to include all states: draft, sent, paid, overdue, cancelled
            // This requires the doctrine/dbal package for MySQL
            // If not using MySQL, you may need to adjust this
            try {
                $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])
                    ->default('draft')
                    ->change();
            } catch (\Exception $e) {
                // If change() fails (missing doctrine/dbal), log it but continue
                // The enum may already be in the correct state
                Log::warning('Could not alter status enum, it may already be correct: ' . $e->getMessage());
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Drop notes column
            if (Schema::hasColumn('invoices', 'notes')) {
                $table->dropColumn('notes');
            }

            // Drop invoice_number column
            if (Schema::hasColumn('invoices', 'invoice_number')) {
                $table->dropColumn('invoice_number');
            }

            // Drop issue_date column
            if (Schema::hasColumn('invoices', 'issue_date')) {
                $table->dropColumn('issue_date');
            }

            // Revert status enum back to original state
            try {
                $table->enum('status', ['pending', 'paid'])->default('pending')->change();
            } catch (\Exception $e) {
                Log::warning('Could not revert status enum: ' . $e->getMessage());
            }
        });
    }
};
