<?php

namespace App\Entity;

use App\Repository\MoleculeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MoleculeRepository::class)]
#[ORM\Table(name: "topia_molecules")]
class Molecule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $definition = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $wikiUrl = null;

    #[ORM\Column(nullable: true)]
    private ?float $ratio_base_sel = null;

    #[ORM\Column]
    private ?bool $hasPurityAnalysis = null;

    #[ORM\Column]
    private ?bool $hasCutAgentsAnalysis = null;

    #[ORM\Column]
    private ?bool $hasSubProductAnalysis = null;

    #[ORM\Column]
    private ?bool $hasTabletsAnalysis = null;

    #[ORM\Column]
    private ?bool $hasContentAnalysis = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getDefinition(): ?string
    {
        return $this->definition;
    }

    public function setDefinition(?string $definition): static
    {
        $this->definition = $definition;

        return $this;
    }

    public function getWikiUrl(): ?string
    {
        return $this->wikiUrl;
    }

    public function setWikiUrl(?string $wikiUrl): static
    {
        $this->wikiUrl = $wikiUrl;

        return $this;
    }

    public function getRatioBaseSel(): ?float
    {
        return $this->ratio_base_sel;
    }

    public function setRatioBaseSel(?float $ratio_base_sel): static
    {
        $this->ratio_base_sel = $ratio_base_sel;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function hasPurityAnalysis(): ?bool
    {
        return $this->hasPurityAnalysis;
    }

    public function setHasPurityAnalysis(bool $hasPurityAnalysis): static
    {
        $this->hasPurityAnalysis = $hasPurityAnalysis;

        return $this;
    }

    public function hasCutAgentsAnalysis(): ?bool
    {
        return $this->hasCutAgentsAnalysis;
    }

    public function setHasCutAgentsAnalysis(bool $hasCutAgentsAnalysis): static
    {
        $this->hasCutAgentsAnalysis = $hasCutAgentsAnalysis;

        return $this;
    }

    public function hasSubProductAnalysis(): ?bool
    {
        return $this->hasSubProductAnalysis;
    }

    public function setHasSubProductAnalysis(bool $hasSubProductAnalysis): static
    {
        $this->hasSubProductAnalysis = $hasSubProductAnalysis;

        return $this;
    }

    public function hasTabletsAnalysis(): ?bool
    {
        return $this->hasTabletsAnalysis;
    }

    public function setHasTabletsAnalysis(bool $hasTabletsAnalysis): static
    {
        $this->hasTabletsAnalysis = $hasTabletsAnalysis;

        return $this;
    }

    public function hasContentAnalysis(): ?bool
    {
        return $this->hasContentAnalysis;
    }

    public function setHasContentAnalysis(bool $hasContentAnalysis): static
    {
        $this->hasContentAnalysis = $hasContentAnalysis;

        return $this;
    }
}
