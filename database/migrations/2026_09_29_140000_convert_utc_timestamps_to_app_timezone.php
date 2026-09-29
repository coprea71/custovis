<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Up to 0.7.3 the app ran in UTC: now()-based timestamps are real UTC, while
 * appointment and change windows were typed in as local wall-clock time.
 * Moving to APP_TIMEZONE therefore converts only the former, per value so
 * daylight saving time is respected.
 */
return new class extends Migration
{
    // Frozen at this release: tables created later never held UTC values.
    private const TABLES = [
        'ai_budgets', 'ai_settings', 'ai_usage_logs', 'api_client_kb_categories', 'api_clients',
        'appointment_checklist_items', 'appointment_checklists', 'appointment_deliveries',
        'appointment_parts_used', 'appointment_signatures', 'audit_logs', 'business_hours',
        'cab_approvals', 'canned_responses', 'chat_channels', 'chat_direct_thread_participants',
        'chat_direct_threads', 'chat_messages', 'chat_read_states', 'cmdb_ci_relations',
        'cmdb_configuration_items', 'customers', 'dashboard_snapshots', 'erp_connections',
        'field_sync_operations', 'git_issue_connections', 'invoice_items', 'invoices',
        'knowledge_base_article_feedback', 'knowledge_base_article_versions',
        'knowledge_base_articles', 'knowledge_base_categories', 'mailboxes', 'module_role',
        'module_user', 'modules', 'passkeys', 'permissions', 'personal_access_tokens', 'roles',
        'service_appointments', 'service_catalog_items', 'settings', 'skills', 'sla_policies',
        'spam_rules', 'team_mail_layouts', 'team_user', 'teams', 'technician_absences',
        'technician_locations', 'technician_profiles', 'technician_shifts', 'technician_skill',
        'themes', 'ticket_attachments', 'ticket_changes', 'ticket_configuration_items',
        'ticket_embeddings', 'ticket_incidents', 'ticket_messages', 'ticket_problems',
        'ticket_read_states', 'ticket_service_requests', 'tickets', 'time_entries', 'users',
        'whatsapp_accounts', 'whatsapp_templates',
    ];

    private const WALL_CLOCK_COLUMNS = [
        'service_appointments' => ['scheduled_start', 'scheduled_end'],
        'ticket_changes' => ['planned_start', 'planned_end'],
    ];

    public function up(): void
    {
        $this->convert('UTC', config('app.timezone'));
    }

    public function down(): void
    {
        $this->convert(config('app.timezone'), 'UTC');
    }

    private function convert(string $from, string $to): void
    {
        if ($from === $to) {
            return;
        }

        foreach (self::TABLES as $table) {
            $columns = $this->timestampColumns($table);

            if ($columns !== []) {
                $this->convertTable($table, $columns, $from, $to);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function timestampColumns(string $table): array
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'id')) {
            return [];
        }

        return collect(Schema::getColumns($table))
            ->filter(fn (array $column) => in_array(strtolower($column['type_name']), ['datetime', 'timestamp'], true))
            ->pluck('name')
            ->diff(self::WALL_CLOCK_COLUMNS[$table] ?? [])
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $columns
     */
    private function convertTable(string $table, array $columns, string $from, string $to): void
    {
        DB::table($table)->select(['id', ...$columns])->orderBy('id')->chunkById(500, function ($rows) use ($table, $columns, $from, $to) {
            foreach ($rows as $row) {
                $changes = [];
                foreach ($columns as $column) {
                    if ($row->{$column} !== null) {
                        $changes[$column] = Carbon::parse($row->{$column}, $from)->setTimezone($to)->format('Y-m-d H:i:s');
                    }
                }

                if ($changes !== []) {
                    DB::table($table)->where('id', $row->id)->update($changes);
                }
            }
        });
    }
};
