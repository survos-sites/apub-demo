<?php

declare(strict_types=1);

namespace Survos\ActivityPubBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Survos\ActivityPubBundle\Entity\ActivityPubActor;

/**
 * @extends ServiceEntityRepository<ActivityPubActor>
 */
final class ActivityPubActorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityPubActor::class);
    }

    public function findOneBySubject(string $subjectType, string $subjectId): ?ActivityPubActor
    {
        return $this->findOneBy(['subjectType' => $subjectType, 'subjectId' => $subjectId]);
    }

    public function findOneByUsername(string $username): ?ActivityPubActor
    {
        return $this->findOneBy(['username' => $username]);
    }
}
