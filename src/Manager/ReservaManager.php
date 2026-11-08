<?php

namespace App\Manager;

use App\Entity\Habitacion;
use App\Entity\Reserva;
use App\Entity\User;
use App\Enum\EstadoReserva;
use App\Enum\TipoHabitacion;
use App\Repository\HabitacionRepository;
use App\Repository\ReservaRepository;
use Doctrine\ORM\EntityManagerInterface;

class ReservaManager
{
    private HabitacionRepository $habitacionRepository;
    private ReservaRepository $reservaRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(HabitacionRepository $habitacionRepository, ReservaRepository $reservaRepository, EntityManagerInterface $entityManager)
    {
        $this->habitacionRepository = $habitacionRepository;
        $this->reservaRepository = $reservaRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Habitaciones disponibles para la búsqueda pública (CU003).
     * El tipo es el filtro opcional de los chips.
     *
     * @return Habitacion[]
     */
    public function buscarDisponibles(\DateTimeImmutable $ingreso, \DateTimeImmutable $egreso, int $huespedes, ?TipoHabitacion $tipo = null): array
    {
        $this->validarFechasBusqueda($ingreso, $egreso);

        $habitaciones = $this->habitacionRepository->buscarDisponibles(
            $this->sinHora($ingreso),
            $this->sinHora($egreso),
            $huespedes
        );

        if ($tipo === null) {
            return $habitaciones;
        }

        return array_values(array_filter(
            $habitaciones,
            static fn (Habitacion $habitacion): bool => $habitacion->getTipo() === $tipo
        ));
    }

    /**
     * La ficha pública solo muestra una habitación activa (CU004).
     */
    public function obtenerHabitacionActiva(int $id): Habitacion
    {
        $habitacion = $this->habitacionRepository->find($id);

        if ($habitacion === null || !$habitacion->isActiva()) {
            throw new \InvalidArgumentException('La habitación seleccionada no se encuentra disponible.');
        }

        return $habitacion;
    }

    public function obtenerReserva(int $id): ?Reserva
    {
        return $this->reservaRepository->find($id);
    }

    /**
     * Revalida y registra la reserva en PENDIENTE, con el total calculado en el servidor.
     */
    public function registrar(Habitacion $habitacion, User $usuario, \DateTimeImmutable $ingreso, \DateTimeImmutable $egreso, int $huespedes): Reserva
    {
        $this->validarParaReserva($habitacion, $ingreso, $egreso, $huespedes);

        $reserva = (new Reserva())
            ->setHabitacion($habitacion)
            ->setUsuario($usuario)
            ->setFechaIngreso($this->sinHora($ingreso))
            ->setFechaEgreso($this->sinHora($egreso))
            ->setCantidadHuespedes($huespedes)
            ->setTotal($this->calcularTotal($habitacion, $ingreso, $egreso))
            ->setEstado(EstadoReserva::PENDIENTE);

        $this->entityManager->persist($reserva);
        $this->entityManager->flush();

        return $reserva;
    }

    /**
     * Tope del select de huéspedes: la mayor capacidad entre las habitaciones activas.
     */
    public function capacidadMaximaDeActivas(): int
    {
        $maxima = 1;

        foreach ($this->habitacionRepository->findBy(['activa' => true]) as $habitacion) {
            $maxima = max($maxima, (int) $habitacion->getCapacidadMaxima());
        }

        return $maxima;
    }

    /**
     * Fechas de la búsqueda: tienen que venir las dos, e ingreso anterior a egreso.
     */
    public function validarFechasBusqueda(?\DateTimeImmutable $ingreso, ?\DateTimeImmutable $egreso): void
    {
        if ($ingreso === null || $egreso === null || $this->sinHora($ingreso) >= $this->sinHora($egreso)) {
            throw new \InvalidArgumentException('La fecha de ingreso debe ser anterior a la fecha de egreso.');
        }
    }

    /**
     * Fechas, huéspedes y disponibilidad antes de mostrar o registrar una reserva (CU005).
     */
    public function validarParaReserva(Habitacion $habitacion, \DateTimeImmutable $ingreso, \DateTimeImmutable $egreso, int $huespedes): void
    {
        $ingreso = $this->sinHora($ingreso);
        $egreso = $this->sinHora($egreso);
        $hoy = new \DateTimeImmutable('today');

        if ($ingreso < $hoy) {
            throw new \InvalidArgumentException('La fecha de ingreso no puede ser anterior a la fecha actual.');
        }

        if ($egreso <= $ingreso) {
            throw new \InvalidArgumentException('La fecha de egreso debe ser posterior a la fecha de ingreso.');
        }

        if ($huespedes <= 0 || $huespedes > $habitacion->getCapacidadMaxima()) {
            throw new \InvalidArgumentException(
                'La cantidad de huéspedes supera la capacidad máxima. Capacidad máxima: '.$habitacion->getCapacidadMaxima()
            );
        }

        if (!$habitacion->isActiva()) {
            throw new \InvalidArgumentException('La habitación seleccionada no se encuentra disponible.');
        }

        if ($this->reservaRepository->existeSuperpuesta($habitacion, $ingreso, $egreso)) {
            throw new \InvalidArgumentException('La habitación no está disponible para las fechas seleccionadas.');
        }
    }

    /**
     * Noches × precio por noche vigente. Queda como decimal en string (RN09).
     */
    public function calcularTotal(Habitacion $habitacion, \DateTimeImmutable $ingreso, \DateTimeImmutable $egreso): string
    {
        $ingreso = $this->sinHora($ingreso);
        $egreso = $this->sinHora($egreso);

        if ($egreso <= $ingreso) {
            throw new \InvalidArgumentException('La fecha de egreso debe ser posterior a la fecha de ingreso.');
        }

        $noches = (string) $ingreso->diff($egreso)->days;

        return bcmul($noches, (string) $habitacion->getPrecioPorNoche(), 2);
    }

    private function sinHora(\DateTimeImmutable $fecha): \DateTimeImmutable
    {
        return $fecha->setTime(0, 0);
    }
}
