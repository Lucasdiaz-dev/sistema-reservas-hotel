<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\InternalTargetPath;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    private const REGISTRATION_WELCOME = 'registration_welcome';

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager, InternalTargetPath $internalTargetPath): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);
        $targetPath = $internalTargetPath->reservationConfirmPath($request, $request->query->get('_target_path'));

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            $security->login($user, 'form_login', 'main');

            if ($targetPath !== null) {
                $internalTargetPath->forget($request, 'main');

                return $this->redirect($targetPath);
            }

            $request->getSession()->set(self::REGISTRATION_WELCOME, true);

            return $this->redirectToRoute('app_bienvenida');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
            'target_path' => $targetPath,
        ]);
    }

    #[Route('/bienvenida', name: 'app_bienvenida', methods: ['GET'])]
    public function welcome(Request $request): Response
    {
        $user = $this->getUser();
        if (!$request->getSession()->get(self::REGISTRATION_WELCOME) || !$user instanceof User) {
            return $this->redirectToRoute('app_busqueda');
        }

        return $this->render('registration/welcome.html.twig', [
            'nombre' => $user->getNombre(),
        ]);
    }
}
