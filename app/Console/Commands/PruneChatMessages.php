<?php

namespace App\Console\Commands;

use App\Models\ChatMessage;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneChatMessages extends Command
{
    public const RETENTION_SETTING_KEY = 'chat.retention_days';

    protected $signature = 'chat:prune';

    protected $description = 'Delete team chat messages older than the configured retention period';

    /**
     * Opt-in: without a configured retention (setting empty/0) nothing is deleted.
     */
    public function handle(): int
    {
        $days = (int) Setting::read(self::RETENTION_SETTING_KEY, '0');

        if ($days <= 0) {
            $this->info('Chat retention disabled, nothing pruned.');

            return self::SUCCESS;
        }

        $deleted = 0;

        ChatMessage::query()->where('created_at', '<', now()->subDays($days))->chunkById(500, function ($messages) use (&$deleted) {
            foreach ($messages as $message) {
                if ($message->attachment_path) {
                    Storage::disk($message->attachment_disk)->delete($message->attachment_path);
                }
                $message->delete();
                $deleted++;
            }
        });

        $this->info("{$deleted} chat message(s) pruned.");

        return self::SUCCESS;
    }
}
