<?php

namespace App\Controller;

use App\Service\RRunnerCached;
use App\Service\FilterService;
use App\Service\RRunner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AnalysisSamplesController extends AbstractController
{
    public function __construct(private readonly RRunnerCached $runner, private readonly FilterService $filterService) {}

    #[Route('/samples', name: 'app_samples')]
    public function app_samples(Request $request): Response
    {
        $filters = $this->filterService->buildFilterArgs($request, includeFamilies: true, includeForms: true);
        $results = $this->runner->run(
            RRunner::builder()
                ->withFilters($filters)
                ->addAnalysis('count')
                ->addAnalysis('histo_count')
                ->addAnalysis('temporal_count', ['label' => 'temporal_count_abs', 'scale' => 'abs'])
                ->addAnalysis('temporal_count', ['label' => 'temporal_count_prop', 'scale' => 'prop'])
                ->addAnalysis('geo_count', ['label' => 'geo_count_abs', 'scale' => 'abs'])
                ->addAnalysis('geo_count', ['label' => 'geo_count_prop', 'scale' => 'prop'])
                ->addAnalysis('pie_consumption')
        );

        return $this->render('pages/page_samples.html.twig', [
            'page_title' => 'Toutes molécules',
            'results' => $results,
            'filters_summary' => $this->filterService->summarizeFilters($request, includeFamilies: true, includeForms: true),
        ]);
    }
}
