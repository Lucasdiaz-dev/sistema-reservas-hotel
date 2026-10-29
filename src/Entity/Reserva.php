<?php

namespace App\Entity;

use App\Enum\EstadoReserva;
use App\Repository\ReservaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservaRepository::class)]
class Reserva
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $fechaIngreso = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $fechaEgreso = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $cantidadHuespedes = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $total = null;

    #[ORM\Column(enumType: EstadoReserva::class)]
    private ?EstadoReserva $estado = null;

    #[ORM\ManyToOne(inversedBy: 'reservas')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $usuario = null;

    #[ORM\ManyToOne(inversedBy: 'reservas')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Habitacion $habitacion = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFechaIngreso(): ?\DateTimeImmutable
    {
        return $this->fechaIngreso;
    }

    public function setFechaIngreso(\DateTimeImmutable $fechaIngreso): static
    {
        $this->fechaIngreso = $fechaIngreso;

        return $this;
    }

    public function getFechaEgreso(): ?\DateTimeImmutable
    {
        return $this->fechaEgreso;
    }

    public function setFechaEgreso(\DateTimeImmutable $fechaEgreso): static
    {
        $this->fechaEgreso = $fechaEgreso;

        return $this;
    }

    public function getCantidadHuespedes(): ?int
    {
        return $this->cantidadHuespedes;
    }

    public function setCantidadHuespedes(int $cantidadHuespedes): static
    {
        $this->cantidadHuespedes = $cantidadHuespedes;

        return $this;
    }

    public function getTotal(): ?string
    {
        return $this->total;
    }

    public function setTotal(string $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getEstado(): ?EstadoReserva
    {
        return $this->estado;
    }

    public function setEstado(EstadoReserva $estado): static
    {
        $this->estado = $estado;

        return $this;
    }

    public function getUsuario(): ?User
    {
        return $this->usuario;
    }

    public function setUsuario(?User $usuario): static
    {
        $this->usuario = $usuario;

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
