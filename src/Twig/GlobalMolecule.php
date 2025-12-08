<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use App\Repository\MoleculeRepository;

class GlobalMolecule extends AbstractExtension
{

    public function __construct(private readonly MoleculeRepository $repo){}
      

    public function getFunctions(): array
    {
        return [
            new TwigFunction('allMolecules', [$this, 'getMolecules']),
        ];
    }

    public function getMolecules()
    {
        return $this->repo->findAll();
    }
}
