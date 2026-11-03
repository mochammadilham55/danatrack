<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('category_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->dateTime('transaction_date');
            $table->enum('type', ['expense', 'income']);
            $table->decimal('amount', 15, 2);

            $table->string('description');
            $table->string('merchant')->nullable();

            $table->enum('source', [
                'email',
                'website',
                'telegram'
            ]);

            $table->enum('status', [
                'pending',
                'approved'
            ])->default('approved');

            $table->string('reference_number')->nullable();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'transaction_date']);
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
