<?php

namespace Database\Factories;

use App\Models\Mailbox;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Mailbox>
 */
class MailboxFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $email = fake()->unique()->safeEmail();

        return [
            'team_id' => fn () => Team::query()->create(['name' => 'Support', 'slug' => 'support-'.Str::random(6)])->id,
            'name' => 'Support Inbox',
            'email_address' => $email,
            'imap_host' => 'imap.example.com',
            'imap_port' => 993,
            'imap_encryption' => 'ssl',
            'imap_username' => $email,
            'imap_password' => 'secret',
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 587,
            'smtp_encryption' => 'tls',
            'smtp_username' => $email,
            'smtp_password' => 'secret',
        ];
    }

    public function withFetchError(string $message = 'AUTHENTICATIONFAILED'): static
    {
        return $this->state(['last_fetch_error' => $message, 'last_fetch_error_at' => now()]);
    }
}
