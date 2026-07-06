<?php

declare(strict_types=1);

namespace Survos\ActivityPubBundle\Controller;

use Survos\ActivityPubBundle\Repository\ActivityPubActorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * No #[Route] attribute here on purpose. WebFinger is a fixed protocol path
 * (RFC 7033, /.well-known/webfinger) that must NOT be prefixed by route_prefix
 * like the rest of this bundle's controllers — see Routing\WebFingerRouteLoader,
 * which registers this route directly with an empty prefix.
 */
final class WebFingerController extends AbstractController
{
    public function __construct(
        private readonly ActivityPubActorRepository $actors,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function resolve(Request $request): Response
    {
        $resource = $request->query->get('resource', '');
        if (!str_starts_with($resource, 'acct:') || !str_contains($resource, '@')) {
            return new JsonResponse(['error' => 'invalid resource'], Response::HTTP_BAD_REQUEST);
        }

        $username = explode('@', substr($resource, 5))[0];
        $actor = $this->actors->findOneByUsername($username)
            ?? throw $this->createNotFoundException($username);

        return new JsonResponse([
            'subject' => $resource,
            'links' => [
                [
                    'rel' => 'self',
                    'type' => 'application/activity+json',
                    'href' => $this->urlGenerator->generate('survos_activity_pub_actor', ['username' => $actor->username], UrlGeneratorInterface::ABSOLUTE_URL),
                ],
            ],
        ], headers: ['Content-Type' => 'application/jrd+json']);
    }
}
