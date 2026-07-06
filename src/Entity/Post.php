<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PostRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

/**
 * Deliberately simple: a post is public the moment it's created — there's no draft
 * state. "Publishing" is the creation act itself, which is what dispatches
 * PostPublishedEvent for the ActivityPub federation listener to pick up.
 */
#[ORM\Entity(repositoryClass: PostRepository::class)]
final class Post
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid', unique: true)]
    public readonly Ulid $id;

    #[ORM\Column]
    public readonly \DateTimeImmutable $published;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: User::class)]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        public readonly User $author,

        #[ORM\Column(length: 255)]
        public readonly string $title,

        #[ORM\Column(type: 'text')]
        public readonly string $body,

        /** URL of the photo/timeline this post is about — federated as a Note attachment. */
        #[ORM\Column(length: 500, nullable: true)]
        public readonly ?string $aboutUrl = null,

        /** Human-readable label for aboutUrl (e.g. the photo's title). */
        #[ORM\Column(length: 255, nullable: true)]
        public readonly ?string $aboutLabel = null,
    ) {
        $this->id = new Ulid();
        $this->published = new \DateTimeImmutable();
    }
}
