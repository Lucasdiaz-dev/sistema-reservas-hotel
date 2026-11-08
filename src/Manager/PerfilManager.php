<?php

namespace App\Manager;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class PerfilManager
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function guardarPerfil(User $usuario, User $datos): void
    {
        $usuario->setNombre($datos->getNombre());
        $usuario->setApellido($datos->getApellido());
        $usuario->setEmail($datos->getEmail());
        $usuario->setTelefono($datos->getTelefono());
        $this->entityManager->flush();
    }
}
