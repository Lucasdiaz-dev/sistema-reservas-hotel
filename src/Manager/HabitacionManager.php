<?php

namespace App\Manager;

use App\Entity\Habitacion;
use App\Repository\HabitacionRepository;
use Doctrine\ORM\EntityManagerInterface;

class HabitacionManager
{
    private HabitacionRepository $habitacionRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        HabitacionRepository $habitacionRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->habitacionRepository = $habitacionRepository;
        $this->entityManager = $entityManager;
    }

    public function getHabitaciones(): array
    {
        return $this->habitacionRepository->findBy([], ['numero' => 'ASC']);
    }

    public function getHabitacion(int $id): ?Habitacion
    {
        return $this->habitacionRepository->find($id);
    }

    public function existeNumero(Habitacion $habitacion): bool
    {
        $habitacionEncontrada = $this->habitacionRepository->findOneBy([
            'numero' => $habitacion->getNumero(),
        ]);

        if ($habitacionEncontrada === null) {
            return false;
        }

        if ($habitacionEncontrada->getId() === $habitacion->getId()) {
            return false;
        }

        return true;
    }

    public function guardarHabitacion(Habitacion $habitacion): void
    {
        $this->entityManager->persist($habitacion);
        $this->entityManager->flush();
    }
}