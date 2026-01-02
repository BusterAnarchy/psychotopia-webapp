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

final class AnalysisPurityTabletsController extends AbstractController
{
    public function __construct(private readonly RRunnerCached $runner, private readonly FilterService $filterService) {}


    #[Route('/purity/tablets-{slug}', name: 'app_purity_tablets', priority: 1)]
    public function app_purity_tablets(Request $request, #[MapEntity(expr: 'repository.findOneBySlug(slug)')] Molecule $molecule): Response
    {
        $unit = "poids";
        $delta = $this->filterService->resolveDelta($request);
        $filters = $this->filterService->buildFilterArgs($request, includeNoCut: true);

        $results = $this->runner->run(
            RRunner::builder()
                ->withFilters($filters)
                ->forMolecule($molecule->getLabel())
                ->withForms('comprime')
                ->addAnalysis('count')
                ->addAnalysis('histo_purity', ['unit' => $unit])
                ->addAnalysis('temporal_purity', ['label' => 'temporal_purity_avg', 'mode' => 'avg', 'delta' => $delta, 'unit' => $unit])
                ->addAnalysis('temporal_purity', ['label' => 'temporal_purity_med', 'mode' => 'med', 'delta' => $delta, 'unit' => $unit])
                ->addAnalysis('mass_reg_purity')
        );

        return $this->render('pages/page_purity_tablets.html.twig', [
            'molecule' => $molecule,
            'results' => $results,
            'unit' => $unit,
            'delta' => $delta,
            'filters_summary' => $this->filterService->summarizeFilters($request, includeDelta: true, includeNoCut: true),
        ]);
    }
}
