<?php

namespace izi\prestashop\rest;

use izi\prestashop\DTO\SigningKeyData;
use izi\prestashop\rest\Exception\InvalidSignatureException;
use izi\prestashop\Service\SigningKeysService;
use izi\prestashop\SystemClock;
use Psr\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Request;

class SignatureVerification
{
    private $signingKeysService;
    private $clock;

    public function __construct(SigningKeysService $signingKeysService = null, ClockInterface $clock = null)
    {
        $this->signingKeysService = $signingKeysService ?? new SigningKeysService();
        $this->clock = $clock ?? SystemClock::fromSystemTimezone();
    }

    public function check(Request $request): void
    {
        $publicKeyHash = $this->getHeader($request, 'x-public-key-hash');
        $publicKeyVersion = $this->getHeader($request, 'x-public-key-ver');
        $signature = $this->getHeader($request, 'x-signature');
        $signatureTimestamp = $this->getHeader($request, 'x-signature-timestamp');

        $keyData = $this->getSigningKeyData($publicKeyVersion, $publicKeyHash);
        $publicKey = $this->extractPublicKey($keyData->getKey()->public_key_base64);

        $data = $this->generateData($request->getContent(), $keyData->getMerchantId(), $publicKeyVersion, $signatureTimestamp);
        $this->verifySignature($data, $signature, $publicKey);

        $this->checkTimestamp($signatureTimestamp);
    }

    private function getHeader(Request $request, string $name): string
    {
        if (!$request->headers->has($name)) {
            throw InvalidSignatureException::missingHeader($name);
        }

        return $request->headers->get($name);
    }

    private function getSigningKeyData(string $keyVersion, string $keyHash): SigningKeyData
    {
        $keyData = $this->signingKeysService->getKeyData($keyVersion);

        if (null === $keyData) {
            throw new InvalidSignatureException('Public key version not found.');
        }

        if ($keyHash !== $keyData->getKey()->hash) {
            throw new InvalidSignatureException('Public key hash mismatch.');
        }

        return $keyData;
    }

    private function generateData(string $body, string $merchantId, string $publicKeyVersion, string $signatureTimestamp): string
    {
        $digest = base64_encode(hash('sha256', $body, true));

        return base64_encode("$digest,$merchantId,$publicKeyVersion,$signatureTimestamp");
    }

    private function extractPublicKey(string $base64EncodedKey)
    {
        $pemFormattedKey = "-----BEGIN PUBLIC KEY-----\n" . $base64EncodedKey . "\n-----END PUBLIC KEY-----";
        $publicKey = openssl_pkey_get_public($pemFormattedKey);

        if (false === $publicKey) {
            throw new \RuntimeException(sprintf('Could not extract public key: %s.', openssl_error_string()));
        }

        return $publicKey;
    }

    /**
     * @param resource $publicKey
     */
    private function verifySignature(string $data, string $signature, $publicKey): void
    {
        $result = openssl_verify($data, base64_decode($signature), $publicKey, OPENSSL_ALGO_SHA256);

        switch ($result) {
            case -1:
                throw new \RuntimeException(sprintf('Could not verify signature: %s.', openssl_error_string()));
            case 0:
                throw new InvalidSignatureException('Signature mismatch.');
            default:
                break;
        }
    }

    private function checkTimestamp(string $signatureTimestamp): void
    {
        if (false === $signatureTime = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.uP', $signatureTimestamp)) {
            throw new InvalidSignatureException('Malformed timestamp.');
        }

        if ($signatureTime < $this->clock->now()->sub(new \DateInterval('PT240S'))) {
            throw new InvalidSignatureException('Signature expired.');
        }
    }
}
