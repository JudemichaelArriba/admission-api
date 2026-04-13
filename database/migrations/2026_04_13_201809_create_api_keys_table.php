<?php
// database/migrations/xxxx_create_api_keys_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('client_name');           // e.g. "enrollment-module"
            $table->string('key', 64)->unique();     // the secret
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->string('last_used_ip')->nullable();
            $table->timestamps();
            $table->softDeletes();                   // revoke without hard delete
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};