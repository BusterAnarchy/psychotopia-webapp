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

use Symfony\Contracts\Translation\TranslatorInterface;

final class AnalysisPurityController extends AbstractController
{
    public function __construct(private readonly RRunnerCached $runner, private readonly FilterService $filterService, private readonly TranslatorInterface $translator ) {}

    #[Route('/content/{slug}', name: 'app_content')]
    #[Route('/purity/{slug}', name: 'app_purity')]
    public function app_purity(Request $request, #[MapEntity(expr: 'repository.findOneBySlug(slug)')] Molecule $molecule): Response
    {
        $unit = "pourcent";
        $delta = $this->filterService->resolveDelta($request);
        
        $filters = $this->filterService->buildFilterArgs($request, includeNoCut: true);

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

        foreach ($results["supply_reg_purity"]["data"] as $key => $value){
            $results["supply_reg_purity"]["data"][$key]["label"] = $this->translator->trans($value["label"]);
        }

        return $this->render('pages/page_purity.html.twig', [
            'molecule' => $molecule,
            'results' => $results,
            'unit' => $unit,
            'delta' => $delta,
            'filters_summary' => $this->filterService->summarizeFilters($request, includeDelta: true, includeNoCut: true),
        ]);
    }
}
