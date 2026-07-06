<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cheques_received', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('sales_rep_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('handover_id')->nullable()->constrained('handovers')->nullOnDelete();
            $table->string('bank_name');
            $table->string('cheque_no');
            $table->decimal('amount', 14, 2);
            $table->date('received_date');
            $table->date('deposit_date')->nullable();
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->enum('status', ['received', 'deposited', 'cleared', 'returned'])->default('received');
            $table->string('returned_reason')->nullable();
            $table->date('returned_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['bank_name', 'cheque_no', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cheques_received');
    }
};
