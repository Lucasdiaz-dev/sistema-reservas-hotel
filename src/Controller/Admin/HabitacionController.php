<?php

namespace App\Controller\Admin;

use App\Entity\Habitacion;
use App\Form\HabitacionType;
use App\Manager\HabitacionManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;

#[Route('/admin/habitaciones')]
class HabitacionController extends AbstractController
{
    private const HABITACIONES_CREADAS = 'habitaciones_creadas';

    private HabitacionManager $habitacionManager;

    public function __construct(HabitacionManager $habitacionManager)
    {
        $this->habitacionManager = $habitacionManager;
    }

    #[Route('', name: 'admin_habitaciones', methods: ['GET'])]
    public function listarHabitaciones(): Response
    {
        $listaDeHabitaciones = $this->habitacionManager->getHabitaciones();

        return $this->render('admin/habitaciones/index.html.twig', [
            'habitaciones' => $listaDeHabitaciones,
        ]);
    }

    #[Route(
        '/nueva',
        name: 'admin_habitacion_nueva',
        methods: ['GET', 'POST']
    )]
    public function nuevaHabitacion(Request $request): Response
    {
        $habitacion = new Habitacion();
        $habitacion->setActiva(true);

        $accion = $this->accionEnviada($request);
        $formulario = $this->createForm(
            HabitacionType::class,
            $habitacion,
            $this->opcionesDelFormulario($accion)
        );

        $formulario->handleRequest($request);

        $seleccionadas = [];
        $portada = '';
        $errorSubida = null;
        $nombreSubido = null;

        if ($formulario->isSubmitted()) {
            $seleccionadas = $this->habitacionManager->filtrarImagenesSeleccionadas(
                $request->request->all('sel')
            );
            $portada = $request->request->get('portada', '');

            if ($accion === 'subir' && $this->tokenValido($formulario)) {
                try {
                    $nombreSubido = $this->habitacionManager->subirImagen($request->files->get('imagen'));
                    $this->addFlash(
                        'success',
                        'La imagen se subió correctamente y ya está disponible para seleccionar.'
                    );
                } catch (\DomainException $error) {
                    $errorSubida = $error->getMessage();
                    $formulario->addError(new FormError($errorSubida));
                }
            }

            if ($accion === 'guardar' && !$this->excedeTamanoDeCarga($formulario)) {
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

                        $id = $habitacion->getId();
                        if ($id === null) {
                            return $this->redirectToRoute('admin_habitaciones');
                        }

                        $this->recordarHabitacionCreada($request, $id);

                        return $this->redirectToRoute('admin_habitacion_creada', [
                            'id' => $id,
                        ]);
                    }
                }
            }
        }

        $respuesta = new Response();

        if ($formulario->isSubmitted()) {
            $respuesta->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->render('admin/habitaciones/form.html.twig', [
            'formulario' => $formulario->createView(),
            'titulo' => 'Nueva habitación',
            'mostrarImagenes' => true,
            'imagenesDisponibles' => $this->imagenesDisponibles($nombreSubido),
            'seleccionadas' => $seleccionadas,
            'portada' => $portada,
            'errorSubida' => $errorSubida,
            'tamanoMaximo' => HabitacionManager::TAMANO_MAXIMO_BYTES,
        ], $respuesta);
    }

    #[Route(
        '/{id}/creada',
        name: 'admin_habitacion_creada',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    public function resultadoHabitacionCreada(int $id, Request $request): Response
    {
        if (!$this->fueCreadaEnEstaSesion($request, $id)) {
            return $this->redirectToRoute('admin_habitaciones');
        }

        $habitacion = $this->habitacionManager->getHabitacion($id);
        if ($habitacion === null) {
            return $this->redirectToRoute('admin_habitaciones');
        }

        $respuesta = $this->render('admin/habitaciones/creada.html.twig', [
            'habitacion' => $habitacion,
        ]);
        $respuesta->headers->set('Cache-Control', 'private, no-store');

        return $respuesta;
    }

    #[Route(
        '/{id}/editar',
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

        $titulo = 'Editar habitación ' . $habitacion->getNumero();
        $seleccionadas = [];
        $portada = '';

        foreach ($habitacion->getImagenHabitacions() as $imagen) {
            $seleccionadas[] = $imagen->getRuta();

            if ($imagen->isPortada()) {
                $portada = $imagen->getRuta();
            }
        }

        $accion = $this->accionEnviada($request);
        $formulario = $this->createForm(
            HabitacionType::class,
            $habitacion,
            $this->opcionesDelFormulario($accion)
        );

        $formulario->handleRequest($request);

        $errorSubida = null;
        $nombreSubido = null;

        if ($formulario->isSubmitted()) {
            $seleccionadas = $this->habitacionManager->filtrarImagenesSeleccionadas(
                $request->request->all('sel')
            );
            $portada = $request->request->get('portada', '');

            if ($accion === 'subir' && $this->tokenValido($formulario)) {
                try {
                    $nombreSubido = $this->habitacionManager->subirImagen($request->files->get('imagen'));
                    $this->addFlash(
                        'success',
                        'La imagen se subió correctamente y ya está disponible para seleccionar.'
                    );
                } catch (\DomainException $error) {
                    $errorSubida = $error->getMessage();
                    $formulario->addError(new FormError($errorSubida));
                }
            }

            if ($accion === 'guardar' && !$this->excedeTamanoDeCarga($formulario)) {
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
                        $this->habitacionManager->actualizarHabitacionConImagenes(
                            $habitacion,
                            $seleccionadas,
                            $portada
                        );

                        $this->addFlash(
                            'success',
                            'La habitación fue modificada correctamente.'
                        );

                        return $this->redirectToRoute('admin_habitaciones');
                    }
                }
            }
        }

        $respuesta = new Response();

        if ($formulario->isSubmitted()) {
            $respuesta->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->render('admin/habitaciones/form.html.twig', [
            'formulario' => $formulario->createView(),
            'titulo' => $titulo,
            'mostrarImagenes' => true,
            'imagenesDisponibles' => $this->imagenesDisponibles($nombreSubido),
            'seleccionadas' => $seleccionadas,
            'portada' => $portada,
            'errorSubida' => $errorSubida,
            'tamanoMaximo' => HabitacionManager::TAMANO_MAXIMO_BYTES,
        ], $respuesta);
    }

    /**
     * @return array<string, mixed>
     */
    private function opcionesDelFormulario(string $accion): array
    {
        $opciones = [
            'post_max_size_message' => $this->habitacionManager->mensajeTamanoMaximo(),
        ];

        if ($accion === 'actualizar' || $accion === 'subir') {
            $opciones['validation_groups'] = false;
        }

        return $opciones;
    }

    private function accionEnviada(Request $request): string
    {
        $accion = $request->request->get('accion', 'guardar');

        return is_string($accion) ? $accion : 'guardar';
    }

    private function tokenValido(FormInterface $formulario): bool
    {
        if (!$formulario->isSubmitted()) {
            return false;
        }

        foreach ($formulario->getErrors() as $error) {
            if ($error->getCause() instanceof CsrfToken) {
                return false;
            }
        }

        return true;
    }

    private function excedeTamanoDeCarga(FormInterface $formulario): bool
    {
        $mensaje = $this->habitacionManager->mensajeTamanoMaximo();

        foreach ($formulario->getErrors() as $error) {
            if ($error->getMessage() === $mensaje) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function imagenesDisponibles(?string $nombreSubido): array
    {
        $imagenes = $this->habitacionManager->getImagenesDisponibles();

        if ($nombreSubido === null || !in_array($nombreSubido, $imagenes, true)) {
            return $imagenes;
        }

        return array_values(array_unique([$nombreSubido, ...$imagenes]));
    }

    private function recordarHabitacionCreada(Request $request, int $id): void
    {
        $session = $request->getSession();
        $ids = $session->get(self::HABITACIONES_CREADAS, []);
        if (!is_array($ids)) {
            $ids = [];
        }

        $ids[] = $id;
        $session->set(self::HABITACIONES_CREADAS, array_values(array_unique($ids, SORT_REGULAR)));
    }

    private function fueCreadaEnEstaSesion(Request $request, int $id): bool
    {
        $ids = $request->getSession()->get(self::HABITACIONES_CREADAS, []);
        if (!is_array($ids)) {
            return false;
        }

        foreach ($ids as $guardado) {
            if ((is_int($guardado) || (is_string($guardado) && ctype_digit($guardado))) && (int) $guardado === $id) {
                return true;
            }
        }

        return false;
    }
}
