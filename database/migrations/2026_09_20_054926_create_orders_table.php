<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->string('customer_name')->index();
            $table->string('customer_email')->index();
            $table->string('status')->index();
            $table->decimal('total', 12, 2);
            $table->timestamp('ordered_at')->index();
            $table->timestamps();

            $table->index(['status', 'ordered_at']);
            $table->index(['ordered_at', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
