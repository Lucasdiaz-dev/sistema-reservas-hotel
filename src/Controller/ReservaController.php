<?php

namespace App\Controller;

use App\Manager\ReservaManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ReservaController extends AbstractController
{
    private ReservaManager $reservaManager;

    public function __construct(ReservaManager $reservaManager)
    {
        $this->reservaManager = $reservaManager;
    }

    #[Route('/habitacion/{id}/reservar', name: 'app_reserva_confirmar', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function confirmar(int $id, Request $request): Response
    {
        try {
            $habitacion = $this->reservaManager->obtenerHabitacionActiva($id);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('success', $e->getMessage());

            return $this->redirectToRoute('app_busqueda');
        }

        $ingreso = $this->fechaDeConsulta($request->query->get('ingreso'));
        $egreso = $this->fechaDeConsulta($request->query->get('egreso'));
        $huespedes = $request->query->get('huespedes');
        $cantidad = is_numeric($huespedes) ? (int) $huespedes : 0;

        try {
            if ($ingreso === null || $egreso === null) {
                throw new \InvalidArgumentException('La fecha de egreso debe ser posterior a la fecha de ingreso.');
            }

            $this->reservaManager->validarParaReserva($habitacion, $ingreso, $egreso, $cantidad);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('success', $e->getMessage());

            return $this->redirectToRoute('app_habitacion_detalle', $this->parametrosDeVuelta($habitacion->getId(), $request));
        }

        return $this->render('reserva/confirmar.html.twig', [
            'habitacion' => $habitacion,
            'ingreso' => $ingreso,
            'egreso' => $egreso,
            'huespedes' => $cantidad,
            'noches' => $ingreso->diff($egreso)->days,
            'total' => $this->reservaManager->calcularTotal($habitacion, $ingreso, $egreso),
        ]);
    }

    // El POST que registra la reserva (CU005) es el paso siguiente.
    #[Route('/habitacion/{id}/reservar', name: 'app_reserva_registrar', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function registrar(int $id): Response
    {
        throw $this->createNotFoundException();
    }

    private function fechaDeConsulta(mixed $valor): ?\DateTimeImmutable
    {
        if (!\is_string($valor) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            return null;
        }

        $fecha = \DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
        $errores = \DateTimeImmutable::getLastErrors();
        if ($fecha === false || ($errores && ($errores['warning_count'] > 0 || $errores['error_count'] > 0))) {
            return null;
        }

        return $fecha;
    }

    /**
     * @return array<string, int|string>
     */
    private function parametrosDeVuelta(int $id, Request $request): array
    {
        $parametros = ['id' => $id];

        foreach (['ingreso', 'egreso', 'huespedes'] as $campo) {
            $valor = $request->query->get($campo);
            if (\is_string($valor) && $valor !== '') {
                $parametros[$campo] = $valor;
            }
        }

        return $parametros;
    }
}
