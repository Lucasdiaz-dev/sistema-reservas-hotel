<?php

namespace App\Controller;

use App\Entity\User;
use App\Manager\PerfilManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MisReservasController extends AbstractController
{
    public function __construct(private PerfilManager $perfilManager)
    {
    }

    #[Route('/reservas', name: 'app_mis_reservas', methods: ['GET'])]
    public function listarMisReservas(): Response
    {
        $usuario = $this->getUser();
        if (!$usuario instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('mis_reservas/index.html.twig', [
            'reservas' => $this->perfilManager->getMisReservas($usuario),
        ]);
    }
}
