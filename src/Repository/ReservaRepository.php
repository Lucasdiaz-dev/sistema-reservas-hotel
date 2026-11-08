<?php

namespace App\Repository;

use App\Entity\Reserva;
use App\Entity\User;
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

    //    /**
    //     * @return Reserva[] Returns an array of Reserva objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Reserva
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
