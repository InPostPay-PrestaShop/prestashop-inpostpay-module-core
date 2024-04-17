<?php

declare(strict_types=1);

namespace izi\prestashop\OAuth2\Authentication;

use Symfony\Component\Validator\Constraints as Assert;

final class ClientCredentials implements ClientCredentialsInterface
{
    /**
     * @var string
     *
     * @Assert\NotBlank()
     */
    private $clientId;

    /**
     * @var string|null
     *
     * @Assert\NotBlank()
     */
    private $clientSecret;

    public function __construct(string $clientId, ?string $clientSecret = null)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getClientSecret(): ?string
    {
        return $this->clientSecret;
    }
}
