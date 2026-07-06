<?php

declare(strict_types=1);

namespace App\Tests\Federation;

use App\Entity\Post;
use App\Entity\User;
use App\Event\PostPublishedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Survos\ActivityPubBundle\Entity\ActivityPubActor;
use Survos\ActivityPubBundle\Repository\ActivityPubActivityRepository;
use Survos\ActivityPubBundle\Repository\ActivityPubActorRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Proves the decoupling actually holds: publishing a Post federates without the
 * activity-pub-bundle ever referencing Post or User directly (see
 * App\EventListener\FederatePostListener, the only class that knows both exist).
 */
final class PostPublishedFederationTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function testPublishingAPostCreatesAFederationActorAndActivity(): void
    {
        $user = new User();
        $user->setEmail('demo@example.com');
        $user->setPassword('irrelevant-for-this-test');
        $this->em->persist($user);
        $this->em->flush();

        $post = new Post(author: $user, title: 'Hello Fediverse', body: 'First post.');
        $this->em->persist($post);
        $this->em->flush();

        self::getContainer()->get(EventDispatcherInterface::class)
            ->dispatch(new PostPublishedEvent($post));

        /** @var ActivityPubActorRepository $actors */
        $actors = self::getContainer()->get(ActivityPubActorRepository::class);
        $actor = $actors->findOneBySubject('user', (string) $user->getId());

        self::assertInstanceOf(ActivityPubActor::class, $actor);
        self::assertSame('demo', $actor->username);

        /** @var ActivityPubActivityRepository $activities */
        $activities = self::getContainer()->get(ActivityPubActivityRepository::class);
        $rows = $activities->findForActor($actor);

        self::assertCount(1, $rows);
        self::assertSame('Add', $rows[0]->type);
        self::assertSame('Hello Fediverse', $rows[0]->payload['object']['name']);
        self::assertStringContainsString('/posts/' . $post->id, $rows[0]->payload['object']['id']);
    }
}
