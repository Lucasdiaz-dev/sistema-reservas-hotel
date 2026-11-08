<?php

namespace App\Repository;

use App\Entity\Habitacion;
use App\Entity\Reserva;
use App\Enum\EstadoReserva;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reserva>
 */
class ReservaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reserva::class);
    }

    /**
     * Hay una reserva que bloquea esta habitación en el período (CU005 RN03).
     * Misma superposición que la búsqueda: el día de egreso puede ser ingreso de otra.
     */
    public function existeSuperpuesta(Habitacion $habitacion, \DateTimeImmutable $ingreso, \DateTimeImmutable $egreso): bool
    {
        $cantidad = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.habitacion = :habitacion')
            ->andWhere('r.estado != :cancelada')
            ->andWhere('r.fechaIngreso < :egreso')
            ->andWhere('r.fechaEgreso > :ingreso')
            ->setParameter('habitacion', $habitacion)
            ->setParameter('cancelada', EstadoReserva::CANCELADA)
            ->setParameter('ingreso', $ingreso)
            ->setParameter('egreso', $egreso)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $cantidad > 0;
    }
}
