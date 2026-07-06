<?php

declare(strict_types=1);

namespace Survos\ActivityPubBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Survos\ActivityPubBundle\Entity\ActivityPubActor;
use Survos\ActivityPubBundle\Repository\ActivityPubActorRepository;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Get-or-create for a federated subject's ActivityPub actor — created lazily on first
 * federation activity (a keypair is generated then, not at signup). The bundle never
 * sees the app's User (or any other) entity: callers pass a subjectType/subjectId pair
 * plus a usernameSeed string (e.g. an email) the app already resolved.
 */
final class ActivityPubActorProvider
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ActivityPubActorRepository $actors,
        private readonly ActivityPubKeyGenerator $keyGenerator,
        private readonly SluggerInterface $slugger,
    ) {
    }

    public function forSubject(string $subjectType, string $subjectId, string $usernameSeed): ActivityPubActor
    {
        $existing = $this->actors->findOneBySubject($subjectType, $subjectId);
        if ($existing !== null) {
            return $existing;
        }

        $keys = $this->keyGenerator->generate();
        $actor = new ActivityPubActor(
            subjectType: $subjectType,
            subjectId: $subjectId,
            username: $this->uniqueUsername($usernameSeed),
            publicKeyPem: $keys['publicKeyPem'],
            privateKeyPem: $keys['privateKeyPem'],
        );
        $this->em->persist($actor);
        $this->em->flush();

        return $actor;
    }

    private function uniqueUsername(string $usernameSeed): string
    {
        $local = explode('@', $usernameSeed)[0] ?: $usernameSeed;
        $base = (string) $this->slugger->slug($local)->lower();

        $candidate = $base;
        $suffix = 2;
        while ($this->actors->findOneByUsername($candidate) !== null) {
            $candidate = $base . '-' . $suffix++;
        }

        return $candidate;
    }
}
