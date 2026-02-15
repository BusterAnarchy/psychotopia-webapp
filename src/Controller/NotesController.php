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

final class NotesController extends AbstractController
{
    public function __construct(private readonly RRunnerCached $runner, private readonly FilterService $filterService) {}

    #[Route('/notes/purity/{slug}', name: 'app_notes_purity')]
    public function app_notes_purity(Request $request, #[MapEntity(expr: 'repository.findOneBySlug(slug)')] Molecule $molecule): Response
    {
        $unit = "pourcent";

        [$rRequest, $formLabel] = match ($molecule->getLabel()) {
            'Cannabis Résine' => [
                RRunner::builder()
                    ->forMolecule('cannabis')
                    ->withForms('resine'),
                'résine',
            ],
            'Cannabis Herbe' => [
                RRunner::builder()
                    ->forMolecule('cannabis')
                    ->withForms('herbe'),
                'herbe',
            ],
            '2C-B' => [
                RRunner::builder()
                    ->forMolecule('2c-b')
                    ->withForms('cristal'),
                'cristaux',
            ],
            'MDMA' => [
                RRunner::builder()
                    ->forMolecule('mdma')
                    ->withForms('cristal'),
                'cristaux',
            ],
            default => [
                RRunner::builder()
                    ->forMolecule($molecule->getLabel())
                    ->withForms('cristal'),
                'cristaux',
            ],
        };

        $rRequest = $rRequest
            ->addOption("-np")
            ->addAnalysis('count')
            ->addAnalysis('average', ['unit' => $unit])
            ->addAnalysis('variability_factor', ['unit' => $unit]);
            

        $results = $this->runner->run($rRequest);

        return $this->render('pages/page_embed_notes.html.twig', [
            'molecule' => $molecule,
            'results' => $results,
            'unit' => $unit,
            'form_label' => $formLabel,
        ]);
    }

    #[Route('/notes/tablets/{slug}', name: 'app_notes_tablets')]
    public function app_notes_tablets(Request $request, #[MapEntity(expr: 'repository.findOneBySlug(slug)')] Molecule $molecule): Response
    {
        $unit = "poids";

        $formLabel = 'comprimé';
        $rRequest = RRunner::builder()
            ->forMolecule($molecule->getLabel())
            ->withForms('comprime');

        $rRequest = $rRequest
            ->addOption("-np")
            ->addAnalysis('count')
            ->addAnalysis('average', ['unit' => $unit])
            ->addAnalysis('variability_factor', ['unit' => $unit]);
            

        $results = $this->runner->run($rRequest);

        return $this->render('pages/page_embed_notes.html.twig', [
            'molecule' => $molecule,
            'results' => $results,
            'unit' => $unit,
            'form_label' => $formLabel,
        ]);
    }
}
