<?php

namespace App\Manager;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class UsuarioManager
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
    )
    {
    }

    /** @return User[] */
    public function getUsuarios(): array
    {
        return $this->userRepository->findBy([], ['apellido' => 'ASC', 'nombre' => 'ASC', 'id' => 'ASC']);
    }

    public function getUsuario(int $id): ?User
    {
        return $this->userRepository->find($id);
    }

    public function guardarUsuario(User $usuario, User $datos, string $rol, User $administrador): void
    {
        if (!in_array($rol, ['CLIENTE', 'ADMINISTRADOR'], true)) {
            throw new \DomainException('Seleccioná un rol válido.');
        }
        if ($usuario->getId() === $administrador->getId() && $rol !== 'ADMINISTRADOR') {
            throw new \DomainException('No podés quitarte tu propio rol de administrador.');
        }

        $usuario->setNombre($datos->getNombre());
        $usuario->setApellido($datos->getApellido());
        $usuario->setEmail($datos->getEmail());
        $usuario->setTelefono($datos->getTelefono());
        $usuario->setRoles($rol === 'ADMINISTRADOR' ? ['ROLE_ADMIN'] : []);
        $this->entityManager->flush();
    }
}
