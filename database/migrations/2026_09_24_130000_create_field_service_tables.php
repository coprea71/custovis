<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technician_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('home_address')->nullable();
            $table->decimal('home_lat', 9, 6)->nullable();
            $table->decimal('home_lng', 9, 6)->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('location_tracking_consent')->default(false); // opt-in GPS log (DSGVO)
            $table->timestamps();
        });

        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('technician_skill', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technician_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('level')->default(1); // 1..5
            $table->timestamps();

            $table->unique(['technician_profile_id', 'skill_id'], 'technician_skill_unique');
        });

        Schema::create('technician_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technician_profile_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday'); // ISO 1 (Mo) .. 7 (So)
            $table->time('starts_at');
            $table->time('ends_at');
            $table->timestamps();
        });

        Schema::create('technician_absences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technician_profile_id')->constrained()->cascadeOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('reason')->default('vacation'); // vacation|sick|other
            $table->timestamps();
        });

        Schema::create('service_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technician_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind')->default('service'); // service|delivery
            $table->string('state');
            $table->dateTime('scheduled_start');
            $table->dateTime('scheduled_end');
            $table->string('address');
            $table->decimal('lat', 9, 6)->nullable();
            $table->decimal('lng', 9, 6)->nullable();
            $table->json('required_skill_ids')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['technician_profile_id', 'scheduled_start'], 'service_appointments_tech_start_index');
        });

        $this->createChecklistTables();
        $this->createProofTables();

        Schema::create('technician_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technician_profile_id')->constrained()->cascadeOnDelete();
            $table->decimal('lat', 9, 6);
            $table->decimal('lng', 9, 6);
            $table->timestamp('recorded_at')->index(); // retention pruning
            $table->timestamps();
        });

        // Offline queue operations already applied — makes PWA retries idempotent.
        Schema::create('field_sync_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technician_profile_id')->constrained()->cascadeOnDelete();
            $table->uuid('operation_uuid');
            $table->string('type');
            $table->string('result'); // applied|conflict|rejected
            $table->string('message')->nullable();
            $table->timestamps();

            $table->unique(['technician_profile_id', 'operation_uuid'], 'field_sync_ops_unique'); // explicit: MySQL caps names at 64 chars
        });
    }

    /**
     * appointment_id = null marks an admin template; filled rows are the
     * per-appointment copy that the technician ticks off.
     */
    private function createChecklistTables(): void
    {
        Schema::create('appointment_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->nullable()->constrained('service_appointments')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('appointment_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_id')->constrained('appointment_checklists')->cascadeOnDelete();
            $table->string('label');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('checked')->default(false);
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
        });
    }

    private function createProofTables(): void
    {
        Schema::create('appointment_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('service_appointments')->cascadeOnDelete();
            $table->string('context'); // checklist|delivery
            $table->string('signer_name');
            $table->string('disk');
            $table->string('path');
            $table->timestamp('signed_at');
            $table->timestamps();
        });

        Schema::create('appointment_parts_used', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('service_appointments')->cascadeOnDelete();
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit')->default('Stk');
            $table->timestamps();
        });

        Schema::create('appointment_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained('service_appointments')->cascadeOnDelete();
            $table->string('delivery_note_number');
            $table->text('items_text')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_email')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->foreignId('pdf_attachment_id')->nullable()->constrained('ticket_attachments')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'field_sync_operations', 'technician_locations', 'appointment_deliveries', 'appointment_parts_used', 'appointment_signatures',
            'appointment_checklist_items', 'appointment_checklists', 'service_appointments',
            'technician_absences', 'technician_shifts', 'technician_skill', 'skills', 'technician_profiles',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
