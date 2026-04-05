<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicant_documents', function (Blueprint $table) {

            $table->dropColumn(['file_path', 'disk']);
        });


        DB::statement('ALTER TABLE applicant_documents ADD file_content LONGBLOB AFTER document_type');
    }

    public function down(): void
    {
        Schema::table('applicant_documents', function (Blueprint $table) {
            $table->dropColumn('file_content');
            $table->string('file_path')->after('document_type');
            $table->string('disk', 30)->default('local')->after('file_path');
        });
    }
};
