<?php

namespace App\Support;

use App\Models\User;
use App\Services\ModuleAccess;
use Illuminate\Support\Str;

/**
 * Catalogue of the in-app help topics. Agent content lives in resources/help/<slug>.md,
 * portal content in resources/help/portal/<slug>.md; a topic is only listed (and only
 * opens) if its area can actually be used.
 */
class HelpTopics
{
    public const GROUPS = [
        'basics' => 'Grundlagen',
        'work' => 'Arbeiten im Agenten-Bereich',
        'team' => 'Team-Einstellungen',
        'admin' => 'Administration',
    ];

    /**
     * slug => [title, summary, group, permission (optional), module slug (optional)].
     * Group 'team' is also open to team admins without the permission, like TeamSettings.
     */
    private const TOPICS = [
        'erste-schritte' => ['Erste Schritte & Kontosicherheit', 'Anmelden, Zwei-Faktor-Authentifizierung einrichten und sich zurechtfinden', 'basics'],
        'tickets-bearbeiten' => ['Tickets bearbeiten', 'Ticketliste, Antworten, interne Notizen, Status, Bearbeiter und Tags', 'basics'],
        'ticket-anlegen' => ['Ticket manuell anlegen', 'Telefonische oder persönliche Anfragen als Ticket erfassen', 'basics'],
        'itil-prozesse' => ['Incidents, Problems und Changes', 'ITIL-Zustände, Details, CAB-Freigabe anfordern und CIs zuordnen', 'work'],
        'cab-freigaben' => ['CAB-Freigaben erteilen', 'Changes als Mitglied des Change Advisory Boards genehmigen oder ablehnen', 'work', 'changes.approve', 'change-management'],
        'team-chat' => ['Team-Chat', 'Kanäle, Ticket-Chats, Direktnachrichten und Dateien', 'work', 'chat.channels.view', 'team-chat'],
        'wissensdatenbank-nutzen' => ['Wissensdatenbank nutzen', 'Artikel suchen, in Antworten einfügen und als Lösung markieren', 'work', 'kb.articles.view', 'knowledge-base'],
        'einsatzplanung' => ['Einsatzplanung (Dispatcher)', 'Vor-Ort-Einsätze anlegen, Techniker zuweisen und umplanen', 'work', 'dispatch.manage', 'field-service'],
        'techniker-app' => ['Techniker-App', 'Tagesliste, Status, Checklisten, Material, Unterschrift und Auslieferung', 'work', 'appointments.view.own', 'field-service'],
        'team-dashboard' => ['Team-Dashboard', 'Kennzahlen, Auslastung und letzte Aktivitäten Ihres Teams', 'work', null, 'reporting'],
        'textbausteine' => ['Textbausteine', 'Antwortvorlagen für das ganze Team pflegen', 'team', 'team.manage'],
        'api-keys' => ['API-Keys & MCP', 'Zugänge für die Ticket-API und KI-Telefonassistenten', 'team', 'team.api_keys.manage'],
        'whatsapp' => ['WhatsApp Business', 'WhatsApp-Konto verbinden und Chats als Tickets bearbeiten', 'team', 'team.whatsapp.manage', 'whatsapp'],
        'git-issues' => ['GitHub-/GitLab-Issues', 'Issues eines Repositorys automatisch als Tickets importieren', 'team', 'team.git_issues.manage'],
        'ki-einstellungen' => ['KI-Einstellungen', 'KI-Provider, API-Keys, Datenschutz und Monatsbudget des Teams', 'team', 'team.ai.manage', 'ai-agent'],
        'erp-anbindung' => ['ERP-/Shop-Anbindung', 'Kundendaten aus Odoo oder Shopware im Ticket anzeigen', 'team', 'team.erp.manage', 'erp-integration'],
        'nutzer' => ['Nutzer verwalten', 'Nutzer anlegen, einladen, Rollen vergeben und sperren', 'admin', 'users.manage'],
        'teams' => ['Teams verwalten', 'Teams anlegen und Mitglieder mit Team-Rolle zuordnen', 'admin', 'team.manage'],
        'rollen' => ['Rollen & Berechtigungen', 'Eigene Rollen anlegen und Berechtigungen zuweisen', 'admin', 'roles.manage'],
        'mailboxen' => ['Mailboxen', 'Postfächer per IMAP/SMTP anbinden und den Abruf prüfen', 'admin', 'mailboxes.manage'],
        'sla' => ['SLA & Geschäftszeiten', 'Reaktions- und Lösungsfristen je Priorität festlegen', 'admin', 'sla.manage'],
        'cmdb' => ['CMDB', 'Configuration Items und ihre Beziehungen pflegen', 'admin', 'cmdb.manage', 'cmdb'],
        'service-katalog' => ['Service-Katalog', 'Leistungen anlegen, die Kunden im Portal anfragen können', 'admin', 'service_catalog.manage', 'service-catalog'],
        'wissensdatenbank-pflegen' => ['Wissensdatenbank pflegen', 'Kategorien und versionierte Artikel verwalten', 'admin', 'kb.articles.manage', 'knowledge-base'],
        'techniker-verwalten' => ['Techniker & Checklisten', 'Technikerprofile, Skills, Schichten, Abwesenheiten und Checklisten-Vorlagen', 'admin', 'technicians.manage', 'field-service'],
        'kunden' => ['Kunden & Kundenportal', 'Kunden anlegen, ins Portal einladen und sperren', 'admin', 'customers.manage'],
        'datenschutz' => ['Datenschutz (DSGVO)', 'Auskunft exportieren, Personen anonymisieren, Aufbewahrungsfristen', 'admin', 'compliance.manage'],
        'module' => ['Module', 'Funktionsbereiche ein- und ausschalten und auf Rollen/Nutzer beschränken', 'admin', 'modules.manage'],
        'theme' => ['Theme', 'Erscheinungsbild der gesamten Installation wählen', 'admin', 'system.settings.manage'],
        'system' => ['System-Updates & Web-Cron', 'Datenbank-Updates ohne Shell ausführen und den Web-Cron einrichten', 'admin', 'system.maintain'],
        'management-dashboard' => ['Management-Dashboard', 'Kennzahlen über alle Teams und Warnungen zum Mailabruf', 'admin', 'dashboard.management.view', 'reporting'],
    ];

