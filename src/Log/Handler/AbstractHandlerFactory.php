<?php

declare(strict_types=1);

namespace izi\prestashop\Log\Handler;

use Monolog\Handler\HandlerInterface;
use Monolog\Logger;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractHandlerFactory implements HandlerFactoryInterface
{
    private $environment;
    private $optionsResolver;

    public function __construct(string $environment)
    {
        $this->environment = $environment;
    }

    public function create(array $options): HandlerInterface
    {
        $options = $this->getOptionsResolver()->resolve($options);

        return $this->doCreate($options);
    }

    protected function createOptionsResolver(): OptionsResolver
    {
        return (new OptionsResolver())
            ->setDefaults([
                'level' => 'prod' === $this->environment ? Logger::INFO : Logger::DEBUG,
                'bubble' => true,
            ])
            ->setNormalizer('level', static function (Options $options, $value) {
                if (null === $value || is_numeric($value)) {
                    return $value;
                }

                $upper = strtoupper($value);
                $constName = \sprintf('%s::%s', Logger::class, $upper);

                if (\defined($constName)) {
                    return \constant($constName);
                }

                throw new \InvalidArgumentException(\sprintf('Could not match "%s" to a log level.', $value));
            });
    }

    abstract protected function doCreate(array $options): HandlerInterface;

    private function getOptionsResolver(): OptionsResolver
    {
        return $this->optionsResolver ?? ($this->optionsResolver = $this->createOptionsResolver());
    }
}
