<?php

declare(strict_types=1);

namespace Survos\ActivityPubBundle\Controller;

use Survos\ActivityPubBundle\Repository\ActivityPubActorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ActorController extends AbstractController
{
    public function __construct(
        private readonly ActivityPubActorRepository $actors,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/users/{username}', name: 'survos_activity_pub_actor', requirements: ['username' => '[A-Za-z0-9_.-]+'])]
    public function show(string $username): JsonResponse
    {
        $actor = $this->actors->findOneByUsername($username)
            ?? throw $this->createNotFoundException($username);

        $actorIri = $this->urlGenerator->generate('survos_activity_pub_actor', ['username' => $username], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse([
            '@context' => [
                'https://www.w3.org/ns/activitystreams',
                'https://w3id.org/security/v1',
            ],
            'id' => $actorIri,
            'type' => 'Person',
            'preferredUsername' => $actor->username,
            'inbox' => $this->urlGenerator->generate('survos_activity_pub_inbox', ['username' => $username], UrlGeneratorInterface::ABSOLUTE_URL),
            'outbox' => $this->urlGenerator->generate('survos_activity_pub_outbox', ['username' => $username], UrlGeneratorInterface::ABSOLUTE_URL),
            'followers' => $actorIri . '/followers',
            'publicKey' => [
                'id' => $actorIri . '#main-key',
                'owner' => $actorIri,
                'publicKeyPem' => $actor->publicKeyPem,
            ],
        ], headers: ['Content-Type' => 'application/activity+json']);
    }
}
