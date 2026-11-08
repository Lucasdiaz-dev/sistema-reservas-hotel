<?php

namespace App\Manager;

use App\Entity\User;
use App\Repository\UserRepository;

class UsuarioManager
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    /** @return User[] */
    public function getUsuarios(): array
    {
        return $this->userRepository->findBy([], ['apellido' => 'ASC', 'nombre' => 'ASC', 'id' => 'ASC']);
    }
}
