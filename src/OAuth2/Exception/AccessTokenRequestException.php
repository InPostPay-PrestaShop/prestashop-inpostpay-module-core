<?php

declare(strict_types=1);

namespace izi\prestashop\OAuth2\Exception;

use Psr\Http\Message\ResponseInterface;

final class AccessTokenRequestException extends \RuntimeException implements OAuth2ExceptionInterface
{
    /**
     * @var string
     */
    private $error;

    /**
     * @var ResponseInterface
     */
    private $response;

    public function __construct(string $error, ResponseInterface $response, string $message = '', int $code = 0)
    {
        $this->error = $error;
        $this->response = $response;

        parent::__construct($message, $code);
    }

    public static function create(array $data, ResponseInterface $response): self
    {
        $error = $data['error'];
        $description = $data['error_description'] ?? null;

        $message = \sprintf('Access token response error: "%s".', $error);
        if (null !== $description) {
            $message = \sprintf('%s Error description: "%s".', $message, $description);
        }

        return new self($error, $response, $message);
    }

    public function getError(): string
    {
        return $this->error;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
