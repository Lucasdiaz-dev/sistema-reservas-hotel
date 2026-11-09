<?php

namespace App\Twig;

use App\Entity\Reserva;
use App\Entity\User;
use App\Manager\ReservaManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ReservaExtension extends AbstractExtension
{
    private ReservaManager $reservaManager;

    public function __construct(ReservaManager $reservaManager)
    {
        $this->reservaManager = $reservaManager;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('puede_cancelar', $this->puedeCancelar(...)),
        ];
    }

    public function puedeCancelar(Reserva $reserva, User $usuario): bool
    {
        return $this->reservaManager->puedeCancelarCliente($reserva, $usuario);
    }
}
