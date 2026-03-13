<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Twig\Environment;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Environment $twig,
        private string $kernelEnvironment,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 10],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        // In dev mode, let Symfony's debug error handler show the full exception
        if ($this->kernelEnvironment === 'dev') {
            return;
        }

        if (!$event->isMainRequest()) {
            return;
        }

        $exception = $event->getThrowable();

        // Let Symfony's security layer handle AccessDeniedException → AccessDeniedHandler
        if ($exception instanceof AccessDeniedException) {
            return;
        }

        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
        } else {
            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        // 403 for authenticated users is handled by AccessDeniedHandler
        // This catches any remaining 403s (e.g. direct HttpException throws)
        try {
            $template = "bundles/TwigBundle/Exception/error{$statusCode}.html.twig";

            if (!$this->twig->getLoader()->exists($template)) {
                $template = 'bundles/TwigBundle/Exception/error.html.twig';
            }

            $response = new Response(
                $this->twig->render($template, [
                    'status_code' => $statusCode,
                    'status_text' => Response::$statusTexts[$statusCode] ?? 'Erreur',
                ]),
                $statusCode,
            );

            if ($exception instanceof HttpExceptionInterface) {
                $response->headers->add($exception->getHeaders());
            }

            $event->setResponse($response);
        } catch (\Throwable) {
            // If rendering the error template itself fails, let Symfony handle it
        }
    }
}
