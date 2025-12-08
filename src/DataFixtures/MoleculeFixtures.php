<?php

namespace App\DataFixtures;

use App\Entity\Molecule;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class MoleculeFixtures extends Fixture
{
    #Dictionary for the urls of the Psychowiki
    private array $wiki_urls = [
        'cocaine' => 'https://www.psychoactif.org/psychowiki/index.php?title=Cocaine,_effets,_risques,_t%C3%A9moignages',
        'heroine' => 'https://www.psychoactif.org/psychowiki/index.php?title=H%C3%A9ro%C3%AFne,_effets,_risques,_t%C3%A9moignages',
        '3-mmc' => 'https://www.psychoactif.org/psychowiki/index.php?title=3-MMC,_effets,_risques,_t%C3%A9moignages',
        'mdma' => 'https://www.psychoactif.org/psychowiki/index.php?title=Ecstasy-MDMA,_effets,_risques,_t%C3%A9moignages',
        'ketamine' => 'https://www.psychoactif.org/psychowiki/index.php?title=K%C3%A9tamine,_effets,_risques,_t%C3%A9moignages',
        'speed' => 'https://www.psychoactif.org/psychowiki/index.php?title=Amph%C3%A9tamine-M%C3%A9thamph%C3%A9tamine,_effets,_risques,_t%C3%A9moignages',
        'cannabis' => 'https://www.psychoactif.org/psychowiki/index.php?title=Cannabis,_effets,_risques,_t%C3%A9moignages',
        '2c-b' => 'https://www.psychoactif.org/psychowiki/index.php?title=2C-B,_effets,_risques,_t%C3%A9moignages',
    ];

    #Dictionary for the presentation of each substance
    private array $definitions = [
        'cocaine' => "La cocaïne est un produit psychoactif de la classe des stimulants du système nerveux central.
                    Elle est issue de la feuille du cocaïer et se présente comme une poudre de couleur blanche scintillante.",
        'heroine' => "L'héroïne est un opiacé synthétisé à partir de la morphine naturellement présente dans l'opium (suc du pavot).
                    Elle est surtout recherchée pour le bien être psychique et physique qu'elle procure.
                    En France, elle se présente généralement sous la forme de poudre allant du beige clair au brun foncé.",
        '3-mmc' => "La 3-MMC est une molécule de synthèse de la famille des cathinones. Cette drogue psychostimulante et entactogène est 
                        apparue en 2011 et peut se présenter comme une poudre planche ou comme de petits cristaux blancs.",
        'mdma' => "La MDMA est une molécule de synthèse de la famille des amphétamines et se présente sous deux formes => soit sous forme de cristaux/poudre
                    translucide, soit sous forme de cachets de taille et de couleur variable appelés \"ecstasy\". Sur cette page, vous retrouverez l'analyse
                    pour la forme cristal/poudre. L'analyse des cachets d'ecstasy possède aussi sa <a href='http://psychotopia.psychoactif.org/histo-comprime-mdma/' target='_blank'>page dédiée</a>.",
        'Comprimés de MDMA' => "La MDMA est une molécule de synthèse de la famille des amphétamines et se présente sous deux formes => soit sous forme de cristaux/poudre
                    translucide, soit sous forme de cachets de taille et de couleur variable appelés \"ecstasy\". Sur cette page, vous retrouverez l'analyse
                    pour les cachets d'ecstasy. L'analyse des échantillons sous forme de cristal ou de poudre possède aussi sa <a href='http://psychotopia.psychoactif.org/purity-mdma/' target='_blank'>page dédiée</a>.",
        'ketamine' => "La kétamine est une molécule de la famille des cycloalkylarylamines utilisée comme anesthésique général en médecine humaine et en médecine vétérinaire.
                    Elle provoque une anesthésie dissociative (dissociation entre le cortex frontal et le reste du cerveau), ainsi que des possibles hallucinations lors de la période de réveil.
                        Elle se présente sous la forme d'une poudre cristalline ou d'un liquide incolore, inodore et sans saveur.",
        'speed' => "Le \"speed\" est une appellation généraliste pour désigner principalement l'amphétamine et la méthamphétamine. Il s'agit
                    d'une drogue euphorisante et stimulante qui peut se présenter sous la forme de poudre jaunâtre avec une forte odeur chimique, mais aussi sous la forme de cristaux ou de cachets. 
                    L'analyse présentée ici se restreint aux formes poudre et cristal.",
        'cannabis' => "Le cannabis est un genre botanique qui rassemble des plantes annuelles de la famille des Cannabaceae.
                                C'est le taux de THC présent dans chaque variété botanique qui détermine si elle est utilisée comme chanvre 
                                à usage agricole (taux faible) ou pour ses effets psychoactives (taux élevé). Ces effets sont variés et dépendants de la variété => citons entre autres 
                                euphorie, excitation, relaxation, augmentation des sensations, sommeil, ... Le cannabis se présente sous différentes formes dont les plus fréquentes sont
                                la fleur séchée et la résine. Sur cette page, vous retrouverez l'analyse de la résine de cannabis mais l'analyse des fleurs séchées possède aussi sa page dédiée.",
        '2c-b' => "La 2C-B est une substance psychédélique synthétique de la classe des phénéthylamines recherchée pour ses  effets psychédéliques et entactogènes. Elle se présente sous la forme de poudre ou de comprimé.
                    Sur cette page, vous retrouverez l'analyse
                    pour la forme poudre. L'analyse des comprimés de 2C-B possède aussi sa <a href='http://psychotopia.psychoactif.org/histo-comprime-2cb/' target='_blank'>page dédiée</a>.",
    ];

    private array $ratio_base_sel = [
        'cocaine' =>  303.352/(303.352+35.453)  * 100,
        'heroine' => 369.411/(369.411+35.453)  * 100,
        '3-mmc' =>  177.247/(177.247+35.453)  * 100,
        'mdma' => 193.242/(193.242+35.453)  * 100,
        'ketamine' => 237.725/(237.725+35.453)  * 100,
        'speed' => 135.2062/(135.2062+35.453)  * 100,
        '2c-b' => 260.13/(260.13+35.453)  * 100,
    ];

    private array $labels = [
        "cocaine" => "Cocaïne",
        "heroine" => "Héroïne",
        "3-mmc" => "3-MMC",
        "mdma" => "MDMA",
        "ketamine" => "Kétamine",
        "speed" => "Speed",
        "2c-b" => "2C-B",
        "cannabis" => "Cannabis"
    ];

    private array $slugs = [
        "cocaine",
        "heroine",
        "3-mmc",
        "mdma",
        "ketamine",
        "speed",
        "2c-b",
        "cannabis"
    ];

    public function load(ObjectManager $manager): void
    {
        foreach ($this->slugs as $slug) {

            $molecule = new Molecule();
            $molecule->setSlug($slug);
            $molecule->setLabel($this->labels[$slug]);
            $molecule->setDefinition($this->definitions[$slug] ?? null);
            $molecule->setWikiUrl($this->wiki_urls[$slug] ?? null);  
            $molecule->setRatioBaseSel($this->ratio_base_sel[$slug] ?? null);

            $manager->persist($molecule);
        }

        $manager->flush();
    }
}
