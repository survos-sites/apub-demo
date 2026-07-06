<?php

declare(strict_types=1);

namespace Survos\ActivityPubBundle\Controller;

use Survos\ActivityPubBundle\Entity\ActivityPubActor;
use Survos\ActivityPubBundle\Entity\ActivityPubFollower;
use Survos\ActivityPubBundle\Message\DeliverActivityMessage;
use Survos\ActivityPubBundle\Repository\ActivityPubActorRepository;
use Survos\ActivityPubBundle\Repository\ActivityPubFollowerRepository;
use Survos\ActivityPubBundle\Service\ActivityPubHttpSignatureVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class InboxController extends AbstractController
{
    public function __construct(
        private readonly ActivityPubActorRepository $actors,
        private readonly ActivityPubFollowerRepository $followers,
        private readonly ActivityPubHttpSignatureVerifier $verifier,
        private readonly EntityManagerInterface $em,
        private readonly HttpClientInterface $http,
        private readonly MessageBusInterface $bus,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/users/{username}/inbox', name: 'survos_activity_pub_inbox', requirements: ['username' => '[A-Za-z0-9_.-]+'], methods: ['POST'])]
    public function receive(string $username, Request $request): Response
    {
        $actor = $this->actors->findOneByUsername($username)
            ?? throw $this->createNotFoundException($username);

        if (!$this->verifier->verify($request)) {
            return new JsonResponse(['error' => 'invalid signature'], Response::HTTP_UNAUTHORIZED);
        }

        $body = json_decode($request->getContent(), true);
        if (!is_array($body) || !isset($body['type'])) {
            return new JsonResponse(['error' => 'invalid activity'], Response::HTTP_BAD_REQUEST);
        }

        match ($body['type']) {
            'Follow' => $this->handleFollow($actor, $username, $body),
            'Undo' => $this->handleUndo($actor, $body),
            default => null, // other activity types (Like, Create, ...) aren't handled yet
        };

        return new Response('', Response::HTTP_ACCEPTED);
    }

    /** @param array<string, mixed> $follow */
    private function handleFollow(ActivityPubActor $actor, string $username, array $follow): void
    {
        $remoteActorIri = is_string($follow['actor'] ?? null) ? $follow['actor'] : null;
        if ($remoteActorIri === null || $this->followers->findOneByActorAndRemoteIri($actor, $remoteActorIri) !== null) {
            return;
        }

        $remoteInboxUrl = $this->fetchInboxUrl($remoteActorIri);
        if ($remoteInboxUrl === null) {
            return;
        }

        $this->em->persist(new ActivityPubFollower(actor: $actor, remoteActorIri: $remoteActorIri, remoteInboxUrl: $remoteInboxUrl));
        $this->em->flush();

        $actorIri = $this->urlGenerator->generate('survos_activity_pub_actor', ['username' => $username], UrlGeneratorInterface::ABSOLUTE_URL);
        $accept = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $actorIri . '/activities/' . new Ulid(),
            'type' => 'Accept',
            'actor' => $actorIri,
            'object' => $follow,
        ];

        $this->bus->dispatch(new DeliverActivityMessage(actorId: (string) $actor->id, inboxUrl: $remoteInboxUrl, payload: $accept));
    }

    /** @param array<string, mixed> $undo */
    private function handleUndo(ActivityPubActor $actor, array $undo): void
    {
        $object = $undo['object'] ?? null;
        if (!is_array($object) || ($object['type'] ?? null) !== 'Follow') {
            return;
        }

        $remoteActorIri = is_string($object['actor'] ?? null) ? $object['actor'] : null;
        if ($remoteActorIri === null) {
            return;
        }

        $follower = $this->followers->findOneByActorAndRemoteIri($actor, $remoteActorIri);
        if ($follower !== null) {
            $this->em->remove($follower);
            $this->em->flush();
        }
    }

    private function fetchInboxUrl(string $remoteActorIri): ?string
    {
        try {
            $data = $this->http->request('GET', $remoteActorIri, [
                'headers' => ['Accept' => 'application/activity+json'],
            ])->toArray();
        } catch (\Throwable) {
            return null;
        }

        return is_string($data['inbox'] ?? null) ? $data['inbox'] : null;
    }
}
