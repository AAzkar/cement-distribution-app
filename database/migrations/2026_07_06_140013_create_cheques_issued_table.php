<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cheques_issued', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('payee_name')->nullable();
            $table->string('bank_name');
            $table->string('cheque_no');
            $table->decimal('amount', 14, 2);
            $table->date('issue_date');
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->enum('status', ['issued', 'cleared', 'bounced'])->default('issued');
            $table->date('cleared_date')->nullable();
            $table->string('bounced_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['bank_name', 'cheque_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cheques_issued');
    }
};
