<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("UPDATE users SET role = 'applicant' WHERE role NOT IN ('admin', 'applicant') OR role IS NULL");
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'applicant') NOT NULL DEFAULT 'applicant'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY role VARCHAR(20) NOT NULL DEFAULT 'applicant'");
    }
};
