<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Usuarios ordenados por apellido. Si hay texto, coincide parcialmente
     * con nombre, apellido o email, sin distinguir mayúsculas.
     *
     * @return User[]
     */
    public function buscarPorNombreOEmail(string $busqueda): array
    {
        $consulta = $this->createQueryBuilder('u')
            ->orderBy('u.apellido', 'ASC')
            ->addOrderBy('u.nombre', 'ASC')
            ->addOrderBy('u.id', 'ASC');

        $busqueda = trim($busqueda);
        if ($busqueda !== '') {
            $consulta
                ->andWhere("(LOWER(u.nombre) LIKE :busqueda ESCAPE '!' OR LOWER(u.apellido) LIKE :busqueda ESCAPE '!' OR LOWER(u.email) LIKE :busqueda ESCAPE '!')")
                ->setParameter('busqueda', $this->patronLike($busqueda));
        }

        return $consulta->getQuery()->getResult();
    }

    private function patronLike(string $busqueda): string
    {
        $busqueda = mb_strtolower($busqueda);
        $busqueda = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $busqueda);

        return '%'.$busqueda.'%';
    }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
