<?php

namespace App\Controller;

use App\Security\InternalTargetPath;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    private const FIREWALL = 'main';

    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, Request $request, InternalTargetPath $internalTargetPath): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
        $targetPath = $this->targetPath($request, $internalTargetPath);

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'target_path' => $targetPath,
            'continue_reservation' => $internalTargetPath->isReservationConfirm($request, $targetPath),
        ]);
    }

    /**
     * Keeps an internal return URL across a failed attempt. Drops anything off-site.
     */
    private function targetPath(Request $request, InternalTargetPath $internalTargetPath): ?string
    {
        $queryTarget = $request->query->get('_target_path');
        if (\is_string($queryTarget) && $queryTarget !== '') {
            $targetPath = $internalTargetPath->normalize($request, $queryTarget);
            if ($targetPath !== null) {
                $internalTargetPath->remember($request, self::FIREWALL, $targetPath);
            }

            return $targetPath;
        }

        $saved = $internalTargetPath->saved($request, self::FIREWALL);
        $targetPath = $internalTargetPath->normalize($request, $saved);
        if ($targetPath !== null) {
            $internalTargetPath->remember($request, self::FIREWALL, $targetPath);
        } elseif (\is_string($saved) && $saved !== '') {
            $internalTargetPath->forget($request, self::FIREWALL);
        }

        return $targetPath;
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
