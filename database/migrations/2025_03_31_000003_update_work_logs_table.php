<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // First add the columns without foreign key constraint
        Schema::table('work_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->after('project_id')->nullable();
            $table->decimal('user_hourly_rate', 10, 2)->after('hourly_rate')->nullable();
        });

        // Get the first admin user or create one if none exists
        $adminUser = DB::table('users')->where('role', 'admin')->first();
        
        if (!$adminUser) {
            $adminUser = DB::table('users')->first();
        }

        if ($adminUser) {
            // Update existing records with the admin user and their hourly rate
            DB::table('work_logs')->update([
                'user_id' => $adminUser->id,
                'user_hourly_rate' => $adminUser->hourly_rate ?? 0
            ]);
        }

        // Now add the foreign key constraint
        Schema::table('work_logs', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->after('project_id')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('work_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'user_hourly_rate']);
        });
    }
}; 