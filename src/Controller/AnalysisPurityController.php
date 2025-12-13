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

final class AnalysisPurityController extends AbstractController
{
    public function __construct(private readonly RRunnerCached $runner, private readonly FilterService $filterService) {}

    #[Route('/content/{slug}', name: 'app_content')]
    #[Route('/purity/{slug}', name: 'app_purity')]
    public function app_purity(Request $request, #[MapEntity(expr: 'repository.findOneBySlug(slug)')] Molecule $molecule): Response
    {
        $unit = "pourcent";
        $delta = $this->filterService->resolveDelta($request);
        
        $filters = $this->filterService->buildFilterArgs($request, includeNoCut: true);

        $rRequest = match ($molecule->getLabel()) {
            'Cannabis Résine' => RRunner::builder()
                    ->forMolecule('Cannabis (THC/CBD)')
                    ->withForms('Résine'),

            'Cannabis Herbe' => RRunner::builder()
                    ->forMolecule('Cannabis (THC/CBD)')
                    ->withForms('Herbe'),

            '2C-B' => RRunner::builder()
                    ->forMolecule('2C-B')
                    ->withForms('Poudre,Cristal'),

            'MDMA' =>RRunner::builder()
                    ->forMolecule('MDMA')
                    ->withForms('Poudre,Cristal'),

            default => 
                RRunner::builder()
                    ->forMolecule($molecule->getLabel())
        };

        $rRequest = $rRequest
            ->withFilters($filters)
            ->addOption("-nip")
            ->addAnalysis('count')
            ->addAnalysis('histo_purity', ['label' => 'histo_purity', 'unit' => $unit])
            ->addAnalysis('temporal_purity', ['label' => 'temporal_purity_avg', 'mode' => 'avg', 'delta' => $delta, 'unit' => $unit])
            ->addAnalysis('temporal_purity', ['label' => 'temporal_purity_med', 'mode' => 'med', 'delta' => $delta, 'unit' => $unit])
            ->addAnalysis('supply_reg_purity')
            ->addAnalysis('geo_purity')
            ->addAnalysis('geo_reg_purity');
            

        $results = $this->runner->run($rRequest);

        $results["histo_purity"]["ratio_base_sel"] = $molecule->getRatioBaseSel();

        return $this->render('pages/page_purity.html.twig', [
            'molecule' => $molecule,
            'results' => $results,
            'unit' => $unit,
            'delta' => $delta,
            'filters_summary' => $this->filterService->summarizeFilters($request, includeDelta: true, includeNoCut: true),
        ]);
    }
}
