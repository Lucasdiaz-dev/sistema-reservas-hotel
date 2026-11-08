<?php

namespace App\Controller\Admin;

use App\Form\FiltroReservasType;
use App\Manager\ReservaManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ReservaController extends AbstractController
{
    private ReservaManager $reservaManager;

    public function __construct(ReservaManager $reservaManager)
    {
        $this->reservaManager = $reservaManager;
    }

    #[Route('/admin/reservas', name: 'admin_reservas', methods: ['GET'])]
    public function listar(Request $request): Response
    {
        $hoy = new \DateTimeImmutable('today');
        $form = $this->createForm(FiltroReservasType::class, [
            'desde' => $hoy->modify('-30 days'),
            'hasta' => $hoy->modify('+90 days'),
        ]);
        $form->handleRequest($request);

        $reservas = null;
        $error = null;
        $desde = null;
        $hasta = null;

        if ($form->isSubmitted() && !$form->isValid()) {
            $error = 'La fecha desde no puede ser posterior a la fecha hasta.';
        } else {
            $desde = $form->get('desde')->getData();
            $hasta = $form->get('hasta')->getData();

            try {
                $this->reservaManager->validarPeriodo($desde, $hasta);
                $reservas = $this->reservaManager->buscarPorPeriodo($desde, $hasta);
            } catch (\InvalidArgumentException $e) {
                $error = $e->getMessage();
            }
        }

        return $this->render('admin/reservas/index.html.twig', [
            'formulario' => $form,
            'reservas' => $reservas,
            'error' => $error,
            'desde' => $desde,
            'hasta' => $hasta,
        ]);
    }

    // Confirmar y rechazar (CU014) es el paso siguiente.
    #[Route('/admin/reservas/{id}/confirmar', name: 'admin_reserva_confirmar', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function confirmar(int $id): Response
    {
        throw $this->createNotFoundException();
    }

    #[Route('/admin/reservas/{id}/rechazar', name: 'admin_reserva_rechazar', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function rechazar(int $id): Response
    {
        throw $this->createNotFoundException();
    }

    // Confirmar, revertir y cancelar (CU015, CU016) vienen después.
    #[Route('/admin/reservas/{id}/confirmar-cancelacion', name: 'admin_reserva_confirmar_cancelacion', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function confirmarCancelacion(int $id): Response
    {
        throw $this->createNotFoundException();
    }

    #[Route('/admin/reservas/{id}/revertir-cancelacion', name: 'admin_reserva_revertir_cancelacion', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function revertirCancelacion(int $id): Response
    {
        throw $this->createNotFoundException();
    }

    #[Route('/admin/reservas/{id}/cancelar', name: 'admin_reserva_cancelar', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function cancelar(int $id): Response
    {
        throw $this->createNotFoundException();
    }
}
