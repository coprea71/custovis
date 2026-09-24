<x-mail::message>
# Hallo {{ $customer->name }},

@if ($invitation)
für Sie wurde ein Zugang zum Kundenportal eingerichtet. Dort können Sie Anfragen stellen, den Status Ihrer Tickets verfolgen und Hilfe-Artikel lesen. Bitte legen Sie über den folgenden Link Ihr persönliches Passwort fest.
@else
wir haben eine Anfrage zum Zurücksetzen Ihres Passworts für das Kundenportal erhalten. Über den folgenden Link können Sie ein neues Passwort festlegen.
@endif

<x-mail::button :url="$url">
Passwort festlegen
</x-mail::button>

Der Link ist {{ $expireMinutes }} Minuten gültig. Falls Sie diese E-Mail nicht erwartet haben, können Sie sie ignorieren.
</x-mail::message>
