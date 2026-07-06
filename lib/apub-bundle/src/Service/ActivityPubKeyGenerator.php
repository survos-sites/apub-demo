<?php

declare(strict_types=1);

namespace Survos\ActivityPubBundle\Service;

final class ActivityPubKeyGenerator
{
    /**
     * @return array{publicKeyPem: string, privateKeyPem: string}
     */
    public function generate(): array
    {
        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        if ($resource === false) {
            throw new \RuntimeException('Failed to generate RSA keypair: ' . openssl_error_string());
        }

        openssl_pkey_export($resource, $privateKeyPem);
        $details = openssl_pkey_get_details($resource);
        if ($details === false) {
            throw new \RuntimeException('Failed to export RSA public key: ' . openssl_error_string());
        }

        return [
            'publicKeyPem' => $details['key'],
            'privateKeyPem' => $privateKeyPem,
        ];
    }
}
