<?php

declare(strict_types=1);

namespace Survos\ActivityPubBundle\Message;

/**
 * Async delivery of one signed ActivityStreams payload to one follower's inbox.
 * Carries the actor's ID, not its private key — the handler re-fetches the actor and
 * signs there, keeping key material out of the message transport.
 */
final class DeliverActivityMessage
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly string $actorId,
        public readonly string $inboxUrl,
        public readonly array $payload,
    ) {
    }
}
