<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Post;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('demo@example.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'demo1234'));
        $manager->persist($user);

        $posts = [
            ['Hello, Fediverse', "This is the first post from the ActivityPub demo app.\n\nPublishing it dispatches an event that federates it as an ActivityStreams \"Add\" activity."],
            ['Decoupled by design', "The activity-pub-bundle never references Post or User directly — it only sees subjectType/subjectId scalars and IRIs, resolved by App\\EventListener\\FederatePostListener."],
        ];

        foreach ($posts as [$title, $body]) {
            $manager->persist(new Post(author: $user, title: $title, body: $body));
        }

        $manager->flush();
    }
}
