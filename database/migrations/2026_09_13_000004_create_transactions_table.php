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
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recurring_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->enum('type', ['income', 'expense']);
            $table->date('occurred_on');
            $table->boolean('is_adjustment')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'occurred_on']);
            $table->index(['recurring_item_id', 'occurred_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
