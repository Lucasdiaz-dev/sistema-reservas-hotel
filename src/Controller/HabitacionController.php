<?php

namespace App\Controller;

use App\Entity\Habitacion;
use App\Form\HabitacionType;
use App\Manager\HabitacionManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
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

    #[Route(
        '/admin/habitaciones/nueva',
        name: 'admin_habitacion_nueva',
        methods: ['GET', 'POST']
    )]
    public function nuevaHabitacion(Request $request): Response
    {
        $habitacion = new Habitacion();
        $habitacion->setActiva(true);

        $formulario = $this->createForm(HabitacionType::class, $habitacion);
        $formulario->handleRequest($request);

        $seleccionadas = [];
        $portada = '';

        if ($formulario->isSubmitted()) {
            $seleccionadas = $request->request->all('sel');
            $portada = $request->request->get('portada', '');

            $errorImagenes = $this->habitacionManager->validarImagenes(
                $seleccionadas,
                $portada
            );

            if ($errorImagenes !== null) {
                $formulario->addError(new FormError($errorImagenes));
            }

            if ($formulario->isValid()) {
                if ($this->habitacionManager->existeNumero($habitacion)) {
                    $formulario->get('numero')->addError(
                        new FormError('Ya existe una habitación con ese número.')
                    );
                } else {
                    $this->habitacionManager->guardarNuevaHabitacionConImagenes(
                        $habitacion,
                        $seleccionadas,
                        $portada
                    );

                    $this->addFlash('success', 'Habitación creada correctamente.');

                    return $this->redirectToRoute('admin_habitaciones');
                }
            }
        }

        $respuesta = new Response();

        if ($formulario->isSubmitted()) {
            $respuesta->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->render('admin/habitaciones/formulario.html.twig', [
            'formulario' => $formulario->createView(),
            'titulo' => 'Nueva habitación',
            'mostrarImagenes' => true,
            'imagenesDisponibles' => $this->habitacionManager->getImagenesDisponibles(),
            'seleccionadas' => $seleccionadas,
            'portada' => $portada,
        ], $respuesta);
    }

    #[Route(
        '/admin/habitaciones/{id}/editar',
        name: 'admin_habitacion_editar',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    public function editarHabitacion(int $id, Request $request): Response
    {
        $habitacion = $this->habitacionManager->getHabitacion($id);

        if ($habitacion === null) {
            throw $this->createNotFoundException('La habitación no existe.');
        }

        $formulario = $this->createForm(HabitacionType::class, $habitacion);
        $formulario->handleRequest($request);

        if ($formulario->isSubmitted() && $formulario->isValid()) {
            if ($this->habitacionManager->existeNumero($habitacion)) {
                $formulario->get('numero')->addError(
                    new FormError('Ya existe una habitación con ese número.')
                );
            } else {
                $this->habitacionManager->guardarHabitacion($habitacion);

                $this->addFlash('success', 'Habitación modificada correctamente.');

                return $this->redirectToRoute('admin_habitaciones');
            }
        }

        $respuesta = new Response();

        if ($formulario->isSubmitted()) {
            $respuesta->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->render('admin/habitaciones/formulario.html.twig', [
            'formulario' => $formulario->createView(),
            'titulo' => 'Editar habitación',
            'mostrarImagenes' => false,
        ], $respuesta);
    }
}