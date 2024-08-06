<?php

declare(strict_types=1);

namespace izi\prestashop\Command\Config;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use izi\prestashop\Configuration\ConsentsConfigurationInterface;
use izi\prestashop\Configuration\DTO\Consent;
use izi\prestashop\Handler\Config\UpdateConsentsConfigurationHandler;
use izi\prestashop\Validator\Unique;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @see UpdateConsentsConfigurationHandler
 */
final class UpdateConsentsConfigurationCommand implements ConsentsConfigurationInterface
{
    public const CONSENTS_COUNT_MAX = 10;

    /**
     * @var Collection<Consent>
     *
     * @Assert\Valid
     * @Assert\Count(max = UpdateConsentsConfigurationCommand::CONSENTS_COUNT_MAX)
     * @Unique(normalizer = {Consent::class, "normalize"})
     */
    private $consents;

    public function __construct(Consent ...$consents)
    {
        $this->consents = new ArrayCollection($consents);
    }

    public function getConsents(?int $shopId = null): array
    {
        return $this->consents->toArray();
    }

    public function addConsent(Consent $consent): self
    {
        if (!$this->consents->contains($consent)) {
            $this->consents->add($consent);
        }

        return $this;
    }

    public function removeConsent(Consent $consent): self
    {
        $this->consents->removeElement($consent);

        return $this;
    }
}
