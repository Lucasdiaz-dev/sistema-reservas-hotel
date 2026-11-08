<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ReservaController extends AbstractController
{
    // La pantalla de confirmación (CU005) es el paso siguiente.
    // La ruta existe para el formulario del detalle y para mandar al login si no hay sesión.
    #[Route('/habitacion/{id}/reservar', name: 'app_reserva_confirmar', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function confirmar(int $id): Response
    {
        throw $this->createNotFoundException();
    }
}
