<?php

namespace App\Controller;

use App\Service\RRunnerCached;
use App\Service\RRunner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


final class EstimatorController extends AbstractController
{   public function __construct(private readonly RRunnerCached $runner) {}
    #[Route('/estimator/mdma', name: 'app_estimator_mdma')]
    public function appEstimatorMDMA(): Response
    {  
        $results = $this->runner->run(
            RRunner::builder()
                ->forMolecule('mdma')
                ->withForms('comprime')
                ->addAnalysis('mass_reg_purity')
        );
        return $this->render('pages/page_estimator_mdma.html.twig', [
            'results' => $results,
            'controller_name' => 'Estimateur',
        ]);
    }

    #[Route('/estimator/purity', name: 'app_estimator_purity')]
    public function appEstimatorPurity(): Response
    {  
        $results = $this->runner->run(
            RRunner::builder()
                ->forMolecule('mdma')
                ->withForms('comprime')
                ->addAnalysis('mass_reg_purity')
        );
        return $this->render('pages/page_estimator_purity.html.twig', [
            'results' => $results,
            'controller_name' => 'Estimateur',
        ]);
    }
}
