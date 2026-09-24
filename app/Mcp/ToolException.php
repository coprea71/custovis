<?php

namespace App\Mcp;

use RuntimeException;

/**
 * Structured error for MCP tools (13.md): the class tells the voice agent
 * whether to ask the caller again (client_error), give up on this record
 * (not_found) or escalate to a human (server_error).
 */
class ToolException extends RuntimeException
{
    public const CLIENT_ERROR = 'client_error';

    public const NOT_FOUND = 'not_found';

    public const SERVER_ERROR = 'server_error';

    public function __construct(public readonly string $errorClass, string $message)
    {
        parent::__construct($message);
    }

    public static function notFound(string $message): self
    {
        return new self(self::NOT_FOUND, $message);
    }
}
