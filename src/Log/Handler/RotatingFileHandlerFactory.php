<?php

declare(strict_types=1);

namespace izi\prestashop\Log\Handler;

use Monolog\Handler\HandlerInterface;
use Monolog\Handler\RotatingFileHandler;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RotatingFileHandlerFactory extends AbstractHandlerFactory
{
    public function supports(string $type): bool
    {
        return 'rotating_file' === $type;
    }

    protected function doCreate(array $options): HandlerInterface
    {
        $handler = new RotatingFileHandler(
            $options['path'],
            $options['max_files'],
            $options['level'],
            $options['bubble'],
            $options['file_permission'],
            $options['use_locking']
        );

        $handler->setFilenameFormat($options['filename_format'], $options['date_format']);

        return $handler;
    }

    protected function createOptionsResolver(): OptionsResolver
    {
        return parent::createOptionsResolver()
            ->setRequired('path')
            ->setDefaults([
                'max_files' => 0,
                'file_permission' => null,
                'use_locking' => false,
                'filename_format' => '{filename}-{date}',
                'date_format' => 'Y-m-d',
            ])
            ->setNormalizer('file_permission', static function (Options $options, $value) {
                if (!is_string($value)) {
                    return $value;
                }

                if (0 === strpos($value, '0')) {
                    return octdec($value);
                }

                return (int) $value;
            });
    }
}
