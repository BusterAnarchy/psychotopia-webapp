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

final class AnalysisCutAgentsController extends AbstractController
{
    public function __construct(private readonly RRunnerCached $runner, private readonly FilterService $filterService) {}

    #[Route('/cut/{slug}', name: 'app_cut')]
    public function app_cut(Request $request, #[MapEntity(expr: 'repository.findOneBySlug(slug)')] Molecule $molecule): Response
    {
        $delta = 15;
        $unit = "pourcent";

        $filters = $this->filterService->buildFilterArgs($request, includeNoCut: true);

        $results = match ($molecule->getLabel()) {
            '3-MMC' => $this->runner->run(
                RRunner::builder()
                    ->withFilters($filters)
                    ->forMolecule('3-MMC')
                    ->addAnalysis('count')
                    ->addAnalysis('count_cut_agents_3MMC', ['label' => 'count_cut_agents'])
                    ->addAnalysis('histo_cut_agents_3MMC', ['label' => 'histo_cut_agents'])
                    ->addAnalysis('temporal_cut_agents_3MMC', ['label' => 'temporal_cut_agents'])
            ),
            default => $this->runner->run(
                RRunner::builder()
                    ->withFilters($filters)
                    ->forMolecule($molecule->getLabel())
                    ->addAnalysis('count')
                    ->addAnalysis('count_cut_agents')
                    ->addAnalysis('histo_cut_agents')
                    ->addAnalysis('temporal_cut_agents')
            ),
        };

        return $this->render('pages/page_cut_agents.html.twig', [
            'molecule' => $molecule,
            'results' => $results,
            'unit' => $unit,
            'delta' => $delta,
            'data_reg_dose_poids' => NULL,
            'filters_summary' => $this->filterService->summarizeFilters($request, includeNoCut: true),
        ]);
    }
}
