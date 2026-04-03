<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks API access for ROLE_TECH_ADMIN and ROLE_SUPER_ADMIN.
 * These roles manage the system but must NOT access patient data via API.
 */
class ApiAccessSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 8],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // Only apply to API routes (except /api/login and /api/docs)
        if (!str_starts_with($path, '/api') || $path === '/api/login' || str_starts_with($path, '/api/docs')) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if ($token === null) {
            return;
        }

        $user = $token->getUser();
        if ($user === null) {
            return;
        }

        $roles = $token->getRoleNames();

        if (in_array('ROLE_TECH_ADMIN', $roles, true) || in_array('ROLE_SUPER_ADMIN', $roles, true)) {
            $event->setResponse(new JsonResponse(
                ['error' => 'Les administrateurs techniques n\'ont pas accès à l\'API.'],
                Response::HTTP_FORBIDDEN,
            ));
        }
    }
}
