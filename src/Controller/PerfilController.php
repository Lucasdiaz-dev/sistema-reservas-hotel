<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\PerfilType;
use App\Manager\PerfilManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PerfilController extends AbstractController
{
    public function __construct(private PerfilManager $perfilManager)
    {
    }

    #[Route('/perfil', name: 'app_perfil', methods: ['GET', 'POST'])]
    public function mostrarPerfil(Request $request): Response
    {
        $usuario = $this->getUser();
        if (!$usuario instanceof User) {
            throw $this->createAccessDeniedException();
        }

        // Un envío inválido no debe modificar al usuario de la sesión.
        $datos = clone $usuario;
        $formulario = $this->createForm(PerfilType::class, $datos);
        $formulario->handleRequest($request);

        if ($formulario->isSubmitted() && $formulario->isValid()) {
            $this->perfilManager->guardarPerfil($usuario, $datos);
            $this->addFlash('success', 'Tu perfil fue actualizado correctamente.');

            return $this->redirectToRoute('app_perfil');
        }

        return $this->render('perfil/index.html.twig', [
            'formulario' => $formulario->createView(),
        ]);
    }
}
