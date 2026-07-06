<?php

declare(strict_types=1);

namespace Survos\ActivityPubBundle\Service;

/**
 * Draft-cavage HTTP Signatures (the scheme used by Mastodon and most of the fediverse) —
 * signs (request-target)/host/date/digest with the actor's RSA private key.
 */
final class ActivityPubHttpSignatureSigner
{
    /**
     * @return array<string, string> headers to merge into the outgoing request
     */
    public function sign(string $method, string $url, string $body, string $keyId, string $privateKeyPem): array
    {
        $parts = parse_url($url);
        $host = $parts['host'] ?? '';
        $path = ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');
        $date = gmdate('D, d M Y H:i:s') . ' GMT';
        $digest = 'SHA-256=' . base64_encode(hash('sha256', $body, true));

        $signingString = implode("\n", [
            '(request-target): ' . strtolower($method) . ' ' . $path,
            'host: ' . $host,
            'date: ' . $date,
            'digest: ' . $digest,
        ]);

        $privateKey = openssl_pkey_get_private($privateKeyPem);
        if ($privateKey === false) {
            throw new \RuntimeException('Invalid private key for HTTP signature: ' . openssl_error_string());
        }

        $signature = '';
        openssl_sign($signingString, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        $signatureHeader = sprintf(
            'keyId="%s",algorithm="rsa-sha256",headers="(request-target) host date digest",signature="%s"',
            $keyId,
            base64_encode($signature),
        );

        return [
            'Host' => $host,
            'Date' => $date,
            'Digest' => $digest,
            'Signature' => $signatureHeader,
        ];
    }
}
