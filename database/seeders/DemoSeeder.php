<?php

namespace Database\Seeders;

use App\Models\CannedResponse;
use App\Models\ChatMessage;
use App\Models\Customer;
use App\Models\KnowledgeBaseCategory;
use App\Models\ServiceCatalogItem;
use App\Models\Skill;
use App\Models\SlaPolicy;
use App\Models\Team;
use App\Models\TechnicianProfile;
use App\Models\Ticket;
use App\Models\TicketChange;
use App\Models\TicketIncident;
use App\Models\TicketProblem;
use App\Models\User;
use App\Services\Chat\ChatAccess;
use App\Services\FieldService\AppointmentService;
use App\Services\KnowledgeBaseService;
use App\Services\ServiceCatalogService;
use App\States\Change\Draft;
use App\States\Incident\InProgress;
use App\States\Problem\Investigating;
use Illuminate\Database\Seeder;

/**
 * Presentable demo installation (12.md): three teams, agents, customers,
 * tickets of every ITIL type, knowledge base, chat and a field appointment.
 * All demo accounts share the password "demo-passwort" — never use on a
 * production system.
 */
class DemoSeeder extends Seeder
{
    public const PASSWORD = 'demo-passwort';

    public function run(): void
    {
        if (Team::query()->where('slug', 'kundenservice')->exists()) {
            return;
        }

        [$support, $ops, $field] = $this->teams();
        $people = $this->people($support, $ops, $field);
        $this->tickets($support, $ops, $people);
        $this->knowledgeBase($people['anna']);
        $this->chat($support, $people);
        $this->fieldService($field, $people);
    }

    /**
     * @return array<int, Team>
     */
    private function teams(): array
    {
        return [
            Team::query()->create(['name' => 'Kundenservice', 'slug' => 'kundenservice', 'description' => 'First-Level-Support']),
            Team::query()->create(['name' => 'IT-Betrieb', 'slug' => 'it-betrieb', 'description' => 'Infrastruktur und Changes']),
            Team::query()->create(['name' => 'Außendienst', 'slug' => 'aussendienst', 'description' => 'Vor-Ort-Einsätze']),
        ];
    }

    /**
     * @return array<string, User|Customer>
     */
    private function people(Team $support, Team $ops, Team $field): array
    {
        $user = fn (string $name, string $email, string $role, Team $team, string $teamRole = 'member') => tap(
            User::query()->create(['name' => $name, 'email' => $email, 'password' => self::PASSWORD]),
            function (User $user) use ($role, $team, $teamRole) {
                $user->assignRole($role);
                $team->users()->attach($user, ['role_in_team' => $teamRole]);
            }
        );

        $dora = $user('Dora Dispatch', 'dora@demo.custovis.local', 'agent', $field, 'team_admin');
        $dora->givePermissionTo(['dispatch.manage', 'technicians.manage']);

        return [
            'anna' => $user('Anna Agent', 'anna@demo.custovis.local', 'agent', $support, 'team_admin'),
            'olaf' => $user('Olaf Operator', 'olaf@demo.custovis.local', 'agent', $ops, 'team_admin'),
            'tina' => $user('Tina Technik', 'tina@demo.custovis.local', 'technician', $field),
            'dora' => $dora,
            'erika' => Customer::query()->create(['name' => 'Erika Musterfrau', 'email' => 'erika@example.com', 'password' => self::PASSWORD]),
            'max' => Customer::query()->create(['name' => 'Max Mustermann', 'email' => 'max@example.com', 'password' => self::PASSWORD]),
        ];
    }

    /**
     * @param  array<string, User|Customer>  $people
     */
    private function tickets(Team $support, Team $ops, array $people): void
    {
        SlaPolicy::query()->create(['team_id' => $support->id, 'name' => 'Standard', 'priority' => 'normal', 'response_time_minutes' => 240, 'resolution_time_minutes' => 2880]);
        SlaPolicy::query()->create(['team_id' => $support->id, 'name' => 'Dringend', 'priority' => 'high', 'response_time_minutes' => 60, 'resolution_time_minutes' => 480]);
        CannedResponse::query()->create(['team_id' => $support->id, 'title' => 'Danke für Ihre Geduld', 'body' => "Vielen Dank für Ihre Geduld – wir melden uns in Kürze.\n\nIhr Kundenservice"]);

        $this->ticket($support, $people['erika'], 'Rechnung doppelt erhalten', 'Ich habe die Rechnung RE-1042 zweimal bekommen.', ['priority' => 'normal', 'assigned_to' => $people['anna']->id]);
        $this->ticket($support, $people['max'], 'Passwort-Reset funktioniert nicht', 'Die E-Mail zum Zurücksetzen kommt nicht an.', ['priority' => 'high']);

        $incident = $this->ticket($ops, null, 'Mailserver nicht erreichbar', 'Monitoring meldet Timeouts auf mail01.', ['type' => 'incident', 'source' => 'api', 'priority' => 'urgent', 'assigned_to' => $people['olaf']->id]);
        TicketIncident::query()->create(['ticket_id' => $incident->id, 'state' => InProgress::class, 'impact' => 'high', 'urgency' => 'high']);
        $incident->messages()->create(['visibility' => 'internal_note', 'direction' => 'outgoing', 'author_user_id' => $people['olaf']->id, 'body_text' => 'Failover auf mail02 eingeleitet.']);

        $problem = $this->ticket($ops, null, 'Wiederkehrende Mail-Timeouts', 'Drei Incidents in zwei Wochen – Ursache suchen.', ['type' => 'problem', 'source' => 'api']);
        TicketProblem::query()->create(['ticket_id' => $problem->id, 'state' => Investigating::class]);

        $change = $this->ticket($ops, null, 'Mailserver auf neue Hardware migrieren', 'Geplante Migration am Wochenende.', ['type' => 'change', 'source' => 'api']);
        TicketChange::query()->create(['ticket_id' => $change->id, 'state' => Draft::class, 'change_type' => 'normal', 'risk_level' => 'medium', 'planned_start' => now()->next('Saturday')->setTime(8, 0)]);

        $laptop = ServiceCatalogItem::query()->create(['team_id' => $ops->id, 'name' => 'Neuer Laptop', 'description' => 'Standard-Arbeitsplatzrechner inkl. Einrichtung', 'active' => true]);
        ServiceCatalogItem::query()->create(['team_id' => $ops->id, 'name' => 'VPN-Zugang', 'description' => 'Zugang zum Firmennetz von unterwegs', 'active' => true]);
        app(ServiceCatalogService::class)->createRequest($laptop, $people['erika']->email, $people['erika']->name, $people['erika'], 'Bitte mit deutscher Tastatur.');
    }

