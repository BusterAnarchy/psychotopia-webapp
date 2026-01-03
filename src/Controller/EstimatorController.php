<?php

namespace App\Controller;

use App\Form\EstimatorConsumptionType;
use App\Form\EstimatorMdmaType;
use App\Form\EstimatorPurityType;
use App\Service\RRunnerCached;
use App\Service\RRunner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


final class EstimatorController extends AbstractController
{
    public function __construct(private readonly RRunnerCached $runner) {}

    #[Route('/estimator/mdma', name: 'app_estimator_mdma')]
    public function appEstimatorMDMA(Request $request): Response
    {  
        $form = $this->createForm(EstimatorMdmaType::class);
        $form->handleRequest($request);

        $results = $this->runner->run(
            RRunner::builder()
                ->forMolecule('mdma')
                ->withForms('comprime')
                ->addAnalysis('mass_reg_purity')
        );
        
        $estimate = null;
        if ($form->isSubmitted()) {
            $mass = $form->get('mass')->getData();
            $estimate = $this->computeMdmaEstimate($mass, $results['mass_reg_purity'] ?? []);
        }

        return $this->render('pages/page_estimator_mdma.html.twig', [
            'results' => $results,
            'form' => $form->createView(),
            'estimate' => $estimate,
            'controller_name' => 'Estimateur',
        ]);
    }

    #[Route('/estimator/purity', name: 'app_estimator_purity')]
    public function appEstimatorPurity(Request $request): Response
    {  
        $estimate = null;
        $moleculeLabel = null;
        $showSupply = false;

        $form = $this->createForm(EstimatorPurityType::class, null, [
            'show_supply' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $moleculeLabel = $form->get('molecule')->getData();
            if ($moleculeLabel) {
                $showSupply = $this->getMoleculeSampleCount($moleculeLabel) > 200;
            }

            if ($showSupply) {
                $form = $this->createForm(EstimatorPurityType::class, null, [
                    'show_supply' => true,
                ]);
                $form->handleRequest($request);
            }

            $supplyLabel = $showSupply && $form->has('supply')
                ? $form->get('supply')->getData()
                : null;

            if ($moleculeLabel) {
                $estimate = $this->computePurityEstimate($moleculeLabel, $supplyLabel);
            }
        }

        return $this->render('pages/page_estimator_purity.html.twig', [
            'form' => $form->createView(),
            'estimate' => $estimate,
            'molecule_label' => $moleculeLabel,
            'controller_name' => 'Estimateur',
        ]);
    }

    #[Route('/ma-conso', name: 'app_estimator_consumption')]
    public function appEstimatorConsumption(Request $request): Response
    {
        $form = $this->createForm(EstimatorConsumptionType::class);
        $form->handleRequest($request);

        $selection = [
            'molecule' => null,
            'form_type' => null,
            'supply' => null,
        ];
        $estimate = null;
        $estimateType = null;
        $needsMass = false;

        if ($form->isSubmitted()) {
            $selection['molecule'] = $form->get('molecule')->getData();
            $selection['form_type'] = $form->get('form_type')->getData();
            $selection['supply'] = $form->get('supply')->getData();
            $mass = $form->get('mass')->getData();

            if ($selection['molecule'] && !in_array($selection['molecule'], ['mdma', '2c-b'], true)) {
                $selection['form_type'] = 'cristal';
            }

            if ($selection['form_type'] === 'comprime') {
                $estimateType = 'comprime';
                $needsMass = $mass === null;
                if ($mass !== null) {
                    $targetMolecule = $selection['molecule'] ?? 'mdma';
                    $results = $this->runner->run(
                        RRunner::builder()
                            ->forMolecule($targetMolecule)
                            ->withForms('comprime')
                            ->addAnalysis('mass_reg_purity')
                            ->addAnalysis('count')
                    );
                    $estimate = $this->computeMdmaEstimate($mass, $results['mass_reg_purity'] ?? []);
                    $estimate['mass_reg_purity'] = $results['mass_reg_purity'];
                    $estimate['count'] = $results['count'];
                }
            } elseif ($selection['molecule']) {
                $estimateType = 'cristal';
                $supply = $selection['supply'] === 'all' ? null : $selection['supply'];
                $estimate = $this->computePurityEstimate($selection['molecule'], $supply);
            }
        }

        return $this->render('pages/page_estimator_conso.html.twig', [
            'form' => $form->createView(),
            'selection' => $selection,
            'estimate' => $estimate,
            'estimate_type' => $estimateType,
            'needs_mass' => $needsMass,
            'submitted' => $form->isSubmitted(),
            'controller_name' => 'Estimateur',
        ]);
    }

    private function computeMdmaEstimate(?float $mass, array $data): ?array
    {
        if ($mass === null) {
            return null;
        }

        if (!isset($data['coef'][0], $data['coef'][1])) {
            return null;
        }

        $origin = (float) $data['coef'][0];
        $coef = (float) $data['coef'][1];
        $value = ($mass * $coef) + $origin;

        $lowLim = null;
        $upLim = null;

        $lowDataset = $data['datasets'][2]['data'] ?? null;
        $upDataset = $data['datasets'][3]['data'] ?? null;
        if (is_array($lowDataset) && is_array($upDataset)) {
            $xs = [];
            foreach ($lowDataset as $point) {
                if (is_array($point) && array_key_exists('x', $point)) {
                    $xs[] = (float) $point['x'];
                }
            }

            if ($xs !== []) {
                $minX = min($xs);
                $maxX = max($xs);
                if ($mass > $minX && $mass < $maxX) {
                    $closestIndex = null;
                    $closestDiff = null;
                    foreach ($lowDataset as $index => $point) {
                        if (!is_array($point) || !array_key_exists('x', $point)) {
                            continue;
                        }
                        $diff = abs(((float) $point['x']) - $mass);
                        if ($closestDiff === null || $diff < $closestDiff) {
                            $closestDiff = $diff;
                            $closestIndex = $index;
                        }
                    }

                    if ($closestIndex !== null) {
                        $lowLim = $lowDataset[$closestIndex]['y'] ?? null;
                        $upLim = $upDataset[$closestIndex]['y'] ?? null;
                    }
                }
            }
        }

        return [
            'value' => $value,
            'low_lim' => $lowLim,
            'up_lim' => $upLim,
        ];
    }

    private function getMoleculeSampleCount(string $molecule): int
    {
        $results = $this->runner->run(
            RRunner::builder()
                ->forMolecule($molecule)
                ->withForms('cristal')
                ->addAnalysis('count')
        );

        return (int) ($results['count'] ?? 0);
    }

    private function computePurityEstimate(string $molecule, ?string $supply): array
    {
        $builder = RRunner::builder()
            ->forMolecule($molecule)
            ->withForms('cristal');

        if ($supply) {
            $builder->withSupply($supply);
        }

        $estimate = $this->runner->run(
            $builder
                ->addAnalysis('pred_interval')
                ->addAnalysis('count')
                ->addAnalysis('temporal_purity', ['label' => 'temporal_purity_avg', 'mode' => 'avg', 'delta' => 15, 'unit' => "pourcent"])
        );

        return $estimate;
    }
}
