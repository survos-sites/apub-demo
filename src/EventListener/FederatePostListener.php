<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Event\PostPublishedEvent;
use Survos\ActivityPubBundle\Service\ActivityPubPublisher;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Translates a Post into the bundle's generic publish() call — this is the only class
 * in the app that knows both "Post" and "ActivityPub" exist. The bundle itself never
 * sees a Post.
 */
final class FederatePostListener
{
    public function __construct(
        private readonly ActivityPubPublisher $activityPub,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[AsEventListener]
    public function onPostPublished(PostPublishedEvent $event): void
    {
        $post = $event->post;

        $this->activityPub->publishAdd(
            subjectType: 'user',
            subjectId: (string) $post->author->getId(),
            usernameSeed: $post->author->getEmail(),
            objectIri: $this->urlGenerator->generate('app_post_show', ['id' => $post->id], UrlGeneratorInterface::ABSOLUTE_URL),
            objectName: $post->title,
            published: $post->published,
            targetIri: $this->urlGenerator->generate('app_homepage', [], UrlGeneratorInterface::ABSOLUTE_URL),
            targetName: 'Posts',
        );
    }
}
