<?php

declare(strict_types=1);

namespace izi\prestashop\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @author Yevgeniy Zholkevskiy <zhenya.zholkevskiy@gmail.com>
 */
final class Unique extends Constraint
{
    /**
     * @var string
     */
    public $message = 'This collection should contain only unique elements.';

    /**
     * @var callable
     */
    public $normalizer;

    public function __construct(?array $options = null, ?string $message = null, ?callable $normalizer = null)
    {
        parent::__construct($options);

        $this->message = $message ?? $this->message;
        $this->normalizer = $normalizer ?? $this->normalizer;

        if (null !== $this->normalizer && !is_callable($this->normalizer)) {
            throw new InvalidArgumentException(sprintf('The "normalizer" option must be a valid callable ("%s" given).', get_debug_type($this->normalizer)));
        }
    }
}
