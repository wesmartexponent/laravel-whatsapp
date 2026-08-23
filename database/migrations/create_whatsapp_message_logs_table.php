<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('amravati-whatsapp.logging.table', 'whatsapp_message_logs');

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->nullable()->index();
            $table->string('phone_number');
            $table->string('type'); // text, image, template, etc.
            $table->string('template_name')->nullable();
            $table->json('payload');
            $table->json('response')->nullable();
            $table->string('status')->default('pending');
            $table->boolean('success')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['phone_number', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        $tableName = config('amravati-whatsapp.logging.table', 'whatsapp_message_logs');
        Schema::dropIfExists($tableName);
    }
};
