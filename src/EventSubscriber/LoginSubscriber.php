<?php

namespace App\EventSubscriber;

use App\Entity\LoginActivity;
use App\Entity\User;
use App\Repository\LoginActivityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

/**
 * Tracks user login events and failures for audit logging.
 */
class LoginSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoginActivityRepository $loginActivityRepository,
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            InteractiveLoginEvent::class => 'onLoginSuccess',
            LoginFailureEvent::class => 'onLoginFailure',
        ];
    }

    /**
     * Record a successful login and update the user's last login time.
     */
    public function onLoginSuccess(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();

        if (!$user instanceof User) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        // Create login activity record
        $activity = new LoginActivity();
        $activity->setUser($user);
        $activity->setUserIdentifier($user->getUserIdentifier());
        $activity->setActivityType(LoginActivity::TYPE_LOGIN);
        $activity->setIpAddress($request?->getClientIp());
        $activity->setUserAgent($request?->headers->get('User-Agent'));
        $activity->setSessionId($request?->getSession()->getId());

        $this->loginActivityRepository->save($activity, false);

        // Update user's last login time
        $user->setLastLoginAt(new \DateTimeImmutable());

        $this->entityManager->flush();
    }

    /**
     * Record a failed login attempt.
     */
    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $request = $event->getRequest();
        $passport = $event->getPassport();

        // Get the identifier that was used for login attempt
        $identifier = null;
        if ($passport !== null) {
            try {
                $badge = $passport->getBadge(\Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge::class);
                $identifier = $badge?->getUserIdentifier();
            } catch (\Exception) {
                // Badge not found, try to get from request
            }
        }

        if ($identifier === null) {
            $identifier = $request->request->get('_username', 'unknown');
        }

        // Get the failure reason
        $exception = $event->getException();
        $details = $exception->getMessage();

        // Create login failure activity record
        $activity = new LoginActivity();
        $activity->setUserIdentifier($identifier);
        $activity->setActivityType(LoginActivity::TYPE_LOGIN_FAILURE);
        $activity->setIpAddress($request->getClientIp());
        $activity->setUserAgent($request->headers->get('User-Agent'));
        $activity->setDetails($details);

        $this->loginActivityRepository->save($activity);
    }
}
