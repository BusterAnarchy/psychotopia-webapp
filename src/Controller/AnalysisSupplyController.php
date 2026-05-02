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

final class AnalysisSupplyController extends AbstractController
{
    public function __construct(private readonly RRunnerCached $runner, private readonly FilterService $filterService,  private readonly TranslatorInterface $translator) {}

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

        foreach ($results["histo_supply"]["labels"] as $key => $value){
            if (preg_match('/^(.*?)(\s+\([^)]*\))$/', $value, $matches)) {
                $results["histo_supply"]["labels"][$key] = $this->translator->trans($matches[1]) . $matches[2];
                continue;
            }

            $results["histo_supply"]["labels"][$key] = $this->translator->trans($value);
        }

        foreach ($results["temporal_supply"]["datasets_area"] as $key => $value){
            $results["temporal_supply"]["datasets_area"][$key]["label"] = $this->translator->trans($value["label"]);
        }

        return $this->render('pages/page_supply.html.twig', [
            'page_title' => 'Supply',
            'results' => $results,
            'filters_summary' => $this->filterService->summarizeFilters($request, includeFamilies: true, includeForms: true),
        ]);
    }
}
