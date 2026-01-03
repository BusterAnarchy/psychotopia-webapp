<?php

namespace App\Service;

use function array_filter;
use function implode;
use function is_array;
use function is_bool;

final class RRunnerBuilder
{
    private array $arguments = [];

    public static function create(): self
    {
        return new self();
    }

    public function build(): array
    {
        return $this->arguments;
    }

    private function addArgument(string $argument): self
    {
        $argument = trim($argument);

        if ($argument !== '') {
            $this->arguments[] = $argument;
        }

        return $this;
    }

    public function addAnalysis(string $name, array $arguments = []): self
    {
        $argument = $name;

        if ($arguments !== []) {
            $pairs = [];

            foreach ($arguments as $key => $value) {
                if ($value === null) {
                    continue;
                }

                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                }

                $pairs[] = sprintf('%s=%s', $key, $value);
            }

            if ($pairs !== []) {
                $argument .= ':' . implode(',', $pairs);
            }
        }

        return $this->addArgument($argument);
    }

    public function addOption(string $option, string $value = null): self
    {
        return $this->addArgument($value === null ? $option : sprintf('%s %s', $option, escapeshellarg($value)));
    }

    public function forMolecule(string $label): self
    {
        return $this->addOption('-m', $label);
    }

    public function withForms(string|array $forms): self
    {
        if (is_array($forms)) {
            $forms = implode(',', array_filter($forms));
        }

        if ($forms === '') {
            return $this;
        }

        return $this->addOption('--form', $forms);
    }

    public function withSupply(string|array $supplies): self
    {
        if (is_array($supplies)) {
            $supplies = implode(',', array_filter($supplies));
        }

        if ($supplies === '') {
            return $this;
        }

        return $this->addOption('--supply', $supplies);
    }

    public function withFilters(array $filters): self
    {
        foreach ($filters as $argument) {
            $this->addArgument($argument);
        }

        return $this;
    }
}
