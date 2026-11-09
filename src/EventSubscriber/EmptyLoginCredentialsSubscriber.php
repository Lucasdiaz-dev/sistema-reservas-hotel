<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

/**
 * Rejects a login submit with empty fields before the user lookup,
 * so the message is about the form and not about a missing account.
 */
class EmptyLoginCredentialsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        // After the CSRF check (512) and before the user lookup (256).
        return [CheckPassportEvent::class => ['rejectEmptyCredentials', 384]];
    }

    public function rejectEmptyCredentials(CheckPassportEvent $event): void
    {
        $passport = $event->getPassport();
        if (!$passport->hasBadge(UserBadge::class) || !$passport->hasBadge(PasswordCredentials::class)) {
            return;
        }

        /** @var UserBadge $userBadge */
        $userBadge = $passport->getBadge(UserBadge::class);
        /** @var PasswordCredentials $passwordBadge */
        $passwordBadge = $passport->getBadge(PasswordCredentials::class);

        $emailMissing = '' === trim($userBadge->getUserIdentifier());
        $passwordMissing = '' === $passwordBadge->getPassword();

        if ($emailMissing && $passwordMissing) {
            throw new CustomUserMessageAuthenticationException('Please enter your email and your password.');
        }

        if ($emailMissing) {
            throw new CustomUserMessageAuthenticationException('Please enter your email.');
        }

        if ($passwordMissing) {
            throw new CustomUserMessageAuthenticationException('Please enter your password.');
        }
    }
}
