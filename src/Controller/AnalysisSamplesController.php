<?php

namespace App\Controller;

use App\Service\RRunnerCached;
use App\Service\FilterService;
use App\Service\RRunner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use Symfony\Contracts\Translation\TranslatorInterface;

final class AnalysisSamplesController extends AbstractController
{
    public function __construct(private readonly RRunnerCached $runner, private readonly FilterService $filterService,  private readonly TranslatorInterface $translator) {}

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

        foreach ($results["histo_count"]["labels"] as $key => $value){
            if (preg_match('/^(.*?)(\s+\([^)]*\))$/', $value, $matches)) {
                $results["histo_count"]["labels"][$key] = $this->translator->trans($matches[1]) . $matches[2];
                continue;
            }

            $results["histo_count"]["labels"][$key] = $this->translator->trans($value);
        }

        foreach ($results["temporal_count_abs"]["datasets_area"] as $key => $value){
            $results["temporal_count_abs"]["datasets_area"][$key]["label"] = $this->translator->trans($value["label"]);
        }

        foreach ($results["temporal_count_prop"]["datasets_area"] as $key => $value){
            $results["temporal_count_prop"]["datasets_area"][$key]["label"] = $this->translator->trans($value["label"]);
        }

        return $this->render('pages/page_samples.html.twig', [
            'page_title' => 'Échantillons',
            'results' => $results,
            'filters_summary' => $this->filterService->summarizeFilters($request, includeFamilies: true, includeForms: true),
        ]);
    }
}
