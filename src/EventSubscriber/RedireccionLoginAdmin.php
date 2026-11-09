<?php

namespace App\EventSubscriber;

use App\Security\InternalTargetPath;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class RedireccionLoginAdmin implements EventSubscriberInterface
{
    private UrlGeneratorInterface $urlGenerator;
    private InternalTargetPath $internalTargetPath;

    public function __construct(UrlGeneratorInterface $urlGenerator, InternalTargetPath $internalTargetPath)
    {
        $this->urlGenerator = $urlGenerator;
        $this->internalTargetPath = $internalTargetPath;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'alEntrar',
        ];
    }

    /**
     * Keeps the post-login redirect on an internal route.
     * An admin who signs in from home or from the reservation flow still lands on /admin/reservas.
     * Any other internal page they were trying to open is preserved.
     */
    public function alEntrar(LoginSuccessEvent $event): void
    {
        $response = $event->getResponse();
        if (!$response instanceof RedirectResponse) {
            return;
        }

        $request = $event->getRequest();
        $firewall = $event->getFirewallName();
        $destination = $this->internalTargetPath->normalize($request, $response->getTargetUrl());
        $isAdmin = \in_array('ROLE_ADMIN', $event->getAuthenticatedToken()->getRoleNames(), true);

        if ($isAdmin && ($destination === null || $this->adminReturnsToReservationList($request, $destination))) {
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('admin_reservas')));
            $this->internalTargetPath->forget($request, $firewall);

            return;
        }

        if ($destination === null) {
            $event->setResponse(new RedirectResponse($this->urlGenerator->generate('app_busqueda')));
            $this->internalTargetPath->forget($request, $firewall);

            return;
        }

        $event->setResponse(new RedirectResponse($destination));
        $this->internalTargetPath->forget($request, $firewall);
    }

    private function adminReturnsToReservationList(Request $request, string $destination): bool
    {
        $normalized = $this->internalTargetPath->normalize($request, $destination);
        if ($normalized === null) {
            return true;
        }

        $parts = parse_url($normalized);
        $path = \is_array($parts) ? ($parts['path'] ?? '') : '';

        return $path === $this->urlGenerator->generate('app_busqueda')
            || $this->internalTargetPath->isReservationConfirm($request, $normalized);
    }
}
