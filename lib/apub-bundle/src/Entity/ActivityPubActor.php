<?php

declare(strict_types=1);

namespace Survos\ActivityPubBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Survos\ActivityPubBundle\Repository\ActivityPubActorRepository;
use Symfony\Component\Uid\Ulid;

/**
 * One ActivityPub actor (a "Person") per federated subject, created lazily on first
 * federation activity — see ActivityPubActorProvider::forSubject(). subjectType/subjectId
 * is an app-defined pair (e.g. 'user', (string) $user->id) — the same no-FK pattern
 * survos/claims-bundle uses for Claim::subjectType/subjectId. This bundle never
 * references the host app's User class.
 */
#[ORM\Entity(repositoryClass: ActivityPubActorRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_activity_pub_actor_username', fields: ['username'])]
#[ORM\UniqueConstraint(name: 'uniq_activity_pub_actor_subject', fields: ['subjectType', 'subjectId'])]
class ActivityPubActor
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid', unique: true)]
    public readonly Ulid $id;

    #[ORM\Column]
    public readonly \DateTimeImmutable $created;

    public function __construct(
        /** App-defined subject class key. Example: 'user'. No FK is enforced by the bundle. */
        #[ORM\Column(length: 32)]
        public readonly string $subjectType,

        /** Identifier of the subject within its type — whatever the app's PK is, stringified. */
        #[ORM\Column(length: 64)]
        public readonly string $subjectId,

        #[ORM\Column(length: 190)]
        public readonly string $username,

        /** PEM-encoded RSA public key, published on the actor document. */
        #[ORM\Column(type: 'text')]
        public readonly string $publicKeyPem,

        /** PEM-encoded RSA private key — never serialized/exposed via any API. */
        #[ORM\Column(type: 'text')]
        public readonly string $privateKeyPem,
    ) {
        $this->id = new Ulid();
        $this->created = new \DateTimeImmutable();
    }
}
