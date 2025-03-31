<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_project', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->decimal('hourly_rate', 10, 2);
            $table->timestamps();

            $table->unique(['user_id', 'project_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_project');
    }
}; 