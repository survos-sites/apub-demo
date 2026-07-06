<?php

declare(strict_types=1);

namespace Survos\ActivityPubBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Survos\ActivityPubBundle\Repository\ActivityPubFollowerRepository;
use Symfony\Component\Uid\Ulid;

/**
 * A remote ActivityPub actor following one of our local actors. Created when their
 * Follow activity is accepted in the inbox — see InboxController.
 */
#[ORM\Entity(repositoryClass: ActivityPubFollowerRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_activity_pub_follower', fields: ['actor', 'remoteActorIri'])]
class ActivityPubFollower
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid', unique: true)]
    public readonly Ulid $id;

    #[ORM\Column]
    public readonly \DateTimeImmutable $created;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: ActivityPubActor::class)]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        public readonly ActivityPubActor $actor,

        #[ORM\Column(length: 500)]
        public readonly string $remoteActorIri,

        #[ORM\Column(length: 500)]
        public readonly string $remoteInboxUrl,
    ) {
        $this->id = new Ulid();
        $this->created = new \DateTimeImmutable();
    }
}
