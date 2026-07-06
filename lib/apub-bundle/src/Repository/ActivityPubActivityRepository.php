<?php

declare(strict_types=1);

namespace Survos\ActivityPubBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Survos\ActivityPubBundle\Entity\ActivityPubActivity;
use Survos\ActivityPubBundle\Entity\ActivityPubActor;

/**
 * @extends ServiceEntityRepository<ActivityPubActivity>
 */
final class ActivityPubActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityPubActivity::class);
    }

    /**
     * @return ActivityPubActivity[]
     */
    public function findForActor(ActivityPubActor $actor): array
    {
        return $this->findBy(['actor' => $actor], ['published' => 'DESC']);
    }
}
