<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number')->nullable()->unique();
            $table->string('type', 20)->default('invoice'); // invoice|cancellation
            $table->string('status', 20)->default('draft'); // draft|issued|cancelled
            $table->foreignId('cancels_invoice_id')->nullable()->constrained('invoices')->restrictOnDelete();
            // Team admins of this team create and issue the invoice; kept when the team is removed (retention).
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->date('issue_date')->nullable();
            $table->date('due_date')->nullable();
            $table->date('service_from')->nullable();
            $table->date('service_to')->nullable();
            $table->decimal('tax_rate', 5, 2)->default(19);
            $table->boolean('small_business')->default(false);
            $table->json('seller')->nullable(); // snapshot at issue time
            $table->json('buyer')->nullable();  // snapshot at issue time
            $table->bigInteger('net_cents')->default(0);
            $table->bigInteger('tax_cents')->default(0);
            $table->bigInteger('gross_cents')->default(0);
            $table->text('notes')->nullable();
            $table->string('xml_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'issue_date']);
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description', 500);
            $table->decimal('quantity', 12, 2);
            $table->string('unit_code', 3)->default('HUR'); // UN/ECE Rec 20: HUR hour, C62 piece
            $table->bigInteger('unit_price_cents');
            $table->bigInteger('net_cents');
            $table->timestamps();
        });

        Schema::table('time_entries', function (Blueprint $table) {
            // Removing a draft item releases its time for billing again.
            $table->foreign('invoice_item_id')->references('id')->on('invoice_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('time_entries', fn (Blueprint $table) => $table->dropForeign(['invoice_item_id']));
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
