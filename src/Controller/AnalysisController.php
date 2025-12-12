<?php

namespace App\Controller;

use App\Entity\Molecule;
use App\Service\CachedRRunner;
use DateTimeImmutable;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AnalysisController extends AbstractController
{
    public function __construct(private readonly CachedRRunner $runner) {}

    #[Route('/molecules', name: 'app_molecules')]
    public function app_molecules(Request $request): Response
    {
        $filters = $this->buildFilterArgs($request, includeFamilies: true);
        $results = $this->runner->run(array_merge(
            $filters,
            [
                'count',
                'histo_count',
                'temporal_count:label=temporal_count_abs,scale=abs',
                'temporal_count:label=temporal_count_prop,scale=prop',
                'geo_count:label=geo_count_abs,scale=abs',
                'geo_count:label=geo_count_prop,scale=prop',
                'pie_consumption',
            ]
        ));

        return $this->render('analysis/molecules.html.twig', [
            'page_title' => 'Toutes molécules',
            'results' => $results,
            'filters_summary' => $this->summarizeFilters($request, includeFamilies: true),
        ]);
    }

    #[Route('/supply', name: 'app_supply')]
    public function app_supply(Request $request): Response
    {
        $filters = $this->buildFilterArgs($request, includeFamilies: true);
        $results = $this->runner->run(array_merge($filters, [
            'histo_supply',
            'temporal_supply',
        ]));

        return $this->render('analysis/supply.html.twig', [
            'page_title' => 'Supply',
            'results' => $results,
            'filters_summary' => $this->summarizeFilters($request, includeFamilies: true),
        ]);
    }

    #[Route('/purity/{slug}', name: 'app_purity')]
    public function app_purity(Request $request, #[MapEntity(expr: 'repository.findOneBySlug(slug)')] Molecule $molecule): Response
    {
        $unit = "pourcent";
        $delta = $this->resolveDelta($request);

        $analysis = [
            "-nip",
            'count',
            "histo_purity:label=histo_purity,unit=$unit",
            "temporal_purity:label=temporal_purity_avg,mode=avg,delta=$delta",
            "temporal_purity:label=temporal_purity_med,mode=med,delta=$delta",
            'supply_reg_purity',
            'geo_purity',
            'geo_reg_purity',
        ];

        $filters = $this->buildFilterArgs($request, includeNoCut: true);

        $results = match ($molecule->getLabel()) {
            'THC-resin' => $this->runner->run(array_merge([
                $this->formatOption('-m', 'Cannabis (THC/CBD)'),
                $this->formatOption('--form', 'Résine'),
            ], $filters, $analysis)),
            'THC-weed' => $this->runner->run(array_merge([
                $this->formatOption('-m', 'Cannabis (THC/CBD)'),
                $this->formatOption('--form', 'Herbe'),
            ], $filters, $analysis)),
            '2C-B' => $this->runner->run(array_merge([
                $this->formatOption('-m', '2C-B'),
                $this->formatOption('--form', 'Poudre,Cristal'),
            ], $filters, $analysis)),
            default => $this->runner->run(array_merge([
                $this->formatOption('-m', $molecule->getLabel()),
            ], $filters, $analysis)),
        };

        $results["histo_purity"]["ratio_base_sel"] = $molecule->getRatioBaseSel();

        return $this->render('analysis/purity.html.twig', [
            'molecule' => $molecule,
            'results' => $results,
            'unit' => $unit,
            'delta' => $delta,
            'filters_summary' => $this->summarizeFilters($request, includeDelta: true, includeNoCut: true),
        ]);
    }

    #[Route('/purity/tablets-{slug}', name: 'app_purity_tablets', priority: 1)]
    public function app_purity_tablets(Request $request, #[MapEntity(expr: 'repository.findOneBySlug(slug)')] Molecule $molecule): Response
    {
        $unit = "poids";
        $delta = $this->resolveDelta($request);

        $results = $this->runner->run(array_merge(
            [
                $this->formatOption('-m', $molecule->getLabel()),
                $this->formatOption('--form', 'comprimé'),
                '-pt',
                '--tablet_mass',
                'count',
                "histo_purity:unit=$unit",
                "temporal_purity:label=temporal_purity_avg,mode=avg,delta=$delta",
                "temporal_purity:label=temporal_purity_med,mode=med,delta=$delta",
                'mass_reg_purity',
            ],
            $this->buildFilterArgs($request, includeNoCut: true)
        ));

        return $this->render('analysis/purity_tablets.html.twig', [
            'molecule' => $molecule,
            'results' => $results,
            'unit' => $unit,
            'delta' => $delta,
            'filters_summary' => $this->summarizeFilters($request, includeDelta: true, includeNoCut: true),
        ]);
    }

    #[Route('/cut/{slug}', name: 'app_cut')]
    public function app_cut(Request $request, #[MapEntity(expr: 'repository.findOneBySlug(slug)')] Molecule $molecule): Response
    {
        $delta = 15;
        $unit = "pourcent";

        $filters = $this->buildFilterArgs($request);

        $analysis = array_merge($filters, [
            $this->formatOption('-m', $molecule->getLabel()),
            'count',
            'count_cut_agents',
            'histo_cut_agents',
            'temporal_cut_agents',
        ]);

        $results = match ($molecule->getLabel()) {
            '3-MMC' => $this->runner->run(array_merge($filters, [
                $this->formatOption('-m', '3-MMC'),
                'count',
                'count_cut_agents_3MMC:label=count_cut_agents',
                'histo_cut_agents_3MMC:label=histo_cut_agents',
                'temporal_cut_agents_3MMC:label=temporal_cut_agents',
            ])),
            default => $this->runner->run($analysis),
        };

        return $this->render('analysis/cut_agents.html.twig', [
            'molecule' => $molecule,
            'results' => $results,
            'unit' => $unit,
            'delta' => $delta,
            'data_reg_dose_poids' => NULL,
            'filters_summary' => $this->summarizeFilters($request),
        ]);
    }

    #[Route('/sub-products/{slug}', name: 'app_sub_products')]
    public function app_sub_products(Request $request, #[MapEntity(expr: 'repository.findOneBySlug(slug)')] Molecule $molecule): Response
    {
        $delta = 15;
        $unit = "pourcent";

        $results = $this->runner->run(array_merge(
            $this->buildFilterArgs($request),
            [
                $this->formatOption('-m', $molecule->getLabel()),
                'count',
                'histo_sub_products',
                'temporal_sub_products',
            ]
        ));

        return $this->render('analysis/sub_products.html.twig', [
            'molecule' => $molecule,
            'results' => $results,
            'unit' => $unit,
            'delta' => $delta,
            'data_reg_dose_poids' => NULL,
            'filters_summary' => $this->summarizeFilters($request),
        ]);
    }

    private function buildFilterArgs(Request $request, bool $includeFamilies = false, bool $includeNoCut = false): array
    {
        $args = [];

        $start = $this->formatDateForCli($request->query->get('date_debut'));
        $end = $this->formatDateForCli($request->query->get('date_fin'));

        if ($start && $end) {
            $args[] = $this->formatOption('--start', $start);
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

        return $args;
    }

    private function summarizeFilters(Request $request, bool $includeFamilies = false, bool $includeDelta = false, bool $includeNoCut = false): array
    {
        $summary = [];

        $start = $this->formatDateForCli($request->query->get('date_debut'));
        $end = $this->formatDateForCli($request->query->get('date_fin'));

        if ($start && $end) {
            $summary[] = sprintf('Entre le %s et le %s', $start, $end);
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

        return $summary;
    }

    private function resolveDelta(Request $request, int $default = 15): int
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
