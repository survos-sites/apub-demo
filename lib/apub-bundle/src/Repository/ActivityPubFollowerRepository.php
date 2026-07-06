<?php

declare(strict_types=1);

namespace Survos\ActivityPubBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Survos\ActivityPubBundle\Entity\ActivityPubActor;
use Survos\ActivityPubBundle\Entity\ActivityPubFollower;

/**
 * @extends ServiceEntityRepository<ActivityPubFollower>
 */
final class ActivityPubFollowerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityPubFollower::class);
    }

    /**
     * @return ActivityPubFollower[]
     */
    public function findForActor(ActivityPubActor $actor): array
    {
        return $this->findBy(['actor' => $actor]);
    }

    public function findOneByActorAndRemoteIri(ActivityPubActor $actor, string $remoteActorIri): ?ActivityPubFollower
    {
        return $this->findOneBy(['actor' => $actor, 'remoteActorIri' => $remoteActorIri]);
    }
}
