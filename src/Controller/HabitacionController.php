<?php

namespace App\Controller;

use App\Entity\Habitacion;
use App\Manager\ReservaManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HabitacionController extends AbstractController
{
    private ReservaManager $reservaManager;

    public function __construct(ReservaManager $reservaManager)
    {
        $this->reservaManager = $reservaManager;
    }

    #[Route('/habitacion/{id}', name: 'app_habitacion_detalle', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function detalle(int $id, Request $request): Response
    {
        try {
            $habitacion = $this->reservaManager->obtenerHabitacionActiva($id);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('success', $e->getMessage());

            return $this->redirectToRoute('app_busqueda');
        }

        return $this->render('habitacion/detalle.html.twig', [
            'habitacion' => $habitacion,
            'ingreso' => $this->fechaDeConsulta($request->query->get('ingreso')),
            'egreso' => $this->fechaDeConsulta($request->query->get('egreso')),
            'huespedes' => $this->huespedesDeConsulta($request->query->get('huespedes'), $habitacion),
        ]);
    }

    private function fechaDeConsulta(mixed $valor): ?string
    {
        if (!\is_string($valor) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            return null;
        }

        return $valor;
    }

    private function huespedesDeConsulta(mixed $valor, Habitacion $habitacion): ?int
    {
        if (!\is_string($valor) && !\is_int($valor)) {
            return null;
        }

        $cantidad = (int) $valor;
        if ($cantidad < 1 || $cantidad > $habitacion->getCapacidadMaxima()) {
            return null;
        }

        return $cantidad;
    }
}
