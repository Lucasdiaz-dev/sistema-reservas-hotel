<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HabitacionController extends AbstractController
{
    // La pantalla de detalle (CU004) se completa en el paso siguiente.
    // La ruta existe para que la búsqueda pueda armar el link con path().
    #[Route('/habitacion/{id}', name: 'app_habitacion_detalle', methods: ['GET'])]
    public function detalle(int $id): Response
    {
        throw $this->createNotFoundException();
    }
}
