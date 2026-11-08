<?php

namespace App\Repository;

use App\Entity\Habitacion;
use App\Entity\Reserva;
use App\Enum\EstadoReserva;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Habitacion>
 */
class HabitacionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Habitacion::class);
    }

    /**
     * Activas, con capacidad suficiente y sin una reserva que bloquee el período.
     * Solo CANCELADA libera la habitación. El día de egreso puede ser ingreso de otra.
     *
     * @return Habitacion[]
     */
    public function buscarDisponibles(\DateTimeImmutable $ingreso, \DateTimeImmutable $egreso, int $huespedes): array
    {
        $sub = $this->getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(r.habitacion)')
            ->from(Reserva::class, 'r')
            ->andWhere('r.estado != :cancelada')
            ->andWhere('r.fechaIngreso < :egreso')
            ->andWhere('r.fechaEgreso > :ingreso');

        return $this->createQueryBuilder('h')
            ->andWhere('h.activa = true')
            ->andWhere('h.capacidadMaxima >= :huespedes')
            ->andWhere($this->createQueryBuilder('h')->expr()->notIn('h.id', $sub->getDQL()))
            ->setParameter('cancelada', EstadoReserva::CANCELADA)
            ->setParameter('ingreso', $ingreso)
            ->setParameter('egreso', $egreso)
            ->setParameter('huespedes', $huespedes)
            ->orderBy('h.numero', 'ASC')
            ->getQuery()->getResult();
    }
}
