<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }
            // Adjust status enum if needed (some DBs require raw SQL; keep simple if not enum)
            // If using MySQL enum, you might need to alter it; skipping destructive changes here.
        });
    }

    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
