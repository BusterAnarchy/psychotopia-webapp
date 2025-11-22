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
        return $this->render('analysis/molecules.html.twig', [
            'controller_name' => 'Molecule',
            'all_molecules_data_count' => $runner->run([ 'count' ]),
            'all_molecules_data'=> $runner->run([ 'histo_count' ]),
            'area_all_molecules_data' => "[]",
            'map_data_abs' => "[]",
            'map_data_abs_color' => "[]",
            'map_data_prop' => "[]",
            'map_data_prop_color' => "[]",
            'conso_all_molecules_data' => "[]",
            'page_title' => 'Analyses'
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
