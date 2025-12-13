<?php

namespace App\Service;

use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Request;

class FilterService
{
    public function buildFilterArgs(
        Request $request,
        bool $includeFamilies = false,
        bool $includeNoCut = false,
        bool $includeForms = false
    ): array
    {
        $args = [];

        $start = $this->formatDateForCli($request->query->get('date_debut'));
        $end = $this->formatDateForCli($request->query->get('date_fin'));

        if ($start) {
            $args[] = $this->formatOption('--start', $start);
        }

        if ($end) {
            $args[] = $this->formatOption('--end', $end);
        }

        if ($includeFamilies) {
            $families = $this->normalizeCsv($request->query->get('familles'));
            if (!empty($families)) {
                $args[] = $this->formatOption('--molecule_families', implode(',', $families));
            }
        }

        if ($includeNoCut && $request->query->getBoolean('no_cut')) {
            $args[] = '--no-purity';
        }

        if ($includeForms) {
            $forms = $this->normalizeCsv($request->query->get('formes'));
            if (!empty($forms)) {
                $args[] = $this->formatOption('--form', implode(',', $forms));
            }
        }

        return $args;
    }

    public function summarizeFilters(
        Request $request,
        bool $includeFamilies = false,
        bool $includeDelta = false,
        bool $includeNoCut = false,
        bool $includeForms = false
    ): array
    {
        $summary = [];

        $start = $this->formatDateForCli($request->query->get('date_debut'));
        $end = $this->formatDateForCli($request->query->get('date_fin'));

        if ($start && $end) {
            $summary[] = sprintf('Entre le %s et le %s', $start, $end);
        } else if ($start) {
            $summary[] = sprintf('Depuis le %s', $start);
        } else if ($end) {
            $summary[] = sprintf('Jusqu\'au %s', $end);
        }

        if ($includeFamilies) {
            $families = $this->normalizeCsv($request->query->get('familles'));
            if (!empty($families)) {
                $labels = array_map(static fn(string $family) => str_replace('_', ' ', $family), $families);
                $summary[] = sprintf('Familles : %s', implode(', ', $labels));
            }
        }

        if ($includeDelta) {
            $delta = $this->extractDelta($request);
            if ($delta !== null) {
                $summary[] = sprintf('Δ = %d jours', $delta);
            }
        }

        if ($includeNoCut && $request->query->getBoolean('no_cut')) {
            $summary[] = 'Pureté > 0 uniquement';
        }

        if ($includeForms) {
            $forms = $this->normalizeCsv($request->query->get('formes'));
            if (!empty($forms)) {
                $summary[] = sprintf('Formes : %s', implode(', ', $forms));
            }
        }

        return $summary;
    }

    public function resolveDelta(Request $request, int $default = 15): int
    {
        return $this->extractDelta($request) ?? $default;
    }

    private function extractDelta(Request $request): ?int
    {
        $range = $request->query->get('range');

        if ($range !== null && ctype_digit($range)) {
            $value = (int) $range;
            if ($value >= 1 && $value <= 30) {
                return $value;
            }
        }

        return null;
    }

    private function formatDateForCli(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if ($date === false) {
            return null;
        }

        return $date->format('d/m/Y');
    }

    private function normalizeCsv(?string $value): array
    {
        if (!$value) {
            return [];
        }

        $items = array_filter(array_map('trim', explode(',', $value)));

        return array_values($items);
    }

    private function formatOption(string $option, ?string $value = null): string
    {
        return $value === null
            ? $option
            : sprintf('%s %s', $option, escapeshellarg($value));
    }
}
