<?php

namespace App\Controller;

use App\Service\RRunnerCached;
use App\Service\RRunner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


final class EstimatorController extends AbstractController
{   public function __construct(private readonly RRunnerCached $runner) {}
    #[Route('/estimator', name: 'app_estimator')]
    public function appEstimator(): Response
    {  
        $results = $this->runner->run(
            RRunner::builder()
                ->forMolecule('mdma')
                ->withForms('comprime')
                ->addAnalysis('mass_reg_purity')
        );
        return $this->render('pages/page_estimator.html.twig', [
            'results' => $results,
            'controller_name' => 'Estimateur',
        ]);
    }
}
