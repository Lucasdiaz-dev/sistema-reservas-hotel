<?php

namespace App\Manager;

use App\Entity\Habitacion;
use App\Repository\HabitacionRepository;
use App\Repository\ReservaRepository;

class ReservaManager
{
    private HabitacionRepository $habitacionRepository;
    private ReservaRepository $reservaRepository;

    public function __construct(HabitacionRepository $habitacionRepository, ReservaRepository $reservaRepository)
    {
        $this->habitacionRepository = $habitacionRepository;
        $this->reservaRepository = $reservaRepository;
    }

    /**
     * Habitaciones disponibles para la búsqueda pública (CU003).
     *
     * @return Habitacion[]
     */
    public function buscarDisponibles(\DateTimeImmutable $ingreso, \DateTimeImmutable $egreso, int $huespedes): array
    {
        $this->validarFechasBusqueda($ingreso, $egreso);

        return $this->habitacionRepository->buscarDisponibles(
            $this->sinHora($ingreso),
            $this->sinHora($egreso),
            $huespedes
        );
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
