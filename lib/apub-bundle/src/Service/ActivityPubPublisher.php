<?php

declare(strict_types=1);

namespace Survos\ActivityPubBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Survos\ActivityPubBundle\Entity\ActivityPubActivity;
use Survos\ActivityPubBundle\Message\DeliverActivityMessage;
use Survos\ActivityPubBundle\Repository\ActivityPubFollowerRepository;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Ulid;

/**
 * Federates the creation of something as an ActivityStreams "Add" activity — the
 * vocabulary's own primitive for "object added to target collection". Takes plain
 * scalars only: the bundle has no knowledge of Bookmark, Post, or any other app
 * entity. The caller (an app-side event listener) resolves IRIs/names before calling
 * this.
 */
final class ActivityPubPublisher
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ActivityPubActorProvider $actorProvider,
        private readonly ActivityPubFollowerRepository $followers,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly MessageBusInterface $bus,
    ) {
    }

    public function publishAdd(
        string $subjectType,
        string $subjectId,
        string $usernameSeed,
        string $objectIri,
        string $objectName,
        \DateTimeImmutable $published,
        ?string $targetIri = null,
        ?string $targetName = null,
    ): void {
        $actor = $this->actorProvider->forSubject($subjectType, $subjectId, $usernameSeed);
        $actorIri = $this->urlGenerator->generate(
            'survos_activity_pub_actor',
            ['username' => $actor->username],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $target = $targetIri !== null ? array_filter([
            'id' => $targetIri,
            'type' => 'Collection',
            'name' => $targetName,
        ], static fn (mixed $value): bool => $value !== null) : null;

        // Minted up front so the row's PK and the JSON-LD "id" we broadcast are the
        // same value — see ActivityPubActivity::$id.
        $activityId = new Ulid();

        $payload = array_filter([
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => $actorIri . '/activities/' . $activityId,
            'type' => 'Add',
            'actor' => $actorIri,
            'published' => $published->format(DATE_ATOM),
            'object' => [
                'id' => $objectIri,
                'type' => 'Object',
                'name' => $objectName,
                'url' => $objectIri,
            ],
            'target' => $target,
            'to' => ['https://www.w3.org/ns/activitystreams#Public', $actorIri . '/followers'],
        ], static fn (mixed $value): bool => $value !== null);

        $activity = new ActivityPubActivity(actor: $actor, type: 'Add', payload: $payload, id: $activityId);
        $this->em->persist($activity);
        $this->em->flush();

        foreach ($this->followers->findForActor($actor) as $follower) {
            $this->bus->dispatch(new DeliverActivityMessage(
                actorId: (string) $actor->id,
                inboxUrl: $follower->remoteInboxUrl,
                payload: $payload,
            ));
        }
    }
}
