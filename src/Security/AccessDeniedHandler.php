<?php

namespace App\Security;

use App\Entity\Patient;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Twig\Environment;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(
        private Environment $twig,
        private Security $security,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): Response
    {
        $subject = $accessDeniedException->getSubject();
        $user = $this->security->getUser();

        // If a medical professional is denied access to a patient,
        // redirect them to the break-the-glass form
        if ($subject instanceof Patient && $user instanceof User) {
            $roles = $user->getRoles();
            if (in_array('ROLE_DOCTOR', $roles, true) || in_array('ROLE_NURSE', $roles, true)) {
                return new RedirectResponse(
                    $this->urlGenerator->generate('app_break_the_glass', [
                        'patientId' => $subject->getId(),
                    ])
                );
            }
        }

        return new Response(
            $this->twig->render('bundles/TwigBundle/Exception/error403.html.twig'),
            Response::HTTP_FORBIDDEN,
        );
    }
}
