<?php

namespace App\Controller;

use App\Entity\Molecule;
use App\Service\CachedRRunner;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AnalysisController extends AbstractController
{
    public function __construct(private readonly CachedRRunner $runner) {}

    #[Route('/molecules', name: 'app_molecules')]
    public function app_molecules(): Response
    {
        $results = $this->runner->run([ 
            'count', 
            'histo_count', 
            'temporal_count:label=temporal_count_abs,scale=abs',
            'temporal_count:label=temporal_count_prop,scale=prop', 
            'geo_count:label=geo_count_abs,scale=abs',
            'geo_count:label=geo_count_prop,scale=prop',
            'pie_consumption'
        ]);

        return $this->render('analysis/molecules.html.twig', [
            'page_title' => 'Toutes molécules',
            'results' => $results,
        ]);
    }

    #[Route('/supply', name: 'app_supply')]
    public function app_supply(): Response
    {
        $results = $this->runner->run([ 
            'histo_supply',
            'temporal_supply',
        ]);

        return $this->render('analysis/supply.html.twig', [
            'page_title' => 'Supply',
            'results' => $results,
        ]);
    }

    #[Route('/purity/{slug}', name: 'app_purity')]
    public function app_purity(#[MapEntity(expr: 'repository.findOneBySlug(slug)')] Molecule $molecule): Response
    {
        $delta = 15;
        $unit = "pourcent";

        $analysis = [
            "-nip", 
            'count', 
            "histo_purity:label=histo_purity,unit=$unit", 
            "temporal_purity:label=temporal_purity_avg,mode=avg,delta=$delta",
            "temporal_purity:label=temporal_purity_med,mode=med,delta=$delta", 
            'supply_reg_purity',
            'geo_purity',
            'geo_reg_purity',
        ];

        $results = match ($molecule->getLabel()) {
            'THC-resin' => $this->runner->run(array_merge(["-m 'Cannabis (THC/CBD)'", "--form Résine"], $analysis)),
            'THC-weed' => $this->runner->run(array_merge(["-m 'Cannabis (THC/CBD)'", "--form Herbe"], $analysis)),
            '2C-B' => $this->runner->run(array_merge(["-m 2C-B", "--form Poudre,Cristal"], $analysis)),
            default => $this->runner->run(array_merge(["-m ". $molecule->getLabel()], $analysis)),
        };

        $results["histo_purity"]["ratio_base_sel"] = $molecule->getRatioBaseSel();

        return $this->render('analysis/purity.html.twig', [
            'molecule_name' => $molecule->getLabel(),
            'presentation' => $molecule->getDefinition(),
            'url_wiki' => $molecule->getWikiUrl(),
            'results' => $results,
            'unit' => $unit,
            'delta' => $delta,
        ]);
    }

    #[Route('/purity/tablets-{slug}', name: 'app_purity_tablets', priority: 1)]
    public function app_purity_tablets(#[MapEntity(expr: 'repository.findOneBySlug(slug)')] Molecule $molecule): Response
    {
        $delta = 15;
        $unit = "poids";

        $results = $this->runner->run([
            "-m " . $molecule->getLabel(),
            "--form comprimé",
            "-pt",
            "--tablet_mass",
            'count',
            "histo_purity:unit=$unit",
            "temporal_purity:label=temporal_purity_avg,mode=avg,delta=$delta",
            "temporal_purity:label=temporal_purity_med,mode=med,delta=$delta",
            'mass_reg_purity',
        ]);

        return $this->render('analysis/purity_tablets.html.twig', [
            'molecule_name' => $molecule->getLabel(),
            'presentation' => $molecule->getDefinition(),
            'url_wiki' => $molecule->getWikiUrl(),
            'results' => $results,
            'unit' => $unit,
            'delta' => $delta,
        ]);
    }

    #[Route('/cut/{slug}', name: 'app_cut')]
    public function app_cut(#[MapEntity(expr: 'repository.findOneBySlug(slug)')] Molecule $molecule): Response
    {
        $delta = 15;
        $unit = "pourcent";

        $analysis = ["-m " . $molecule->getLabel(), 'count', 'count_cut_agents', 'histo_cut_agents','temporal_cut_agents'];

        $results = match ($molecule->getLabel()) {
            '3-MMC' => $this->runner->run([
                "-m 3-MMC", 
                'count',
                'count_cut_agents_3MMC:label=count_cut_agents',
                'histo_cut_agents_3MMC:label=histo_cut_agents',
                'temporal_cut_agents_3MMC:label=temporal_cut_agents'
            ]),
            default => $this->runner->run($analysis),
        };

        return $this->render('analysis/cut_agents.html.twig', [
            'molecule_name' => $molecule->getLabel(),
            'presentation' => $molecule->getDefinition(),
            'url_wiki' => $molecule->getWikiUrl(),
            'results' => $results,
            'unit' => $unit,
            'delta' => $delta,
            'data_reg_dose_poids' => NULL,
        ]);
    }

    #[Route('/sub-products/{slug}', name: 'app_sub_products')]
    public function app_sub_products(#[MapEntity(expr: 'repository.findOneBySlug(slug)')] Molecule $molecule): Response
    {
        $delta = 15;
        $unit = "pourcent";

        $results = $this->runner->run([
            "-m " . $molecule->getLabel(), 
            'count',
            'histo_sub_products',
            'temporal_sub_products'
        ]);

        return $this->render('analysis/sub_products.html.twig', [
            'molecule_name' => $molecule->getLabel(),
            'presentation' => $molecule->getDefinition(),
            'url_wiki' => $molecule->getWikiUrl(),
            'results' => $results,
            'unit' => $unit,
            'delta' => $delta,
            'data_reg_dose_poids' => NULL,
        ]);
    }
}
