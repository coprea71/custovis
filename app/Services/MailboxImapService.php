<?php

namespace App\Services;

use App\Models\Mailbox;
use RuntimeException;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\ClientManager;

class MailboxImapService
{
    public function connect(Mailbox $mailbox): Client
    {
        $client = (new ClientManager)->make([
            'host' => $mailbox->imap_host,
            'port' => $mailbox->imap_port,
            'encryption' => $mailbox->imap_encryption === 'none' ? false : $mailbox->imap_encryption,
            'validate_cert' => true,
            'username' => $mailbox->imap_username,
            'password' => $mailbox->imap_password,
            'protocol' => 'imap',
        ]);

        $client->connect();

        return $client;
    }

    /**
     * Read-only check: logs in and counts unseen INBOX messages without importing or flagging them.
     */
    public function countUnseen(Mailbox $mailbox): int
    {
        $client = $this->connect($mailbox);

        try {
            $inbox = $client->getFolder('INBOX') ?? throw new RuntimeException('Ordner INBOX nicht gefunden.');

            return $inbox->query()->unseen()->count();
        } finally {
            $client->disconnect();
        }
    }
}
