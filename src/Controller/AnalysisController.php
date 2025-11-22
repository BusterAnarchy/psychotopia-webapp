<?php

namespace App\Controller;

use App\Service\RRunner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AnalysisController extends AbstractController
{
    #[Route('/molecules', name: 'app_molecules')]
    public function app_molecules(RRunner $runner): Response
    {
        $results = $runner->run([ 
            'count', 
            'histo_count', 
            'temporal_count:label=temporal_count_abs,scale=abs',
            'temporal_count:label=temporal_count_prop,scale=prop', 
            'geo_count:label=geo_count_abs,scale=abs',
            'geo_count:label=geo_count_prop,scale=prop',
        ]);

        return $this->render('analysis/molecules.html.twig', [
            'controller_name' => 'Molecule',
            'page_title' => 'Analyses',
            'results' => $results,
        ]);
    }

    #[Route('/supply', name: 'app_supply')]
    public function app_supply(): Response
    {
        return $this->render('analysis/index.html.twig', [
            'controller_name' => 'Supply',
        ]);
    }

    #[Route('/purity-{molecule}', name: 'app_purity')]
    public function app_purity(string $molecule): Response
    {
        return $this->render('analysis/index.html.twig', [
            'controller_name' => 'Purity ' . $molecule,
        ]);
    }

    #[Route('/cut-{molecule}', name: 'app_cut')]
    public function app_cut(string $molecule): Response
    {
        return $this->render('analysis/index.html.twig', [
            'controller_name' => 'Cut ' . $molecule,
        ]);
    }
}
