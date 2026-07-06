<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Post;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched right after a Post is persisted. The ActivityPub federation listener
 * (App\EventListener\FederatePostListener — added once activity-pub-bundle's
 * subjectType/subjectId publisher API lands, see survos-sites/scanseum#16) will
 * subscribe to this and translate it into a generic publish() call. Nothing about
 * ActivityPub belongs on Post or PostController themselves.
 */
final class PostPublishedEvent extends Event
{
    public function __construct(
        public readonly Post $post,
    ) {
    }
}
