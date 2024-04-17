<?php

declare(strict_types=1);

namespace izi\prestashop\OAuth2\Token;

use izi\prestashop\Clock\SystemClock;
use izi\prestashop\OAuth2\Exception\UnexpectedValueException;
use Psr\Clock\ClockInterface;

/**
 * @mixin AccessTokenFactoryInterface
 */
trait AccessTokenFactoryTrait
{
    /**
     * @var ClockInterface
     */
    private $clock;

    /**
     * @var int|null
     */
    private $defaultExpirationTime;

    public function __construct(?ClockInterface $clock = null, ?int $defaultExpirationTime = null)
    {
        $this->clock = $clock ?? SystemClock::fromSystemTimezone();
        $this->defaultExpirationTime = $defaultExpirationTime;
    }

    private function getExpiresAt(array $data): ?\DateTimeImmutable
    {
        $expiresIn = isset($data['expires_in']) ? (int) $data['expires_in'] : $this->defaultExpirationTime;

        if (null === $expiresIn) {
            return null;
        }

        if (0 > $expiresIn) {
            throw new UnexpectedValueException('Negative access token expiration time.');
        }

        return $this->clock->now()->add(new \DateInterval(sprintf('PT%dS', $expiresIn)));
    }

    private function getScopes(array $data): ?array
    {
        if (!isset($data['scope'])) {
            return null;
        }

        return explode(' ', $data['scope']);
    }
}
