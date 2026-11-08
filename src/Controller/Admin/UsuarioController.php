<?php

namespace App\Controller\Admin;

use App\Manager\UsuarioManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/usuarios')]
class UsuarioController extends AbstractController
{
    public function __construct(private UsuarioManager $usuarioManager)
    {
    }

    #[Route('', name: 'admin_usuarios', methods: ['GET'])]
    public function listarUsuarios(): Response
    {
        return $this->render('admin/usuarios/index.html.twig', [
            'usuarios' => $this->usuarioManager->getUsuarios(),
        ]);
    }
}