    /**
     * Customer portal screens: slug => [title, summary, module slug (optional)].
     */
    private const PORTAL_TOPICS = [
        'anmelden' => ['Anmelden & Passwort', 'Zugang einrichten, anmelden, Passwort vergessen und abmelden'],
        'meine-anfragen' => ['Meine Anfragen verfolgen', 'Status Ihrer Anfragen ansehen und dem Support antworten'],
        'neue-anfrage' => ['Neue Anfrage stellen', 'Eine Leistung aus dem Service-Katalog anfragen', 'service-catalog'],
        'hilfe-artikel' => ['Hilfe-Artikel nutzen', 'Lösungen selbst finden und Artikel bewerten', 'knowledge-base'],
    ];

    /**
     * @return array<string, array{title: string, summary: string, group: string}>
     */
    public static function for(User $user): array
    {
        $isTeamAdmin = $user->teams()->wherePivot('role_in_team', 'team_admin')->exists();
        $modules = app(ModuleAccess::class);

        return collect(self::TOPICS)
            ->filter(fn (array $topic) => (! isset($topic[4]) || $modules->allows($user, $topic[4]))
                && (! isset($topic[3]) || $user->can($topic[3]) || ($topic[2] === 'team' && $isTeamAdmin)))
            ->map(fn (array $topic) => ['title' => $topic[0], 'summary' => $topic[1], 'group' => $topic[2]])
            ->all();
    }

    /**
     * Customers have no per-person module assignment, so only the global switch counts
     * (same rule as the module middleware for the portal).
     *
     * @return array<string, array{title: string, summary: string}>
     */
    public static function forPortal(): array
    {
        $modules = app(ModuleAccess::class);

        return collect(self::PORTAL_TOPICS)
            ->filter(fn (array $topic) => ! isset($topic[2]) || $modules->enabled($topic[2]))
            ->map(fn (array $topic) => ['title' => $topic[0], 'summary' => $topic[1]])
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_keys(self::TOPICS);
    }

    /**
     * @return list<string>
     */
    public static function portalSlugs(): array
    {
        return array_keys(self::PORTAL_TOPICS);
    }

    /**
     * @param  string  $path  slug relative to resources/help, e.g. "mailboxen" or "portal/anmelden"
     */
    public static function html(string $path): string
    {
        return Str::markdown(
            file_get_contents(resource_path("help/{$path}.md")),
            ['html_input' => 'escape', 'allow_unsafe_links' => false],
        );
    }
}
