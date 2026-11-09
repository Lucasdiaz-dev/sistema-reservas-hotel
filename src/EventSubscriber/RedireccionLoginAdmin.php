<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class RedireccionLoginAdmin implements EventSubscriberInterface
{
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'alEntrar',
        ];
    }

    /**
     * Si el admin entra desde el login, sin una página pendiente, cae en el listado.
     * Si venía de otra URL, se respeta esa vuelta.
     */
    public function alEntrar(LoginSuccessEvent $event): void
    {
        if (!in_array('ROLE_ADMIN', $event->getAuthenticatedToken()->getRoleNames(), true)) {
            return;
        }

        $respuesta = $event->getResponse();
        if (!$respuesta instanceof RedirectResponse) {
            return;
        }

        $camino = parse_url($respuesta->getTargetUrl(), PHP_URL_PATH);
        if ($camino !== $this->urlGenerator->generate('app_busqueda')) {
            return;
        }

        $event->setResponse(new RedirectResponse($this->urlGenerator->generate('admin_reservas')));
    }
}
