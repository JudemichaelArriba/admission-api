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
        Schema::table('applicant_documents', function (Blueprint $table) {
            $table->string('disk', 30)->default('local')->after('file_path');
            $table->string('original_filename')->nullable()->after('disk');
            $table->string('mime_type', 120)->nullable()->after('original_filename');
            $table->unsignedBigInteger('file_size')->nullable()->after('mime_type');
            $table->string('sha256', 64)->nullable()->after('file_size');
            $table->string('scan_status', 20)->default('pending')->after('sha256');
            $table->index('document_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applicant_documents', function (Blueprint $table) {
            $table->dropIndex(['document_type']);
            $table->dropColumn([
                'disk',
                'original_filename',
                'mime_type',
                'file_size',
                'sha256',
                'scan_status',
            ]);
        });
    }
};
