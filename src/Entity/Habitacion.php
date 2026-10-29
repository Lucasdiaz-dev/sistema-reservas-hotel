<?php

namespace App\Entity;

use App\Enum\TipoHabitacion;
use App\Repository\HabitacionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HabitacionRepository::class)]
class Habitacion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $numero = null;

    #[ORM\Column(enumType: TipoHabitacion::class)]
    private ?TipoHabitacion $tipo = null;

    #[ORM\Column]
    private ?int $capacidadMaxima = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $precioPorNoche = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $descripcion = null;

    #[ORM\Column]
    private ?bool $activa = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $estrellas = null;

    /**
     * @var Collection<int, Reserva>
     */
    #[ORM\OneToMany(targetEntity: Reserva::class, mappedBy: 'habitacion')]
    private Collection $reservas;

    /**
     * @var Collection<int, ImagenHabitacion>
     */
    #[ORM\OneToMany(targetEntity: ImagenHabitacion::class, mappedBy: 'habitacion', orphanRemoval: true)]
    private Collection $imagenHabitacions;

    public function __construct()
    {
        $this->reservas = new ArrayCollection();
        $this->imagenHabitacions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumero(): ?int
    {
        return $this->numero;
    }

    public function setNumero(int $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    public function getTipo(): ?TipoHabitacion
    {
        return $this->tipo;
    }

    public function setTipo(TipoHabitacion $tipo): static
    {
        $this->tipo = $tipo;

        return $this;
    }

    public function getCapacidadMaxima(): ?int
    {
        return $this->capacidadMaxima;
    }

    public function setCapacidadMaxima(int $capacidadMaxima): static
    {
        $this->capacidadMaxima = $capacidadMaxima;

        return $this;
    }

    public function getPrecioPorNoche(): ?string
    {
        return $this->precioPorNoche;
    }

    public function setPrecioPorNoche(string $precioPorNoche): static
    {
        $this->precioPorNoche = $precioPorNoche;

        return $this;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(string $descripcion): static
    {
        $this->descripcion = $descripcion;

        return $this;
    }

    public function isActiva(): ?bool
    {
        return $this->activa;
    }

    public function setActiva(bool $activa): static
    {
        $this->activa = $activa;

        return $this;
    }

    public function getEstrellas(): ?int
    {
        return $this->estrellas;
    }

    public function setEstrellas(int $estrellas): static
    {
        $this->estrellas = $estrellas;

        return $this;
    }

    /**
     * @return Collection<int, Reserva>
     */
    public function getReservas(): Collection
    {
        return $this->reservas;
    }

    public function addReserva(Reserva $reserva): static
    {
        if (!$this->reservas->contains($reserva)) {
            $this->reservas->add($reserva);
            $reserva->setHabitacion($this);
        }

        return $this;
    }

    public function removeReserva(Reserva $reserva): static
    {
        if ($this->reservas->removeElement($reserva)) {
            // set the owning side to null (unless already changed)
            if ($reserva->getHabitacion() === $this) {
                $reserva->setHabitacion(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ImagenHabitacion>
     */
    public function getImagenHabitacions(): Collection
    {
        return $this->imagenHabitacions;
    }

    public function addImagenHabitacion(ImagenHabitacion $imagenHabitacion): static
    {
        if (!$this->imagenHabitacions->contains($imagenHabitacion)) {
            $this->imagenHabitacions->add($imagenHabitacion);
            $imagenHabitacion->setHabitacion($this);
        }

        return $this;
    }

    public function removeImagenHabitacion(ImagenHabitacion $imagenHabitacion): static
    {
        if ($this->imagenHabitacions->removeElement($imagenHabitacion)) {
            // set the owning side to null (unless already changed)
            if ($imagenHabitacion->getHabitacion() === $this) {
                $imagenHabitacion->setHabitacion(null);
            }
        }

        return $this;
    }
}
