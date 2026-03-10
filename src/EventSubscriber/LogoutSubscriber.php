<?php

namespace App\EventSubscriber;

use App\Entity\LoginActivity;
use App\Entity\User;
use App\Repository\LoginActivityRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * Tracks user logout events for audit logging.
 */
class LogoutSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoginActivityRepository $loginActivityRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogout',
        ];
    }

    /**
     * Record a logout event.
     */
    public function onLogout(LogoutEvent $event): void
    {
        $token = $event->getToken();
        
        if ($token === null) {
            return;
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            return;
        }

        $request = $event->getRequest();

        // Create logout activity record
        $activity = new LoginActivity();
        $activity->setUser($user);
        $activity->setUserIdentifier($user->getUserIdentifier());
        $activity->setActivityType(LoginActivity::TYPE_LOGOUT);
        $activity->setIpAddress($request->getClientIp());
        $activity->setUserAgent($request->headers->get('User-Agent'));
        $activity->setSessionId($request->getSession()->getId());

        $this->loginActivityRepository->save($activity);
    }
}
