<?php

declare(strict_types=1);

namespace izi\prestashop\Mail\Event;

use izi\prestashop\Event\Event;

final class SendEmailEvent extends Event
{
    /**
     * @var string
     */
    private $template;

    /**
     * @var array<string, mixed>
     */
    private $parameters;

    /**
     * @var string[]
     */
    private $recipients = [];

    /**
     * @var string[]|null
     */
    private $bcc;

    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(string $template, array $parameters)
    {
        $this->template = $template;
        $this->parameters = $parameters;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function hasParameter(string $name): bool
    {
        return array_key_exists($name, $this->parameters);
    }

    /**
     * @return mixed
     */
    public function getParameter(string $name)
    {
        return $this->parameters[$name] ?? null;
    }

    /**
     * @return string[]
     */
    public function getRecipients(): array
    {
        return $this->recipients;
    }

    public function hasRecipient(string $email): bool
    {
        return in_array($email, $this->recipients, true);
    }

    public function setRecipients(string $email, string ...$emails): void
    {
        $this->recipients = array_merge([$email], $emails);
    }

    public function addRecipient(string $email): void
    {
        $this->recipients[] = $email;
    }

    /**
     * @return string[]|null
     */
    public function getBcc(): ?array
    {
        return $this->bcc;
    }

    public function hasBcc(string $email): bool
    {
        if (null === $this->bcc) {
            return false;
        }

        return in_array($email, $this->bcc, true);
    }

    public function setBcc(string ...$emails): void
    {
        $this->bcc = $emails;
    }

    public function addBcc(string $email): void
    {
        if (null === $this->bcc) {
            $this->bcc = [];
        }

        $this->bcc[] = $email;
    }
}
