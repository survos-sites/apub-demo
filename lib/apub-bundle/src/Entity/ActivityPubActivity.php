<?php

declare(strict_types=1);

namespace Survos\ActivityPubBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Survos\ActivityPubBundle\Repository\ActivityPubActivityRepository;
use Symfony\Component\Uid\Ulid;

/**
 * One outbox entry — a persisted ActivityStreams activity, published by one of our
 * local actors.
 */
#[ORM\Entity(repositoryClass: ActivityPubActivityRepository::class)]
class ActivityPubActivity
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid', unique: true)]
    public readonly Ulid $id;

    #[ORM\Column]
    public readonly \DateTimeImmutable $published;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: ActivityPubActor::class)]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        public readonly ActivityPubActor $actor,

        #[ORM\Column(length: 32)]
        public readonly string $type,

        /** @var array<string, mixed> full ActivityStreams JSON-LD body */
        #[ORM\Column(type: Types::JSON)]
        public readonly array $payload,

        /**
         * Pass explicitly when the payload's own "id" field (a public IRI delivered to
         * followers) must match this row's id exactly — see ActivityPubPublisher, which
         * mints the Ulid first so the JSON-LD "id" and this row's PK are the same value.
         */
        ?Ulid $id = null,
    ) {
        $this->id = $id ?? new Ulid();
        $this->published = new \DateTimeImmutable();
    }
}
