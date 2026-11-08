<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PerfilController extends AbstractController
{
    #[Route('/perfil', name: 'app_perfil', methods: ['GET'])]
    public function mostrarPerfil(): Response
    {
        return $this->render('perfil/index.html.twig', ['usuario' => $this->getUser()]);
    }
}
