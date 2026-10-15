<?php

namespace App\Services\Backup;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Writes and replays an SQL dump without mysqldump, because the hosts have
 * no shell. Supports MySQL/MariaDB (production) and SQLite (tests).
 */
class DatabaseDumper
{
    public const HEADER = '-- Custovis Datensicherung';

    /** Structure only: restored rows would revive stale locks, sessions and queued jobs. */
    private const SKIP_DATA = ['cache', 'cache_locks', 'sessions', 'jobs', 'job_batches'];

    private const ROWS_PER_INSERT = 200;

    public function dump(string $path): void
    {
        $file = fopen($path, 'wb');
        fwrite($file, self::HEADER."\n-- Treiber: {$this->driver()}\n-- Version: ".config('custovis.version').' | Erstellt: '.now()->toIso8601String()."\n");

        foreach ($this->tables() as $table) {
            foreach ($this->createStatements($table) as $statement) {
                fwrite($file, $statement.";\n");
            }
            if (! in_array($table, self::SKIP_DATA, true)) {
                $this->writeRows($file, $table);
            }
        }

        fclose($file);
    }

    /**
     * Replaces the whole database with the dump. The caller must have
     * validated the file first, since the current tables are dropped.
     */
    public function restore(string $path): void
    {
        $file = fopen($path, 'rb');
        $this->assertCompatible(fgets($file), fgets($file));

        Schema::dropAllTables();
        $this->setForeignKeyChecks(false);

        try {
            foreach ($this->statements($file) as $statement) {
                DB::unprepared($statement);
            }
        } finally {
            $this->setForeignKeyChecks(true);
            fclose($file);
        }
    }

    private function assertCompatible(string|false $header, string|false $driverLine): void
    {
        if (trim((string) $header) !== self::HEADER) {
            throw new RuntimeException('Die Datei ist keine Custovis-Datensicherung.');
        }
        if (trim((string) $driverLine) !== "-- Treiber: {$this->driver()}") {
            throw new RuntimeException('Die Sicherung stammt von einem anderen Datenbanksystem.');
        }
    }

    /**
     * @return list<string>
     */
    private function tables(): array
    {
        $schema = $this->driver() === 'sqlite' ? 'main' : $this->connection()->getDatabaseName();

        return Schema::getTableListing($schema, false);
    }

    /**
     * @return list<string>
     */
    private function createStatements(string $table): array
    {
        if ($this->driver() === 'sqlite') {
            return DB::table('sqlite_master')->where('tbl_name', $table)->whereNotNull('sql')
                ->orderByRaw("type = 'index'")->pluck('sql')->all();
        }

        $row = (array) DB::selectOne('SHOW CREATE TABLE '.$this->wrap($table));

        return [$row['Create Table']];
    }

    /**
     * @param  resource  $file
     */
    private function writeRows($file, string $table): void
    {
        $columns = null;
        $values = [];

        foreach (DB::table($table)->cursor() as $row) {
            $row = (array) $row;
            $columns ??= implode(', ', array_map(fn (string $column) => $this->wrap($column), array_keys($row)));
            $values[] = '('.implode(', ', array_map(fn ($value) => $this->quote($value), $row)).')';

            if (count($values) === self::ROWS_PER_INSERT) {
                fwrite($file, 'INSERT INTO '.$this->wrap($table)." ({$columns}) VALUES ".implode(', ', $values).";\n");
                $values = [];
            }
        }

        if ($values !== []) {
            fwrite($file, 'INSERT INTO '.$this->wrap($table)." ({$columns}) VALUES ".implode(', ', $values).";\n");
        }
    }

    /**
     * A statement ends with ";" at a line end outside of a string literal;
     * values may span lines (SQLite keeps newlines unescaped).
     *
     * @param  resource  $file
     * @return \Generator<int, string>
     */
    private function statements($file): \Generator
    {
        $buffer = '';
        $inString = false;

        while (($line = fgets($file)) !== false) {
            if ($buffer === '' && str_starts_with($line, '--')) {
                continue;
            }
            $buffer .= $line;
            $inString = $this->endsInsideString($line, $inString);

            if (! $inString && str_ends_with(rtrim($line), ';')) {
                yield $buffer;
                $buffer = '';
            }
        }
    }

    private function endsInsideString(string $line, bool $inString): bool
    {
        $backslashEscapes = $this->driver() !== 'sqlite';

        for ($i = 0, $length = strlen($line); $i < $length; $i++) {
            if ($inString && $backslashEscapes && $line[$i] === '\\') {
                $i++;
            } elseif ($line[$i] === "'") {
                $inString = ! $inString;
            }
        }

        return $inString;
    }

    private function setForeignKeyChecks(bool $enabled): void
    {
        $enabled ? Schema::enableForeignKeyConstraints() : Schema::disableForeignKeyConstraints();
    }

    private function quote(mixed $value): string
    {
        return $value === null ? 'NULL' : $this->connection()->getPdo()->quote((string) $value);
    }

    private function wrap(string $identifier): string
    {
        return $this->connection()->getQueryGrammar()->wrap($identifier);
    }

    private function driver(): string
    {
        return $this->connection()->getDriverName();
    }

    private function connection(): Connection
    {
        return DB::connection();
    }
}
