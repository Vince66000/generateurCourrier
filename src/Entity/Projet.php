<?php

namespace App\Entity;

use App\Repository\ProjetRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ProjetRepository::class)
 */
class Projet
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $reference;

    /**
     * @ORM\Column(type="integer")
     */
    private $idSociete;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $dateDebut;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $dateFin;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getIdSociete(): ?int
    {
        return $this->idSociete;
    }

    public function setIdSociete(int $idSociete): self
    {
        $this->idSociete = $idSociete;

        return $this;
    }

    public function getDateDebut(): ?int
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?int $dateDebut): self
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ? int
    {
        return $this->dateFin;
    }

    public function setDateFin(?int $dateFin): self
    {
        $this->dateFin = $dateFin;

        return $this;
    }
}
