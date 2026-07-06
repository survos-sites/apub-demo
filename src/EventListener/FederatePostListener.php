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

        // Mastodon builds its own link-preview card by parsing <a> tags inside
        // content — it doesn't render the structured "attachment" field for a plain
        // link. So the aboutUrl link goes into the HTML body itself; attachmentIri
        // below is a redundant-but-correct structured hint for consumers that do
        // look at it.
        $content = sprintf('<p>%s</p>', nl2br(htmlspecialchars($post->body)));
        if ($post->aboutUrl !== null) {
            $label = htmlspecialchars($post->aboutLabel ?? $post->aboutUrl);
            $content .= sprintf('<p><a href="%s">%s</a></p>', htmlspecialchars($post->aboutUrl), $label);
        }

        $this->activityPub->publishCreate(
            subjectType: 'user',
            subjectId: (string) $post->author->getId(),
            usernameSeed: $post->author->getEmail(),
            objectIri: $this->urlGenerator->generate('app_post_show', ['id' => $post->id], UrlGeneratorInterface::ABSOLUTE_URL),
            content: $content,
            published: $post->published,
            attachmentIri: $post->aboutUrl,
            attachmentName: $post->aboutLabel,
        );
    }
}
