<?php

namespace App\Controller;

use App\Repository\MoleculeRepository;
use App\Service\RRunner;
use App\Service\RRunnerCached;
use App\Service\FilterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AnalysisEmbeddedController extends AbstractController
{
    public function __construct(
        private readonly RRunnerCached $runner,
        private readonly FilterService $filterService,
        private readonly MoleculeRepository $moleculeRepository
    ) {}

    private $charts = [
        'samples_distribution' => [
            'title' => 'Répartition par molécule',
            'template' => 'components/charts/chart_pie.html.twig',
            'analysis' => 'histo_count',
            'result_key' => 'histo_count',
            'context' => ['id' => 'embedded_chart'],
        ],
        'samples_first_consumption' => [
            'title' => 'Proportion après première consommation',
            'template' => 'components/charts/chart_pie.html.twig',
            'analysis' => 'pie_consumption',
            'result_key' => 'pie_consumption',
            'context' => ['id' => 'embedded_chart'],
        ],
        'samples_timeline_absolute' => [
            'title' => "Évolution temporelle — Nombre d'échantillons",
            'template' => 'components/charts/chart_area_stacked.html.twig',
            'analysis' => 'temporal_count:label=temporal_count_abs,scale=abs',
            'result_key' => 'temporal_count_abs',
            'context' => [
                'id' => 'embedded_chart',
                'is_stacked' => 'true',
                'mode' => 'absolu',
            ],
        ],
        'samples_timeline_relative' => [
            'title' => "Évolution temporelle — Proportion",
            'template' => 'components/charts/chart_area_stacked.html.twig',
            'analysis' => 'temporal_count:label=temporal_count_prop,scale=prop',
            'result_key' => 'temporal_count_prop',
            'context' => [
                'id' => 'embedded_chart',
                'is_stacked' => 'true',
                'mode' => 'relatif',
            ],
        ],
        'samples_map_absolute' => [
            'title' => "Carte — Nombre d'échantillons par région",
            'renderer' => 'map',
            'analysis' => 'geo_count:label=geo_count_abs,scale=abs',
            'result_key' => 'geo_count_abs',
            'context' => [
                'id' => 'embedded_map',
                'options' => [
                    'start_hsl' => [120, 60, 85],
                    'end_hsl' => [200, 100, 30],
                ]
            ],
        ],
        'samples_map_relative' => [
            'title' => "Carte — Échantillons par million d'habitants",
            'renderer' => 'map',
            'analysis' => 'geo_count:label=geo_count_prop,scale=prop',
            'result_key' => 'geo_count_prop',
            'context' => [
                'id' => 'embedded_map',
                'options' => [
                    'start_hsl' => [50, 100, 70],
                    'end_hsl' => [0, 100, 40],
                ],
            ],
            
        ],
        'supply_distribution' => [
            'title' => "Répartition par voie d'approvisionnement",
            'template' => 'components/charts/chart_pie.html.twig',
            'analysis' => 'histo_supply',
            'result_key' => 'histo_supply',
            'context' => ['id' => 'embedded_chart'],
        ],
        'supply_timeline' => [
            'title' => "Évolution temporelle par voie d'approvisionnement",
            'template' => 'components/charts/chart_area_stacked.html.twig',
            'analysis' => 'temporal_supply',
            'result_key' => 'temporal_supply',
            'context' => [
                'id' => 'embedded_chart',
                'is_stacked' => 'true',
                'mode' => 'relatif',
            ],
        ],
        'purity_histogram' => [
            'title' => 'Histogramme de la pureté',
            'template' => 'components/charts/chart_bar_y.html.twig',
            'analysis' => '-nip histo_purity:unit=pourcent',
            'result_key' => 'histo_purity',
            'context' => [
                'id' => 'embedded_chart',
                'unit' => 'pourcent',
            ],
        ],
        'purity_temporal_mean' => [
            'title' => 'Évolution temporelle – Moyennes et écarts type',
            'template' => 'components/charts/chart_line.html.twig',
            'analysis' => '-nip temporal_purity:label=temporal_purity_avg,mode=avg,delta=15,unit=pourcent',
            'result_key' => 'temporal_purity_avg',
            'context' => [
                'id' => 'embedded_chart',
                'is_stacked' => 'false',
            ],
        ],
        'purity_temporal_median' => [
            'title' => 'Évolution temporelle – Médianes et quartiles',
            'template' => 'components/charts/chart_line.html.twig',
            'analysis' => '-nip temporal_purity:label=temporal_purity_med,mode=med,delta=15,unit=pourcent',
            'result_key' => 'temporal_purity_med',
            'context' => [
                'id' => 'embedded_chart',
                'is_stacked' => 'false',
            ],
        ],
        'purity_map' => [
            'title' => 'Carte de la pureté moyenne par région',
            'renderer' => 'map',
            'analysis' => '-nip geo_purity',
            'result_key' => 'geo_purity',
            'context' => [
                'id' => 'embedded_map',
                'options' => [
                    'start_hsl' => [120, 60, 85],
                    'end_hsl' => [200, 100, 30],
                ]
            ]
        ],
        'purity_tablets_histogram' => [
            'title' => 'Histogramme de la pureté',
            'template' => 'components/charts/chart_bar_y.html.twig',
            'analysis' => '-pt --tablet_mass histo_purity:unit=poids',
            'result_key' => 'histo_purity',
            'context' => [
                'id' => 'embedded_chart',
                'unit' => 'poids',
            ],
        ],
        'purity_tablets_temporal_mean' => [
            'title' => 'Évolution temporelle – Moyenne et écarts type',
            'template' => 'components/charts/chart_line.html.twig',
            'analysis' => '-pt --tablet_mass temporal_purity:label=temporal_purity_avg,mode=avg,delta=15,unit=poids',
            'result_key' => 'temporal_purity_avg',
            'context' => [
                'id' => 'embedded_chart',
                'is_stacked' => 'false',
            ],
        ],
        'purity_tablets_temporal_median' => [
            'title' => 'Évolution temporelle – Médianes et quartiles',
            'template' => 'components/charts/chart_line.html.twig',
            'analysis' => '-pt --tablet_mass temporal_purity:label=temporal_purity_med,mode=med,delta=15,unit=poids',
            'result_key' => 'temporal_purity_med',
            'context' => [
                'id' => 'embedded_chart',
                'is_stacked' => 'false',
            ],
        ],
        'purity_tablets_scatter' => [
            'title' => 'Quantité de substance active vs masse des comprimés',
            'template' => 'components/scatter_charts/chart_line.html.twig',
            'analysis' => '-pt --tablet_mass mass_reg_purity',
            'result_key' => 'mass_reg_purity',
            'context' => [
                'id' => 'embedded_chart',
            ],
        ],
        'cut_agents_share' => [
            'title' => 'Proportion des échantillons avec produits de coupe',
            'template' => 'components/charts/chart_pie.html.twig',
            'analysis' => 'count_cut_agents',
            'result_key' => 'count_cut_agents',
            'context' => [
                'id' => 'embedded_chart',
            ],
        ],
        'cut_agents_distribution' => [
            'title' => 'Proportion par produit de coupe',
            'template' => 'components/charts/chart_bar_x.html.twig',
            'analysis' => 'histo_cut_agents',
            'result_key' => 'histo_cut_agents',
            'context' => [
                'id' => 'embedded_chart',
            ],
        ],
        'cut_agents_timeline' => [
            'title' => 'Évolution temporelle des produits de coupe',
            'template' => 'components/charts/chart_area_stacked.html.twig',
            'analysis' => 'temporal_cut_agents',
            'result_key' => 'temporal_cut_agents',
            'context' => [
                'id' => 'embedded_chart',
                'is_stacked' => 'false',
                'mode' => 'relatif',
            ],
        ],
        'sub_product_distribution' => [
            'title' => 'Proportion par sous-produit',
            'template' => 'components/charts/chart_bar_x.html.twig',
            'analysis' => 'histo_sub_products',
            'result_key' => 'histo_sub_products',
            'context' => [
                'id' => 'embedded_chart',
            ],
        ],
        'sub_product_timeline' => [
            'title' => 'Évolution temporelle des sous-produits',
            'template' => 'components/charts/chart_area_stacked.html.twig',
            'analysis' => 'temporal_sub_products',
            'result_key' => 'temporal_sub_products',
            'context' => [
                'id' => 'embedded_chart',
                'is_stacked' => 'false',
                'mode' => 'relatif',
            ],
        ]
    ];

    #[Route('/embedded', name: 'app_embedded')]
    public function app_cut(Request $request): Response
    {
        $chartId = $request->query->get('chart');
        $slug = $request->query->get('molecule');

        if (empty($chartId)) {
            throw $this->createNotFoundException(sprintf(format: 'Aucun graphique demandé'));
        }

        if (!isset($this->charts[$chartId])) {
            throw $this->createNotFoundException(sprintf('Le graphique "%s" est introuvable.', $chartId));
        }

        $config = $this->charts[$chartId];

        $filters = $this->filterService->buildFilterArgs($request, includeFamilies: true, includeForms: true);

        $rRequest =  RRunner::builder();

        if (!empty($slug)) {

            $molecule = $this->moleculeRepository->findOneBy(['slug' => $slug]);
            if ($molecule) {

                $rRequest = match ($molecule->getLabel()) {
                    'Cannabis Résine' => RRunner::builder()
                            ->forMolecule('cannabis')
                            ->withForms('resine'),

                    'Cannabis Herbe' => RRunner::builder()
                            ->forMolecule('cannabis')
                            ->withForms('herbe'),

                    '2C-B' => RRunner::builder()
                            ->forMolecule('2c-b')
                            ->withForms('cristal'),

                    'MDMA' =>RRunner::builder()
                            ->forMolecule('mdma')
                            ->withForms('cristal'),

                    default => 
                        RRunner::builder()
                            ->forMolecule($molecule->getLabel())
                            ->withForms('cristal')
                };
            }
        }

        $rRequest = $rRequest
            ->withFilters($filters)
            ->addAnalysis($config['analysis'] ?? $config['result_key']);
            
        $results = $this->runner->run($rRequest);

        if ($results['histo_purity']) {
            $results["histo_purity"]["ratio_base_sel"] = $molecule->getRatioBaseSel();
        }

        $data = $results[$config['result_key']];

        if ($data === null) {
            throw $this->createNotFoundException(sprintf('Aucune donnée n’est disponible pour le graphique "%s".',$chartId));
        }

        $context = $config['context'] ?? [];
        $context['chart_data'] = $config['renderer'] === 'map' ? $data : json_encode($data);
        
        return $this->render('pages/page_embed_chart.html.twig', [
            'chart' => [
                'title' => $config['title'],
                'type' => $config['renderer'] ?? 'template',
                'template' => $config['template'] ?? null,
                'context' => $context,
            ]
        ]);
    }
}
