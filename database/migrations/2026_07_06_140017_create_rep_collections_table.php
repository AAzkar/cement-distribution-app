<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rep_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_rep_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained('zones')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->date('entry_date');
            $table->enum('mode', ['cash', 'cheque', 'bank_transfer']);
            $table->decimal('amount', 14, 2);
            $table->string('reference')->nullable();
            $table->foreignId('cheque_received_id')->nullable()->constrained('cheques_received')->nullOnDelete();
            $table->foreignId('handover_id')->nullable()->constrained('handovers')->nullOnDelete();
            $table->foreignId('cashbook_entry_id')->nullable()->constrained('cashbook_entries')->nullOnDelete();
            $table->enum('status', ['pending', 'handed_over', 'confirmed'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rep_collections');
    }
};
