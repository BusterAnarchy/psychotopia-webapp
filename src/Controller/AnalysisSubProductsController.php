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

final class AnalysisSubProductsController extends AbstractController
{
    public function __construct(private readonly RRunnerCached $runner, private readonly FilterService $filterService) {}

    #[Route('/sub-products/{slug}', name: 'app_sub_products')]
    public function app_sub_products(Request $request, #[MapEntity(expr: 'repository.findOneBySlug(slug)')] Molecule $molecule): Response
    {
        $delta = 15;
        $unit = "pourcent";

        $filters = $this->filterService->buildFilterArgs($request, includeNoCut: true);

        $results = $this->runner->run(
            RRunner::builder()
                ->withFilters($filters)
                ->forMolecule($molecule->getLabel())
                ->addAnalysis('count')
                ->addAnalysis('histo_sub_products')
                ->addAnalysis('temporal_sub_products')
        );

        return $this->render('pages/page_sub_products.html.twig', [
            'molecule' => $molecule,
            'results' => $results,
            'unit' => $unit,
            'delta' => $delta,
            'data_reg_dose_poids' => NULL,
            'filters_summary' => $this->filterService->summarizeFilters($request, includeNoCut: true),
        ]);
    }
}
