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
        $filters = $this->buildFilterArgs($request, includeFamilies: true, includeForms: true);
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

        if ($response = $this->renderChartEmbed($request, $results, [
            'distribution' => [
                'title' => 'Répartition par molécule',
                'template' => 'components/charts/pie_chart.html.twig',
                'result_key' => 'histo_count',
                'context' => ['id' => 'embedded_chart'],
            ],
            'first_consumption' => [
                'title' => 'Proportion après première consommation',
                'template' => 'components/charts/pie_chart.html.twig',
                'result_key' => 'pie_consumption',
                'context' => ['id' => 'embedded_chart'],
            ],
            'timeline_absolute' => [
                'title' => "Évolution temporelle — Nombre d'échantillons",
                'template' => 'components/charts/area_stacked_chart.html.twig',
                'result_key' => 'temporal_count_abs',
                'context' => [
                    'id' => 'embedded_chart',
                    'is_stacked' => 'true',
                    'mode' => 'absolu',
                ],
            ],
            'timeline_relative' => [
                'title' => "Évolution temporelle — Proportion",
                'template' => 'components/charts/area_stacked_chart.html.twig',
                'result_key' => 'temporal_count_prop',
                'context' => [
                    'id' => 'embedded_chart',
                    'is_stacked' => 'true',
                    'mode' => 'relatif',
                ],
            ],
            'map_absolute' => [
                'title' => "Carte — Nombre d'échantillons par région",
                'renderer' => 'map',
                'result_key' => 'geo_count_abs',
                'context' => ['id' => 'embedded_map'],
                'options' => [
                    'start_hsl' => [120, 60, 85],
                    'end_hsl' => [200, 100, 30],
                ],
            ],
            'map_relative' => [
                'title' => "Carte — Échantillons par million d'habitants",
                'renderer' => 'map',
                'result_key' => 'geo_count_prop',
                'context' => ['id' => 'embedded_map'],
                'options' => [
                    'start_hsl' => [50, 100, 70],
                    'end_hsl' => [0, 100, 40],
                ],
            ],
        ], 'Toutes molécules')) {
            return $response;
        }

        return $this->render('pages/page_molecules.html.twig', [
            'page_title' => 'Toutes molécules',
            'results' => $results,
            'filters_summary' => $this->summarizeFilters($request, includeFamilies: true, includeForms: true),
        ]);
    }

    #[Route('/supply', name: 'app_supply')]
    public function app_supply(Request $request): Response
    {
        $filters = $this->buildFilterArgs($request, includeFamilies: true, includeForms: true);
        $results = $this->runner->run(array_merge($filters, [
            'histo_supply',
            'temporal_supply',
        ]));

        if ($response = $this->renderChartEmbed($request, $results, [
            'distribution' => [
                'title' => "Répartition par voie d'approvisionnement",
                'template' => 'components/charts/pie_chart.html.twig',
                'result_key' => 'histo_supply',
                'context' => ['id' => 'embedded_chart'],
            ],
            'timeline' => [
                'title' => "Évolution temporelle par voie d'approvisionnement",
                'template' => 'components/charts/area_stacked_chart.html.twig',
                'result_key' => 'temporal_supply',
                'context' => [
                    'id' => 'embedded_chart',
                    'is_stacked' => 'true',
                    'mode' => 'relatif',
                ],
            ],
        ], 'Supply')) {
            return $response;
        }

        return $this->render('pages/page_supply.html.twig', [
            'page_title' => 'Supply',
            'results' => $results,
            'filters_summary' => $this->summarizeFilters($request, includeFamilies: true, includeForms: true),
        ]);
    }

    #[Route('/content/{slug}', name: 'app_content')]
    #[Route('/purity/{slug}', name: 'app_purity')]
    public function app_purity(Request $request, #[MapEntity(expr: 'repository.findOneBySlug(slug)')] Molecule $molecule): Response
    {
        $unit = "pourcent";
        $delta = $this->resolveDelta($request);
        $isContentRoute = $request->attributes->get('_route') === 'app_content';
        $analysisLabel = $isContentRoute ? 'Teneur' : 'Pureté';
        $analysisLabelLower = mb_strtolower($analysisLabel);

        $analysis = [
            "-nip",
            'count',
            "histo_purity:label=histo_purity,unit=$unit",
            "temporal_purity:label=temporal_purity_avg,mode=avg,delta=$delta,unit=$unit",
            "temporal_purity:label=temporal_purity_med,mode=med,delta=$delta,unit=$unit",
            'supply_reg_purity',
            'geo_purity',
            'geo_reg_purity',
        ];

        $filters = $this->buildFilterArgs($request, includeNoCut: true);

        $results = match ($molecule->getLabel()) {
            'Cannabis Résine' => $this->runner->run(array_merge([
                $this->formatOption('-m', 'Cannabis (THC/CBD)'),
                $this->formatOption('--form', 'Résine'),
            ], $filters, $analysis)),
            'Cannabis Herbe' => $this->runner->run(array_merge([
                $this->formatOption('-m', 'Cannabis (THC/CBD)'),
                $this->formatOption('--form', 'Herbe'),
            ], $filters, $analysis)),
            '2C-B' => $this->runner->run(array_merge([
                $this->formatOption('-m', '2C-B'),
                $this->formatOption('--form', 'Poudre,Cristal'),
            ], $filters, $analysis)),
            'MDMA' => $this->runner->run(array_merge([
                $this->formatOption('-m', 'MDMA'),
                $this->formatOption('--form', 'Poudre,Cristal'),
            ], $filters, $analysis)),
            default => $this->runner->run(array_merge([
                $this->formatOption('-m', $molecule->getLabel()),
            ], $filters, $analysis)),
        };

        $results["histo_purity"]["ratio_base_sel"] = $molecule->getRatioBaseSel();

        if ($response = $this->renderChartEmbed($request, $results, [
            'histogram' => [
                'title' => sprintf('Histogramme de la %s', $analysisLabelLower),
                'template' => 'components/charts/bar_y_chart.html.twig',
                'result_key' => 'histo_purity',
                'context' => [
                    'id' => 'embedded_chart',
                    'unit' => $unit,
                ],
            ],
            'temporal_mean' => [
                'title' => 'Évolution temporelle – Moyennes et écarts type',
                'template' => 'components/charts/line_chart.html.twig',
                'result_key' => 'temporal_purity_avg',
                'context' => [
                    'id' => 'embedded_chart',
                    'is_stacked' => 'false',
                ],
            ],
            'temporal_median' => [
                'title' => 'Évolution temporelle – Médianes et quartiles',
                'template' => 'components/charts/line_chart.html.twig',
                'result_key' => 'temporal_purity_med',
                'context' => [
                    'id' => 'embedded_chart',
                    'is_stacked' => 'false',
                ],
            ],
            'map' => [
                'title' => sprintf('Carte de la %s moyenne par région', $analysisLabelLower),
                'renderer' => 'map',
                'result_key' => 'geo_purity',
                'context' => ['id' => 'embedded_map'],
                'options' => [
                    'start_hsl' => [120, 60, 85],
                    'end_hsl' => [200, 100, 30],
                ],
            ],
        ], sprintf('%s - %s', $analysisLabel, $molecule->getLabel()))) {
            return $response;
        }

        return $this->render('pages/page_purity.html.twig', [
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
                "temporal_purity:label=temporal_purity_avg,mode=avg,delta=$delta,unit=$unit",
                "temporal_purity:label=temporal_purity_med,mode=med,delta=$delta,unit=$unit",
                'mass_reg_purity',
            ],
            $this->buildFilterArgs($request, includeNoCut: true)
        ));

        if ($response = $this->renderChartEmbed($request, $results, [
            'histogram' => [
                'title' => 'Histogramme de la pureté',
                'template' => 'components/charts/bar_y_chart.html.twig',
                'result_key' => 'histo_purity',
                'context' => [
                    'id' => 'embedded_chart',
                    'unit' => $unit,
                ],
            ],
            'temporal_mean' => [
                'title' => 'Évolution temporelle – Moyenne et écarts type',
                'template' => 'components/charts/line_chart.html.twig',
                'result_key' => 'temporal_purity_avg',
                'context' => [
                    'id' => 'embedded_chart',
                    'is_stacked' => 'false',
                ],
            ],
            'temporal_median' => [
                'title' => 'Évolution temporelle – Médianes et quartiles',
                'template' => 'components/charts/line_chart.html.twig',
                'result_key' => 'temporal_purity_med',
                'context' => [
                    'id' => 'embedded_chart',
                    'is_stacked' => 'false',
                ],
            ],
            'scatter' => [
                'title' => 'Quantité de substance active vs masse des comprimés',
                'template' => 'components/scatter_charts/line_chart.html.twig',
                'result_key' => 'mass_reg_purity',
                'context' => [
                    'id' => 'embedded_chart',
                ],
            ],
        ], sprintf('Pureté comprimé - %s', $molecule->getLabel()))) {
            return $response;
        }

        return $this->render('pages/page_purity_tablets.html.twig', [
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

        $filters = $this->buildFilterArgs($request, includeNoCut: true);

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

        if ($response = $this->renderChartEmbed($request, $results, [
            'share' => [
                'title' => 'Proportion des échantillons avec produits de coupe',
                'template' => 'components/charts/pie_chart.html.twig',
                'result_key' => 'count_cut_agents',
                'context' => [
                    'id' => 'embedded_chart',
                ],
            ],
            'distribution' => [
                'title' => 'Proportion par produit de coupe',
                'template' => 'components/charts/bar_x_chart.html.twig',
                'result_key' => 'histo_cut_agents',
                'context' => [
                    'id' => 'embedded_chart',
                ],
            ],
            'timeline' => [
                'title' => 'Évolution temporelle des produits de coupe',
                'template' => 'components/charts/area_stacked_chart.html.twig',
                'result_key' => 'temporal_cut_agents',
                'context' => [
                    'id' => 'embedded_chart',
                    'is_stacked' => 'false',
                    'mode' => 'relatif',
                ],
            ],
        ], sprintf('Coupe - %s', $molecule->getLabel()))) {
            return $response;
        }

        return $this->render('pages/page_cut_agents.html.twig', [
            'molecule' => $molecule,
            'results' => $results,
            'unit' => $unit,
            'delta' => $delta,
            'data_reg_dose_poids' => NULL,
            'filters_summary' => $this->summarizeFilters($request, includeNoCut: true),
        ]);
    }

    #[Route('/sub-products/{slug}', name: 'app_sub_products')]
    public function app_sub_products(Request $request, #[MapEntity(expr: 'repository.findOneBySlug(slug)')] Molecule $molecule): Response
    {
        $delta = 15;
        $unit = "pourcent";

        $results = $this->runner->run(array_merge(
            $this->buildFilterArgs($request, includeNoCut: true),
            [
                $this->formatOption('-m', $molecule->getLabel()),
                'count',
                'histo_sub_products',
                'temporal_sub_products',
            ]
        ));

        if ($response = $this->renderChartEmbed($request, $results, [
            'distribution' => [
                'title' => 'Proportion par sous-produit',
                'template' => 'components/charts/bar_x_chart.html.twig',
                'result_key' => 'histo_sub_products',
                'context' => [
                    'id' => 'embedded_chart',
                ],
            ],
            'timeline' => [
                'title' => 'Évolution temporelle des sous-produits',
                'template' => 'components/charts/area_stacked_chart.html.twig',
                'result_key' => 'temporal_sub_products',
                'context' => [
                    'id' => 'embedded_chart',
                    'is_stacked' => 'false',
                    'mode' => 'relatif',
                ],
            ],
        ], sprintf('Sous produits - %s', $molecule->getLabel()))) {
            return $response;
        }

        return $this->render('pages/page_sub_products.html.twig', [
            'molecule' => $molecule,
            'results' => $results,
            'unit' => $unit,
            'delta' => $delta,
            'data_reg_dose_poids' => NULL,
            'filters_summary' => $this->summarizeFilters($request, includeNoCut: true),
        ]);
    }

    private function renderChartEmbed(Request $request, array $results, array $charts, string $pageTitle): ?Response
    {
        $chartId = $request->query->get('chart');

        if (empty($chartId)) {
            return null;
        }

        if (!isset($charts[$chartId])) {
            throw $this->createNotFoundException(sprintf('Le graphique "%s" est introuvable.', $chartId));
        }

        $config = $charts[$chartId];
        $data = null;

        if (isset($config['result_key'])) {
            $data = $results[$config['result_key']] ?? null;
            if ($data === null) {
                throw $this->createNotFoundException(sprintf(
                    'Aucune donnée n’est disponible pour le graphique "%s".',
                    $chartId
                ));
            }
        }

        if (($config['renderer'] ?? 'template') === 'map') {
            $chart = [
                'type' => 'map',
                'title' => $config['title'] ?? $pageTitle,
                'id' => $config['context']['id'] ?? $config['id'] ?? 'embedded_map',
                'data' => $data ?? [],
                'options' => $config['options'] ?? [],
            ];
        } else {
            if (!isset($config['template'])) {
                throw $this->createNotFoundException(sprintf('Modèle introuvable pour "%s".', $chartId));
            }

            $context = $config['context'] ?? [];

            if ($data !== null) {
                $key = $config['data_context_key'] ?? 'chart_data';
                $format = $config['data_format'] ?? 'json';
                $context[$key] = $format === 'raw' ? $data : json_encode($data);
            }

            $chart = [
                'type' => 'template',
                'title' => $config['title'] ?? $pageTitle,
                'template' => $config['template'],
                'context' => $context,
            ];
        }

        return $this->render('pages/page_embed_chart.html.twig', [
            'chart' => $chart,
        ]);
    }

    private function buildFilterArgs(
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

    private function summarizeFilters(
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
