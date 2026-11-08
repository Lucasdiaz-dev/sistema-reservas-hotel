<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UsuarioAdminType;
use App\Manager\UsuarioManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/usuarios')]
class UsuarioController extends AbstractController
{
    public function __construct(
        private UsuarioManager $usuarioManager,
        private FormFactoryInterface $fabricaDeFormularios,
        private LoggerInterface $logger,
    )
    {
    }

    #[Route('', name: 'admin_usuarios', methods: ['GET'])]
    public function listarUsuarios(): Response
    {
        return $this->mostrarListado();
    }

    #[Route('/{id}/editar', name: 'admin_usuario_editar', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function editarUsuario(int $id, Request $request): Response
    {
        $usuario = $this->usuarioManager->getUsuario($id);
        if ($usuario === null) {
            $this->addFlash('error', 'El usuario seleccionado ya no existe.');
            return $this->redirectToRoute('admin_usuarios');
        }
        $administrador = $this->getUser();
        if (!$administrador instanceof User) {
            throw $this->createAccessDeniedException();
        }

        // Los datos enviados solo se copian al usuario si el formulario es válido.
        $datos = clone $usuario;
        $formulario = $this->crearFormulario($datos);
        $formulario->handleRequest($request);
        if ($formulario->isSubmitted() && $formulario->isValid()) {
            try {
                $this->usuarioManager->guardarUsuario($usuario, $datos, $formulario->get('rol')->getData(), $administrador);
                $this->addFlash('success', 'La operación fue realizada correctamente.');
                return $this->redirectToRoute('admin_usuarios');
            } catch (\DomainException $error) {
                $formulario->addError(new FormError($error->getMessage()));
            } catch (\Exception $error) {
                $this->logger->error('Error al guardar un usuario.', ['exception' => $error]);
                $this->addFlash('error', 'No se pudo guardar la información del usuario.');
                return $this->redirectToRoute('admin_usuarios');
            }
        }

        return $this->mostrarListado($formulario, $id);
    }

    private function crearFormulario(User $usuario): FormInterface
    {
        return $this->fabricaDeFormularios->createNamed('usuario_admin_'.$usuario->getId(), UsuarioAdminType::class, $usuario, [
            'action' => $this->generateUrl('admin_usuario_editar', ['id' => $usuario->getId()]),
            'method' => 'POST',
            'csrf_token_id' => 'editar_usuario_'.$usuario->getId(),
        ]);
    }

    private function mostrarListado(?FormInterface $formularioEditado = null, ?int $usuarioEditado = null): Response
    {
        $usuarios = $this->usuarioManager->getUsuarios();
        $formularios = [];
        foreach ($usuarios as $usuario) {
            $formularios[$usuario->getId()] = ($usuario->getId() === $usuarioEditado
                ? $formularioEditado
                : $this->crearFormulario(clone $usuario))->createView();
        }

        return $this->render('admin/usuarios/index.html.twig', [
            'usuarios' => $usuarios,
            'formularios' => $formularios,
            'usuarioEditado' => $usuarioEditado,
        ]);
    }
}
