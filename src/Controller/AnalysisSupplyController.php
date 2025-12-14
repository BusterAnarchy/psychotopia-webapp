<?php

namespace App\Controller;

use App\Entity\Molecule;
use App\Service\RRunnerCached;
use App\Service\FilterService;
use App\Service\RRunner;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AnalysisSupplyController extends AbstractController
{
    public function __construct(private readonly RRunnerCached $runner, private readonly FilterService $filterService) {}

    #[Route('/supply', name: 'app_supply')]
    public function app_supply(Request $request): Response
    {
        $filters = $this->filterService->buildFilterArgs($request, includeFamilies: true, includeForms: true);
        $results = $this->runner->run(
            RRunner::builder()
                ->withFilters($filters)
                ->addAnalysis('histo_supply')
                ->addAnalysis('temporal_supply')
        );

        return $this->render('pages/page_supply.html.twig', [
            'page_title' => 'Supply',
            'results' => $results,
            'filters_summary' => $this->filterService->summarizeFilters($request, includeFamilies: true, includeForms: true),
        ]);
    }
}
