<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JWTAuthenticationSuccessSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'lexik_jwt_authentication.on_authentication_success' => 'onAuthenticationSuccess',
        ];
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof User) {
            return;
        }

        $data = $event->getData();
        $data['fullName'] = $user->getFullName();
        $data['roles'] = $user->getRoles();
        $event->setData($data);
    }
}
