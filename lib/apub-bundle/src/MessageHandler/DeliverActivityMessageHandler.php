<?php

declare(strict_types=1);

namespace Survos\ActivityPubBundle\MessageHandler;

use Survos\ActivityPubBundle\Message\DeliverActivityMessage;
use Survos\ActivityPubBundle\Repository\ActivityPubActorRepository;
use Survos\ActivityPubBundle\Service\ActivityPubHttpSignatureSigner;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsMessageHandler]
final class DeliverActivityMessageHandler
{
    public function __construct(
        private readonly ActivityPubActorRepository $actors,
        private readonly ActivityPubHttpSignatureSigner $signer,
        private readonly HttpClientInterface $http,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(DeliverActivityMessage $message): void
    {
        $actor = $this->actors->find($message->actorId);
        if ($actor === null) {
            return;
        }

        $body = json_encode($message->payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $keyId = $this->urlGenerator->generate(
            'survos_activity_pub_actor',
            ['username' => $actor->username],
            UrlGeneratorInterface::ABSOLUTE_URL,
        ) . '#main-key';

        $headers = $this->signer->sign('POST', $message->inboxUrl, $body, $keyId, $actor->privateKeyPem);
        $headers['Content-Type'] = 'application/activity+json';

        $this->http->request('POST', $message->inboxUrl, [
            'headers' => $headers,
            'body' => $body,
        ]);
    }
}
