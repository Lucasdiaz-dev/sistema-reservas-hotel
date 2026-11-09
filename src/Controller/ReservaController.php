<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\EstadoReserva;
use App\Manager\ReservaManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ReservaController extends AbstractController
{
    private const CANCELLATION_RESULTS = 'reservation_cancellation_results';

    private ReservaManager $reservaManager;

    public function __construct(ReservaManager $reservaManager)
    {
        $this->reservaManager = $reservaManager;
    }

    #[Route('/habitacion/{id}/reservar', name: 'app_reserva_confirmar', requirements: ['id' => '\d+'], methods: ['GET'])]
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

        $parametros = [
            'habitacion' => $habitacion,
            'ingreso' => $ingreso,
            'egreso' => $egreso,
            'huespedes' => $cantidad,
            'noches' => $ingreso->diff($egreso)->days,
            'total' => $this->reservaManager->calcularTotal($habitacion, $ingreso, $egreso),
        ];

        if (!$this->getUser() instanceof User) {
            $parametros['destino'] = $this->generateUrl('app_reserva_confirmar', [
                'id' => $habitacion->getId(),
                'ingreso' => $ingreso->format('Y-m-d'),
                'egreso' => $egreso->format('Y-m-d'),
                'huespedes' => $cantidad,
            ]);

            return $this->noStore($this->render('reserva/steps.html.twig', $parametros));
        }

        return $this->noStore($this->render('reserva/confirmar.html.twig', $parametros));
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
            $this->reservaManager->cancelarPorCliente($reserva);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('success', $e->getMessage());

            return $this->redirectToRoute('app_mis_reservas');
        } catch (\Throwable) {
            $this->addFlash('success', 'No se pudo actualizar la reserva.');

            return $this->redirectToRoute('app_mis_reservas');
        }

        $estado = $reserva->getEstado();
        if ($estado !== EstadoReserva::CANCELADA && $estado !== EstadoReserva::CANCELACION_PENDIENTE) {
            return $this->redirectToRoute('app_mis_reservas');
        }

        $this->rememberCancellation($request, $reserva->getId(), $estado);

        return $this->redirectToRoute('app_reserva_cancellation', ['id' => $reserva->getId()]);
    }

    #[Route('/reservas/{id}/cancelacion', name: 'app_reserva_cancellation', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function cancellation(int $id, Request $request): Response
    {
        $reserva = $this->reservaManager->obtenerReserva($id);
        $usuario = $this->getUser();

        if ($reserva === null) {
            throw $this->createNotFoundException();
        }

        if (!$usuario instanceof User || $reserva->getUsuario()?->getId() !== $usuario->getId()) {
            throw $this->createAccessDeniedException();
        }

        $estado = $reserva->getEstado();
        $guardado = $this->cancellationResult($request, $id);
        $resultadoValido = $estado === EstadoReserva::CANCELADA || $estado === EstadoReserva::CANCELACION_PENDIENTE;

        if ($guardado === null || $estado === null || $guardado !== $estado || !$resultadoValido) {
            return $this->redirectToRoute('app_mis_reservas');
        }

        return $this->render('reserva/cancellation.html.twig', [
            'reserva' => $reserva,
        ]);
    }

    private function noStore(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
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

    /**
     * Ties the result screen to this session's successful cancellation.
     * Reading it does not repeat the POST.
     */
    private function rememberCancellation(Request $request, ?int $id, EstadoReserva $estado): void
    {
        if ($id === null) {
            return;
        }

        $session = $request->getSession();
        $results = $session->get(self::CANCELLATION_RESULTS, []);
        if (!\is_array($results)) {
            $results = [];
        }

        $results[$id] = $estado->value;
        $session->set(self::CANCELLATION_RESULTS, $results);
    }

    private function cancellationResult(Request $request, int $id): ?EstadoReserva
    {
        $results = $request->getSession()->get(self::CANCELLATION_RESULTS, []);
        if (!\is_array($results) || !isset($results[$id]) || !\is_string($results[$id])) {
            return null;
        }

        return EstadoReserva::tryFrom($results[$id]);
    }
}
