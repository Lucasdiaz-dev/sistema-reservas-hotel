<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Nombres que el header ya usa y que todavía arman Luciano y Matías.
 * Cuando entren sus pantallas, esta clase se va: si no, los nombres de ruta chocan.
 */
class RutaPendienteController extends AbstractController
{
    #[Route('/perfil', name: 'app_perfil')]
    #[Route('/admin/habitaciones', name: 'admin_habitaciones')]
    #[Route('/admin/usuarios', name: 'admin_usuarios')]
    public function pendiente(): Response
    {
        throw $this->createNotFoundException();
    }
}
