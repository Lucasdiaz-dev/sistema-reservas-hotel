<?php

namespace App\Entity;

use App\Repository\ImagenHabitacionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImagenHabitacionRepository::class)]
class ImagenHabitacion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $ruta = null;

    #[ORM\Column]
    private ?bool $portada = null;

    #[ORM\ManyToOne(inversedBy: 'imagenHabitacions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Habitacion $habitacion = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRuta(): ?string
    {
        return $this->ruta;
    }

    public function setRuta(string $ruta): static
    {
        $this->ruta = $ruta;

        return $this;
    }

    public function isPortada(): ?bool
    {
        return $this->portada;
    }

    public function setPortada(bool $portada): static
    {
        $this->portada = $portada;

        return $this;
    }

    public function getHabitacion(): ?Habitacion
    {
        return $this->habitacion;
    }

    public function setHabitacion(?Habitacion $habitacion): static
    {
        $this->habitacion = $habitacion;

        return $this;
    }
}
