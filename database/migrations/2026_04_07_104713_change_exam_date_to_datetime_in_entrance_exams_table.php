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
        Schema::table('entrance_exams', function (Blueprint $table) {
            // This changes the existing column to a datetime type
            $table->dateTime('exam_date')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entrance_exams', function (Blueprint $table) {
            // This reverts it back to date if you roll back
            $table->date('exam_date')->change();
        });
    }
};
