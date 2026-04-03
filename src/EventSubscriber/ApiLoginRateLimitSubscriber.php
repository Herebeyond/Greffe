<?php

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * Rate limits the /api/login endpoint to prevent brute force attacks.
 * Uses sliding window: 5 attempts per minute per IP.
 */
class ApiLoginRateLimitSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire(service: 'limiter.api_login')]
        private RateLimiterFactory $apiLoginLimiter,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ($request->getPathInfo() !== '/api/login' || $request->getMethod() !== 'POST') {
            return;
        }

        $limiter = $this->apiLoginLimiter->create($request->getClientIp() ?? 'unknown');
        $limit = $limiter->consume(1);

        if (!$limit->isAccepted()) {
            $retryAfter = $limit->getRetryAfter();
            $event->setResponse(new JsonResponse(
                ['error' => 'Trop de tentatives de connexion. Réessayez plus tard.'],
                Response::HTTP_TOO_MANY_REQUESTS,
                ['Retry-After' => $retryAfter->getTimestamp() - time()],
            ));
        }
    }
}
