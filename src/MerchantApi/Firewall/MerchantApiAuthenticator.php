<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Firewall;

use izi\prestashop\BasketApp\Signature\Response\PublicKey;
use izi\prestashop\BasketApp\Signature\Response\SigningKey;
use izi\prestashop\MerchantApi\Exception\InvalidSignatureException;
use Psr\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Request;

final class MerchantApiAuthenticator
{
    private $signingKeysService;
    private $clock;

    public function __construct(SigningKeysServiceInterface $signingKeysService, ClockInterface $clock)
    {
        $this->signingKeysService = $signingKeysService;
        $this->clock = $clock;
    }

    public function authenticate(Request $request): void
    {
        $publicKeyHash = $this->getHeader($request, 'x-public-key-hash');
        $publicKeyVersion = $this->getHeader($request, 'x-public-key-ver');
        $signature = $this->getHeader($request, 'x-signature');
        $signatureTimestamp = $this->getHeader($request, 'x-signature-timestamp');

        $signingKey = $this->getSigningKey($publicKeyVersion, $publicKeyHash);
        $publicKey = $this->extractPublicKey($signingKey->getPublicKey());

        $data = $this->generateData($signingKey, $request->getContent(), $signatureTimestamp);
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

    private function getSigningKey(string $version, string $hash): SigningKey
    {
        $key = $this->signingKeysService->getSigningKey($version);

        if (null === $key) {
            throw new InvalidSignatureException('Could not find public key with given version.');
        }

        if ($hash !== $key->getPublicKey()->getHash()) {
            throw new InvalidSignatureException('Public key hash mismatch.');
        }

        return $key;
    }

    private function generateData(SigningKey $key, string $body, string $signatureTimestamp): string
    {
        $digest = base64_encode(hash('sha256', $body, true));

        return base64_encode(sprintf(
            '%s,%s,%s,%s',
            $digest,
            $key->getMerchantId(),
            $key->getPublicKey()->getVersion(),
            $signatureTimestamp
        ));
    }

    private function extractPublicKey(PublicKey $key)
    {
        $publicKey = openssl_pkey_get_public($key->getPemFormatted());

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
        if (false === $signatureTime = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.vP', $signatureTimestamp)) {
            throw new InvalidSignatureException('Malformed timestamp.');
        }

        if ($signatureTime < $this->clock->now()->sub(new \DateInterval('PT240S'))) {
            throw new InvalidSignatureException('Signature expired.');
        }
    }
}
