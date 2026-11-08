<?php

namespace App\Controller;

use App\Enum\TipoHabitacion;
use App\Form\BusquedaType;
use App\Manager\ReservaManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BusquedaController extends AbstractController
{
    private ReservaManager $reservaManager;

    public function __construct(ReservaManager $reservaManager)
    {
        $this->reservaManager = $reservaManager;
    }

    #[Route('/', name: 'app_busqueda', methods: ['GET'])]
    public function buscar(Request $request): Response
    {
        $form = $this->createForm(BusquedaType::class, null, [
            'capacidad_maxima' => $this->reservaManager->capacidadMaximaDeActivas(),
        ]);
        $form->handleRequest($request);

        $habitaciones = null;
        $error = null;
        $ingreso = null;
        $egreso = null;
        $huespedes = null;
        $tipo = TipoHabitacion::tryFrom((string) $request->query->get('tipo'));

        if ($form->isSubmitted()) {
            $ingreso = $form->get('ingreso')->getData();
            $egreso = $form->get('egreso')->getData();
            $huespedes = (int) $form->get('huespedes')->getData();

            try {
                // las fechas inválidas o faltantes cortan antes de consultar
                $this->reservaManager->validarFechasBusqueda($ingreso, $egreso);
                $habitaciones = $this->reservaManager->buscarDisponibles($ingreso, $egreso, $huespedes, $tipo);
            } catch (\InvalidArgumentException $e) {
                $error = $e->getMessage();
            }
        }

        return $this->render('busqueda/index.html.twig', [
            'formulario' => $form,
            'habitaciones' => $habitaciones,
            'error' => $error,
            'ingreso' => $ingreso,
            'egreso' => $egreso,
            'huespedes' => $huespedes,
            'tipo' => $tipo?->value,
        ]);
    }
}
