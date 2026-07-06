<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_rep_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained('zones')->nullOnDelete();
            $table->date('entry_date');
            $table->decimal('sales_amount', 14, 2)->default(0);
            $table->unsignedInteger('invoice_count')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['sales_rep_id', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_entries');
    }
};
