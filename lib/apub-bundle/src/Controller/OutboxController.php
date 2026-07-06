<?php

declare(strict_types=1);

namespace Survos\ActivityPubBundle\Controller;

use Survos\ActivityPubBundle\Repository\ActivityPubActivityRepository;
use Survos\ActivityPubBundle\Repository\ActivityPubActorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class OutboxController extends AbstractController
{
    public function __construct(
        private readonly ActivityPubActorRepository $actors,
        private readonly ActivityPubActivityRepository $activities,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/users/{username}/outbox', name: 'survos_activity_pub_outbox', requirements: ['username' => '[A-Za-z0-9_.-]+'])]
    public function show(string $username): JsonResponse
    {
        $actor = $this->actors->findOneByUsername($username)
            ?? throw $this->createNotFoundException($username);

        $items = array_map(
            static fn ($activity) => $activity->payload,
            $this->activities->findForActor($actor),
        );

        return new JsonResponse([
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $this->urlGenerator->generate('survos_activity_pub_outbox', ['username' => $username], UrlGeneratorInterface::ABSOLUTE_URL),
            'type' => 'OrderedCollection',
            'totalItems' => count($items),
            'orderedItems' => $items,
        ], headers: ['Content-Type' => 'application/activity+json']);
    }
}
