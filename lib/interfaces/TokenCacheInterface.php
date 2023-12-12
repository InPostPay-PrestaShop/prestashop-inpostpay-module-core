<?php

namespace izi\interfaces;

interface TokenCacheInterface
{
    public function getCachedToken(): ?string;

    public function setCachedToken(?string $token, ?int $expiresIn);
}
