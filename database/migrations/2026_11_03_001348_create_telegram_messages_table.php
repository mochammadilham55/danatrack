<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('telegram_id');
            $table->string('chat_id');
            $table->string('message_id');

            $table->text('message_text')->nullable();

            $table->enum('status', [
                'received',
                'processed',
                'failed'
            ])->default('received');

            $table->foreignId('transaction_id')
                ->nullable()
                ->constrained('transactions')
                ->nullOnDelete();

            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->unique(['chat_id', 'message_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_messages');
    }
};