    private function ticket(Team $team, ?Customer $customer, string $subject, string $body, array $attributes = []): Ticket
    {
        $ticket = Ticket::query()->create($attributes + [
            'team_id' => $team->id,
            'source' => 'mailbox',
            'subject' => $subject,
            'customer_id' => $customer?->id,
            'requester_email' => $customer?->email,
            'requester_name' => $customer?->name,
        ]);

        $ticket->messages()->create(['visibility' => 'public', 'direction' => 'incoming', 'author_customer_id' => $customer?->id, 'body_text' => $body, 'body_html' => e($body)]);

        return $ticket;
    }

    private function knowledgeBase(User $author): void
    {
        $general = KnowledgeBaseCategory::query()->create(['name' => 'Allgemein']);
        $phone = KnowledgeBaseCategory::query()->create(['name' => 'Telefon-FAQ', 'parent_id' => $general->id]);
        $tech = KnowledgeBaseCategory::query()->create(['name' => 'Technik intern']);
        $kb = app(KnowledgeBaseService::class);

        $kb->publish(null, ['category_id' => $phone->id, 'title' => 'Öffnungszeiten der Hotline', 'body' => 'Die Hotline ist **montags bis freitags von 8 bis 18 Uhr** erreichbar.', 'visibility' => 'public'], $author);
        $kb->publish(null, ['category_id' => $general->id, 'title' => 'Passwort zurücksetzen', 'body' => "1. Auf *Passwort vergessen* klicken\n2. E-Mail-Adresse eingeben\n3. Link in der E-Mail öffnen", 'visibility' => 'public'], $author);
        $kb->publish(null, ['category_id' => $tech->id, 'title' => 'Failover mail01 → mail02', 'body' => 'Nur für den IT-Betrieb: `failover.sh mail02` auf dem Jump-Host ausführen.', 'visibility' => 'internal'], $author);
    }

    /**
     * @param  array<string, User|Customer>  $people
     */
    private function chat(Team $support, array $people): void
    {
        $access = app(ChatAccess::class);

        // Created directly (not via ChatService) so seeding never needs a broadcaster.
        $access->teamChannel($support)->messages()->create(['user_id' => $people['anna']->id, 'body' => 'Guten Morgen! Wer übernimmt heute die Hotline?']);
        $access->globalChannel()->messages()->create(['user_id' => $people['olaf']->id, 'body' => 'Wartungsfenster Samstag 8–12 Uhr: Mailserver-Migration.']);
        ChatMessage::query()->create(['direct_thread_id' => $access->directThread($people['anna'], $people['olaf'])->id, 'user_id' => $people['anna']->id, 'body' => 'Kannst du dir Ticket „Mailserver nicht erreichbar" ansehen?']);
    }

    /**
     * @param  array<string, User|Customer>  $people
     */
    private function fieldService(Team $field, array $people): void
    {
        $heating = Skill::query()->create(['name' => 'Heizungstechnik']);
        Skill::query()->create(['name' => 'Netzwerk']);

        $tina = TechnicianProfile::query()->create(['user_id' => $people['tina']->id, 'home_address' => 'Alexanderplatz 1, Berlin', 'home_lat' => 52.5219, 'home_lng' => 13.4132]);
        $tina->skills()->attach($heating, ['level' => 4]);
        foreach (range(1, 5) as $weekday) {
            $tina->shifts()->create(['weekday' => $weekday, 'starts_at' => '07:00:00', 'ends_at' => '16:00:00']);
        }

        $ticket = $this->ticket($field, $people['max'], 'Heizung ohne Funktion', 'Die Heizung bleibt seit gestern kalt.', ['type' => 'incident', 'priority' => 'high']);
        $start = now()->addWeekday()->setTime(9, 0);

        app(AppointmentService::class)->create($ticket, [
            'kind' => 'service', 'address' => 'Unter den Linden 10, Berlin', 'scheduled_start' => $start, 'scheduled_end' => $start->copy()->addHours(2),
            'required_skill_ids' => [$heating->id], 'notes' => 'Kunde ist ab 8:30 Uhr erreichbar.',
        ], $people['dora']);
    }
}
