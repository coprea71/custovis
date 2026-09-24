<?php

namespace App\Services\Installation;

use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

/**
 * Writes .env from .env.example for the web installer. Values are strictly
 * checked: a newline or quote inside a value could otherwise inject
 * arbitrary settings (e.g. APP_DEBUG=true) into the environment file.
 */
class EnvironmentWriter
{
    public function __construct(
        private readonly ?string $examplePath = null,
        private readonly ?string $targetPath = null,
    ) {}

    /**
     * @param  array<string, string>  $values
     * @return string the generated APP_KEY
     */
    public function write(array $values): string
    {
        $key = 'base64:'.base64_encode(Encrypter::generateKey(config('app.cipher')));
        $values = ['APP_KEY' => $key, 'APP_ENV' => 'production', 'APP_DEBUG' => 'false', ...$values];

        $content = File::get($this->examplePath ?? base_path('.env.example'));

        foreach ($values as $name => $value) {
            $line = $name.'='.$this->quote($value);
            $content = preg_match("/^{$name}=.*$/m", $content)
                ? preg_replace("/^{$name}=.*$/m", str_replace(['\\', '$'], ['\\\\', '\\$'], $line), $content)
                : rtrim($content)."\n{$line}\n";
        }

        File::put($this->targetPath ?? base_path('.env'), $content);

        return $key;
    }

    private function quote(string $value): string
    {
        if (preg_match('/[\r\n"\\\\]/', $value)) {
            throw new InvalidArgumentException('Ungültige Zeichen in einem Konfigurationswert.');
        }

        return preg_match('/[\s#$]/', $value) || $value === '' ? '"'.$value.'"' : $value;
    }
}
