<?php

namespace App\Manager;

use App\Entity\Habitacion;
use App\Repository\HabitacionRepository;

class HabitacionManager
{
    private HabitacionRepository $habitacionRepository;

    public function __construct(HabitacionRepository $habitacionRepository)
    {
        $this->habitacionRepository = $habitacionRepository;
    }

    public function getHabitaciones(): array
    {
        return $this->habitacionRepository->findBy([], ['numero' => 'ASC']);
    }

    public function getHabitacion(int $id): ?Habitacion
    {
        return $this->habitacionRepository->find($id);
    }
}