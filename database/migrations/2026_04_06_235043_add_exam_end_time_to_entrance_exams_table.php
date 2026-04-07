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
           
            $table->dateTime('exam_end_time')->after('exam_date');
        });
    }

    public function down(): void
    {
        Schema::table('entrance_exams', function (Blueprint $table) {
            $table->dropColumn('exam_end_time');
        });
    }
};
