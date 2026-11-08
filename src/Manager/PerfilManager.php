<?php

namespace App\Manager;

use App\Entity\User;
use App\Entity\Reserva;
use App\Repository\ReservaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PerfilManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ReservaRepository $reservaRepository,
    )
    {
    }

    /** @return Reserva[] */
    public function getMisReservas(User $usuario): array
    {
        return $this->reservaRepository->getReservasDelUsuario($usuario);
    }

    public function guardarPerfil(User $usuario, User $datos, ?string $nuevaContrasena = null): void
    {
        // Calculamos el hash antes de modificar al usuario administrado por Doctrine.
        $hash = $nuevaContrasena !== null && $nuevaContrasena !== ''
            ? $this->passwordHasher->hashPassword($usuario, $nuevaContrasena)
            : null;
        $usuario->setNombre($datos->getNombre());
        $usuario->setApellido($datos->getApellido());
        $usuario->setEmail($datos->getEmail());
        $usuario->setTelefono($datos->getTelefono());
        if ($hash !== null) {
            $usuario->setPassword($hash);
        }
        $this->entityManager->flush();
    }
}
