<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('amravati-whatsapp.templates.table', 'whatsapp_templates');

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('language', 10)->default('en_US');
            $table->string('category')->nullable();
            $table->string('status')->default('UNKNOWN');
            $table->json('components')->nullable();
            $table->string('header_type')->nullable();
            $table->unsignedTinyInteger('body_params_count')->default(0);
            $table->unsignedTinyInteger('header_params_count')->default(0);
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->unique(['name', 'language']);
            $table->index('status');
            $table->index('category');
        });
    }

    public function down(): void
    {
        $tableName = config('amravati-whatsapp.templates.table', 'whatsapp_templates');
        Schema::dropIfExists($tableName);
    }
};
