<?php

declare(strict_types=1);

namespace Survos\ActivityPubBundle\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Verifies an incoming inbox POST's Signature header against the sender's public key,
 * fetched from their actor document (draft-cavage HTTP Signatures).
 */
final class ActivityPubHttpSignatureVerifier
{
    public function __construct(
        private readonly HttpClientInterface $http,
    ) {
    }

    public function verify(Request $request): bool
    {
        $sigHeader = $request->headers->get('Signature');
        if ($sigHeader === null) {
            return false;
        }

        $parsed = $this->parseSignatureHeader($sigHeader);
        $keyId = $parsed['keyId'] ?? null;
        $signatureB64 = $parsed['signature'] ?? null;
        if ($keyId === null || $signatureB64 === null) {
            return false;
        }

        $publicKeyPem = $this->fetchPublicKey($keyId);
        if ($publicKeyPem === null) {
            return false;
        }

        $headerList = explode(' ', $parsed['headers'] ?? '(request-target) host date');
        $signingString = $this->buildSigningString($request, $headerList);

        $publicKey = openssl_pkey_get_public($publicKeyPem);
        if ($publicKey === false) {
            return false;
        }

        return openssl_verify($signingString, base64_decode($signatureB64), $publicKey, OPENSSL_ALGO_SHA256) === 1;
    }

    /** @param string[] $headerList */
    private function buildSigningString(Request $request, array $headerList): string
    {
        $lines = [];
        foreach ($headerList as $header) {
            $header = trim($header);
            $lines[] = $header === '(request-target)'
                ? '(request-target): ' . strtolower($request->getMethod()) . ' ' . $request->getPathInfo()
                : $header . ': ' . $request->headers->get($header, '');
        }

        return implode("\n", $lines);
    }

    /** @return array<string, string> */
    private function parseSignatureHeader(string $header): array
    {
        $result = [];
        foreach (explode(',', $header) as $pair) {
            if (preg_match('/^(\w+)="(.*)"$/', trim($pair), $matches)) {
                $result[$matches[1]] = $matches[2];
            }
        }

        return $result;
    }

    private function fetchPublicKey(string $keyId): ?string
    {
        $actorIri = explode('#', $keyId)[0];
        try {
            $data = $this->http->request('GET', $actorIri, [
                'headers' => ['Accept' => 'application/activity+json'],
            ])->toArray();
        } catch (\Throwable) {
            return null;
        }

        return is_string($data['publicKey']['publicKeyPem'] ?? null) ? $data['publicKey']['publicKeyPem'] : null;
    }
}
