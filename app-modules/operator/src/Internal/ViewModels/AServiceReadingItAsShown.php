<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Kernel\Api\AServiceReadingIt;

/** One service reading a setting or a secret, flattened for a template. */
final readonly class AServiceReadingItAsShown
{
    /**
     * @param string $service           the service
     * @param string $variable          the variable it reads it into
     * @param bool   $declaresADefault  whether its manifest declares a default
     * @param string $default           that default, blank where none is declared
     */
    public function __construct(
        public string $service,
        public string $variable,
        public bool $declaresADefault,
        public string $default,
    ) {}

    /** The reader, as the stack named it. */
    public static function of(AServiceReadingIt $reader): self
    {
        return new self(
            service: $reader->service()->named(),
            variable: $reader->variable(),
            declaresADefault: $reader->default()->isHeld(),
            default: $reader->default()->said(),
        );
    }
}
