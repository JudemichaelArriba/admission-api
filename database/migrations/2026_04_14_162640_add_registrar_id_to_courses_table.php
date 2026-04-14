<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {

        
            $table->unsignedBigInteger('registrar_id')
                ->nullable()
                ->after('id')
                ->index();

            
            $table->unique('registrar_id');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {

            $table->dropUnique(['registrar_id']);
            $table->dropColumn('registrar_id');
        });
    }
};