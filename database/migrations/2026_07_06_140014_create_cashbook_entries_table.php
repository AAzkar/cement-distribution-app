<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashbook_entries', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_no')->unique();
            $table->date('entry_date');
            $table->enum('direction', ['inflow', 'outflow']);
            $table->enum('subtype', [
                'cash', 'bank_transfer', 'cheque_received', 'sales_rep_collection',
                'expense', 'supplier_payment', 'cheque_issued',
            ]);
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained('zones')->nullOnDelete();
            $table->decimal('amount', 14, 2);
            $table->foreignId('payment_mode_id')->constrained('payment_modes');
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('expense_category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('sales_rep_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->foreignId('cheque_received_id')->nullable()->constrained('cheques_received')->nullOnDelete();
            $table->foreignId('cheque_issued_id')->nullable()->constrained('cheques_issued')->nullOnDelete();
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'locked'])->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->index(['entry_date', 'warehouse_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashbook_entries');
    }
};
