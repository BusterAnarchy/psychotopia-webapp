<?php

namespace App\Service;

class RRunner
{
    public function __construct(private string $rCliPath)
    {

    }

    public function run(array $args = []): mixed
    {
        $cmd = [$this->rCliPath, '-f json'];
        
        foreach ($args as $arg) {
            $cmd[] = $arg;
        }

        $output = shell_exec(implode(' ', $cmd));

        if (!$output) {
            throw new \RuntimeException("Le script R n'a rien renvoyé.");
        }

        $json = json_decode($output, true);

        if ($json === null) {
            throw new \RuntimeException("Sortie JSON invalide depuis R : " . $output);
        }

        return $json;
    }

    public static function formatOption(string $option, ?string $value = null): string
    {
        return $value === null ? $option: sprintf('%s %s', $option, escapeshellarg($value));
    }
}
