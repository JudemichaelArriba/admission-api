<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create the new normalized schedules table
        Schema::create('exam_schedules', function (Blueprint $table) {
            $table->id();
            $table->dateTime('exam_date');
            $table->dateTime('exam_end_time');
            $table->string('room');
            $table->timestamps();
        });

        // 2. Modify the existing entrance_exams table
        Schema::table('entrance_exams', function (Blueprint $table) {
            // Add the foreign key to the new schedule table
            $table->unsignedBigInteger('exam_schedule_id')->after('applicant_id')->nullable();
            $table->foreign('exam_schedule_id')->references('id')->on('exam_schedules')->onDelete('cascade');
            
            // Drop the redundant columns
            $table->dropColumn(['exam_date', 'exam_end_time', 'room']);
        });
    }

    public function down(): void
    {
        Schema::table('entrance_exams', function (Blueprint $table) {
            $table->dateTime('exam_date')->nullable();
            $table->dateTime('exam_end_time')->nullable();
            $table->string('room')->nullable();
            $table->dropForeign(['exam_schedule_id']);
            $table->dropColumn('exam_schedule_id');
        });

        Schema::dropIfExists('exam_schedules');
    }
};