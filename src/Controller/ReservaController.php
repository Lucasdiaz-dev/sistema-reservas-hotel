<?php

namespace App\Controller;

use App\Entity\User;
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

            return $this->redirectToRoute('app_habitacion_detalle', $this->parametrosDeVuelta($habitacion->getId(), $request->query));
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

    #[Route('/habitacion/{id}/reservar', name: 'app_reserva_registrar', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function registrar(int $id, Request $request): Response
    {
        $token = $request->request->get('_token');
        if (!\is_string($token) || !$this->isCsrfTokenValid('reserva'.$id, $token)) {
            throw $this->createAccessDeniedException();
        }

        $usuario = $this->getUser();
        if (!$usuario instanceof User) {
            throw $this->createAccessDeniedException();
        }

        try {
            $habitacion = $this->reservaManager->obtenerHabitacionActiva($id);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('success', $e->getMessage());

            return $this->redirectToRoute('app_busqueda');
        }

        $ingreso = $this->fechaDeConsulta($request->request->get('ingreso'));
        $egreso = $this->fechaDeConsulta($request->request->get('egreso'));
        $huespedes = $request->request->get('huespedes');
        $cantidad = is_numeric($huespedes) ? (int) $huespedes : 0;

        try {
            if ($ingreso === null || $egreso === null) {
                throw new \InvalidArgumentException('La fecha de egreso debe ser posterior a la fecha de ingreso.');
            }

            $reserva = $this->reservaManager->registrar($habitacion, $usuario, $ingreso, $egreso, $cantidad);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('success', $e->getMessage());

            return $this->redirectToRoute('app_habitacion_detalle', $this->parametrosDeVuelta($habitacion->getId(), $request->request));
        } catch (\Throwable) {
            $this->addFlash('success', 'No se pudo registrar la reserva. Intentá de nuevo.');

            return $this->redirectToRoute('app_reserva_confirmar', $this->parametrosDeVuelta($habitacion->getId(), $request->request));
        }

        return $this->redirectToRoute('app_reserva_pendiente', ['id' => $reserva->getId()]);
    }

    #[Route('/reservas/{id}/pendiente', name: 'app_reserva_pendiente', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function pendiente(int $id): Response
    {
        $reserva = $this->reservaManager->obtenerReserva($id);
        $usuario = $this->getUser();

        if ($reserva === null) {
            throw $this->createNotFoundException();
        }

        if (!$usuario instanceof User || $reserva->getUsuario()?->getId() !== $usuario->getId()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('reserva/pendiente.html.twig', [
            'reserva' => $reserva,
        ]);
    }

    #[Route('/reservas/{id}/cancelar', name: 'app_reserva_cancelar', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function cancelar(int $id, Request $request): Response
    {
        $token = $request->request->get('_token');
        if (!\is_string($token) || !$this->isCsrfTokenValid('reserva'.$id, $token)) {
            throw $this->createAccessDeniedException();
        }

        $reserva = $this->reservaManager->obtenerReserva($id);
        $usuario = $this->getUser();

        if ($reserva === null) {
            throw $this->createNotFoundException();
        }

        if (!$usuario instanceof User || $reserva->getUsuario()?->getId() !== $usuario->getId()) {
            throw $this->createAccessDeniedException();
        }

        try {
            $mensaje = $this->reservaManager->cancelarPorCliente($reserva);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('success', $e->getMessage());

            return $this->redirectToRoute('app_mis_reservas');
        } catch (\Throwable) {
            $this->addFlash('success', 'No se pudo actualizar la reserva.');

            return $this->redirectToRoute('app_mis_reservas');
        }

        $this->addFlash('success', $mensaje);

        return $this->redirectToRoute('app_mis_reservas');
    }

    #[Route('/reservas', name: 'app_mis_reservas', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function misReservas(): Response
    {
        $usuario = $this->getUser();
        if (!$usuario instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $grupos = $this->reservaManager->listarDeUsuario($usuario);

        return $this->render('mis_reservas/index.html.twig', [
            'proximas' => $grupos['proximas'],
            'canceladas' => $grupos['canceladas'],
        ]);
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
    private function parametrosDeVuelta(int $id, \Symfony\Component\HttpFoundation\ParameterBag $origen): array
    {
        $parametros = ['id' => $id];

        foreach (['ingreso', 'egreso', 'huespedes'] as $campo) {
            $valor = $origen->get($campo);
            if (\is_string($valor) && $valor !== '') {
                $parametros[$campo] = $valor;
            }
        }

        return $parametros;
    }
}
