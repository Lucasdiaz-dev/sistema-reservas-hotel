<?php

namespace App\Controller;

use App\Manager\HabitacionManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HabitacionController extends AbstractController
{
    private HabitacionManager $habitacionManager;

    public function __construct(HabitacionManager $habitacionManager)
    {
        $this->habitacionManager = $habitacionManager;
    }

    #[Route('/admin/habitaciones', name: 'admin_habitaciones', methods: ['GET'])]
    public function listarHabitaciones(): Response
    {
        $listaDeHabitaciones = $this->habitacionManager->getHabitaciones();

        return $this->render('admin/habitaciones/index.html.twig', [
            'habitaciones' => $listaDeHabitaciones,
        ]);
    }
}