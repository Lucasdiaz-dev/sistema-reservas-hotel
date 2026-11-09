<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Nombres que el header ya usa y que todavía no tienen pantalla.
 * Cada ruta se va de acá cuando exista la real: si no, el nombre choca.
 */
class RutaPendienteController extends AbstractController
{
    #[Route('/perfil', name: 'app_perfil')]
    #[Route('/admin/usuarios', name: 'admin_usuarios')]
    public function pendiente(): Response
    {
        throw $this->createNotFoundException();
    }
}
