<?php

namespace App\Controller;

use App\Service\RRunner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AnalysisController extends AbstractController
{
    public function __construct(private readonly RRunner $runner) {}

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

    #Dictionary for the titles of each page
    private array $dict_title = [
    'All molecules' => "Analyse par type de molécules",
    'Supply' => "Analyse par mode d'approvisionnement",
    'Cocaïne' => "Statistiques sur la pureté de la cocaïne",
    'Cocaïne_coupe' => "Analyse des produits de coupe sur la cocaïne",
    'Héroïne' => "Statistiques sur la pureté de l'héroïne", 
    'Héroïne_coupe' => "Analyse des produits de coupe sur l'héroïne",
    'Héroïne_sous_produit' => "Analyse des sous-produits de la synthèse de l'héroïne",
    '3-MMC' => "Statistiques sur la pureté de la 3-MMC",
    '3-MMC_coupe' => "Analyse des produits de coupe sur la 3-MMC",
    'MDMA' => "Statistiques sur la pureté de la MDMA sous forme cristal/poudre",
    'Comprimés de MDMA' => "Statistiques sur la teneur en MDMA des cachets d'ecstasy",
    'Kétamine' => "Statistiques sur la pureté de la kétamine",
    'Speed' => "Statistiques sur la pureté du speed",
    'Speed_coupe' => "Analyse des produits de coupe sur le speed", 
    'Résine de Cannabis' => "Statistiques sur la teneur en THC de la résine de cannabis",
    'Herbe de Cannabis' => "Statistiques sur la teneur en THC des fleurs séchées de cannabis",
    '2C-B' => "Statistiques sur la pureté de la 2C-B",
    'Comprimés de 2C-B' => "Statistiques sur la teneur en 2C-B des comprimés"
    ];

    #Dictionary for the urls of the Psychowiki
    private array $dict_urls = [
    'Cocaïne' => 'https://www.psychoactif.org/psychowiki/index.php?title=Cocaine,_effets,_risques,_t%C3%A9moignages',
    'Héroïne' => 'https://www.psychoactif.org/psychowiki/index.php?title=H%C3%A9ro%C3%AFne,_effets,_risques,_t%C3%A9moignages',
    '3-MMC' => 'https://www.psychoactif.org/psychowiki/index.php?title=3-MMC,_effets,_risques,_t%C3%A9moignages',
    'MDMA' => 'https://www.psychoactif.org/psychowiki/index.php?title=Ecstasy-MDMA,_effets,_risques,_t%C3%A9moignages',
    'Comprimés de MDMA' => 'https://www.psychoactif.org/psychowiki/index.php?title=Ecstasy-MDMA,_effets,_risques,_t%C3%A9moignages',
    'Kétamine' => 'https://www.psychoactif.org/psychowiki/index.php?title=K%C3%A9tamine,_effets,_risques,_t%C3%A9moignages',
    'Speed' => 'https://www.psychoactif.org/psychowiki/index.php?title=Amph%C3%A9tamine-M%C3%A9thamph%C3%A9tamine,_effets,_risques,_t%C3%A9moignages',
    'Résine de Cannabis' => 'https://www.psychoactif.org/psychowiki/index.php?title=Cannabis,_effets,_risques,_t%C3%A9moignages',
    'Herbe de Cannabis' => 'https://www.psychoactif.org/psychowiki/index.php?title=Cannabis,_effets,_risques,_t%C3%A9moignages',
    '2C-B' => 'https://www.psychoactif.org/psychowiki/index.php?title=2C-B,_effets,_risques,_t%C3%A9moignages',
    'Comprimés de 2C-B' => 'https://www.psychoactif.org/psychowiki/index.php?title=2C-B,_effets,_risques,_t%C3%A9moignages'
    ];

    #Dictionary for the presentation of each substance
    private array $dict_pres = [
    'Cocaïne' => "La cocaïne est un produit psychoactif de la classe des stimulants du système nerveux central.
                Elle est issue de la feuille du cocaïer et se présente comme une poudre de couleur blanche scintillante.",
    'Héroïne' => "L'héroïne est un opiacé synthétisé à partir de la morphine naturellement présente dans l'opium (suc du pavot).
                Elle est surtout recherchée pour le bien être psychique et physique qu'elle procure.
                En France, elle se présente généralement sous la forme de poudre allant du beige clair au brun foncé.",
    '3-MMC' => "La 3-MMC est une molécule de synthèse de la famille des cathinones. Cette drogue psychostimulante et entactogène est 
                    apparue en 2011 et peut se présenter comme une poudre planche ou comme de petits cristaux blancs.",
    'MDMA' => "La MDMA est une molécule de synthèse de la famille des amphétamines et se présente sous deux formes => soit sous forme de cristaux/poudre
                translucide, soit sous forme de cachets de taille et de couleur variable appelés \"ecstasy\". Sur cette page, vous retrouverez l'analyse
                pour la forme cristal/poudre. L'analyse des cachets d'ecstasy possède aussi sa <a href='http://psychotopia.psychoactif.org/histo-comprime-mdma/' target='_blank'>page dédiée</a>.",
    'Comprimés de MDMA' => "La MDMA est une molécule de synthèse de la famille des amphétamines et se présente sous deux formes => soit sous forme de cristaux/poudre
                translucide, soit sous forme de cachets de taille et de couleur variable appelés \"ecstasy\". Sur cette page, vous retrouverez l'analyse
                pour les cachets d'ecstasy. L'analyse des échantillons sous forme de cristal ou de poudre possède aussi sa <a href='http://psychotopia.psychoactif.org/purity-mdma/' target='_blank'>page dédiée</a>.",
    'Kétamine' => "La kétamine est une molécule de la famille des cycloalkylarylamines utilisée comme anesthésique général en médecine humaine et en médecine vétérinaire.
                Elle provoque une anesthésie dissociative (dissociation entre le cortex frontal et le reste du cerveau), ainsi que des possibles hallucinations lors de la période de réveil.
                    Elle se présente sous la forme d'une poudre cristalline ou d'un liquide incolore, inodore et sans saveur.",
    'Speed' => "Le \"speed\" est une appellation généraliste pour désigner principalement l'amphétamine et la méthamphétamine. Il s'agit
                d'une drogue euphorisante et stimulante qui peut se présenter sous la forme de poudre jaunâtre avec une forte odeur chimique, mais aussi sous la forme de cristaux ou de cachets. 
                L'analyse présentée ici se restreint aux formes poudre et cristal.",
    'Résine de Cannabis' => "Le cannabis est un genre botanique qui rassemble des plantes annuelles de la famille des Cannabaceae.
                            C'est le taux de THC présent dans chaque variété botanique qui détermine si elle est utilisée comme chanvre 
                            à usage agricole (taux faible) ou pour ses effets psychoactives (taux élevé). Ces effets sont variés et dépendants de la variété => citons entre autres 
                            euphorie, excitation, relaxation, augmentation des sensations, sommeil, ... Le cannabis se présente sous différentes formes dont les plus fréquentes sont
                            la fleur séchée et la résine. Sur cette page, vous retrouverez l'analyse de la résine de cannabis mais l'analyse des fleurs séchées possède aussi sa page dédiée.",
    'Herbe de Cannabis' => "Le cannabis est un genre botanique qui rassemble des plantes annuelles de la famille des Cannabaceae.
                            C'est le taux de THC présent dans chaque variété botanique qui détermine si elle est utilisée comme chanvre 
                            à usage agricole (taux faible) ou pour ses effets psychoactives (taux élevé). Ces effets sont variés et dépendants de la variété => citons entre autres 
                            euphorie, excitation, relaxation, augmentation des sensations, sommeil, ... Le cannabis se présente sous différentes formes dont les plus fréquentes sont
                            la fleur séchée et la résine. Sur cette page, vous retrouverez l'analyse de les fleurs séchées de cannabis mais l'analyse de la résine possède aussi sa page dédiée.",
    '2C-B' => "La 2C-B est une substance psychédélique synthétique de la classe des phénéthylamines recherchée pour ses  effets psychédéliques et entactogènes. Elle se présente sous la forme de poudre ou de comprimé.
                Sur cette page, vous retrouverez l'analyse
                pour la forme poudre. L'analyse des comprimés de 2C-B possède aussi sa <a href='http://psychotopia.psychoactif.org/histo-comprime-2cb/' target='_blank'>page dédiée</a>.",
    'Comprimés de 2C-B' => " La 2C-B est une substance psychédélique synthétique de la classe des phénéthylamines recherchée pour ses  effets psychédéliques et entactogènes. Elle se présente sous la forme de poudre ou de comprimé.
                            Sur cette page, vous retrouverez l'analyse
                            pour les comprimés de 2C-B. L'analyse des échantillons sous forme de poudre possède aussi sa <a href='http://psychotopia.psychoactif.org/purity-2cb/' target='_blank'>page dédiée</a>.",
    ];

    #[Route('/purity-{molecule}', name: 'app_purity')]
    public function app_purity(string $molecule): Response
    {
        $delta = 15;
        $unit = "pourcent";

        if ($molecule == "Cannabis") {
            $molecule = "'Cannabis (THC/CBD)'";
        }

        $results = $this->runner->run([
            "-m $molecule",
            "-nip", 
            'count', 
            'histo_purity', 
            "temporal_purity:label=temporal_purity_avg,mode=avg,delta=$delta",
            "temporal_purity:label=temporal_purity_med,mode=med,delta=$delta", 
            'supply_reg_purity',
            'geo_purity',
            'geo_reg_purity',
        ]);

        return $this->render('analysis/purity.html.twig', [
            'molecule_name' => $molecule,
            'results' => $results,
            'presentation' => $this->dict_pres[$molecule] ?? '',
            'url_wiki' => $this->dict_urls[$molecule] ?? '',
            'unit' => $unit,
            'delta' => $delta,
            'data_reg_dose_poids' => NULL,
        ]);
    }

    #[Route('/cut-{molecule}', name: 'app_cut')]
    public function app_cut(string $molecule): Response
    {
        $delta = 15;
        $unit = "pourcent";

        if ($molecule == "Cannabis") {
            $molecule = "'Cannabis (THC/CBD)'";
        }

        $results = $molecule !== '3-MMC' ? $this->runner->run([
            "-m $molecule", 
            'count',
            'count_cut_agents',
            'histo_cut_agents',
            'temporal_cut_agents'
        ]) : $this->runner->run([
            "-m $molecule", 
            'count',
            'count_cut_agents_3MMC:label=count_cut_agents',
            'histo_cut_agents_3MMC:label=histo_cut_agents',
            'temporal_cut_agents_3MMC:label=temporal_cut_agents'
        ]);

        return $this->render('analysis/cut_agents.html.twig', [
            'molecule_name' => $molecule,
            'results' => $results,
            'presentation' => $this->dict_pres[$molecule] ?? '',
            'url_wiki' => $this->dict_urls[$molecule] ?? '',
            'unit' => $unit,
            'delta' => $delta,
            'data_reg_dose_poids' => NULL,
        ]);
    }

    #[Route('/sub-products-{molecule}', name: 'app_sub_products')]
    public function app_sub_products(string $molecule): Response
    {
        $delta = 15;
        $unit = "pourcent";

        $results = $this->runner->run([
            "-m $molecule", 
            'count',
            'histo_sub_products',
            'temporal_sub_products'
        ]);

        return $this->render('analysis/sub_products.html.twig', [
            'molecule_name' => $molecule,
            'results' => $results,
            'presentation' => $this->dict_pres[$molecule] ?? '',
            'url_wiki' => $this->dict_urls[$molecule] ?? '',
            'unit' => $unit,
            'delta' => $delta,
            'data_reg_dose_poids' => NULL,
        ]);
    }
}
