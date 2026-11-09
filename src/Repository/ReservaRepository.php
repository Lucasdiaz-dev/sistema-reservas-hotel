<?php

namespace App\Repository;

use App\Entity\Habitacion;
use App\Entity\Reserva;
use App\Entity\User;
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

    /**
     * Reservas cuyo período se cruza con el filtro del admin (CU008).
     * La estadía ocupa [ingreso, egreso); el filtro incluye desde y hasta.
     *
     * @return Reserva[]
     */
    public function buscarPorPeriodo(\DateTimeImmutable $desde, \DateTimeImmutable $hasta): array
    {
        return $this->createQueryBuilder('r')
            ->addSelect('u', 'h')
            ->join('r.usuario', 'u')
            ->join('r.habitacion', 'h')
            ->andWhere('r.fechaIngreso <= :hasta')
            ->andWhere('r.fechaEgreso > :desde')
            ->setParameter('desde', $desde)
            ->setParameter('hasta', $hasta)
            ->orderBy('r.fechaIngreso', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return Reserva[] */
    public function getReservasDelUsuario(User $usuario): array
    {
        return $this->createQueryBuilder('r')
            ->addSelect('habitacion', 'imagen')
            ->join('r.habitacion', 'habitacion')
            ->leftJoin('habitacion.imagenHabitacions', 'imagen')
            ->andWhere('r.usuario = :usuario')
            ->setParameter('usuario', $usuario)
            ->orderBy('r.fechaIngreso', 'ASC')
            ->addOrderBy('r.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
