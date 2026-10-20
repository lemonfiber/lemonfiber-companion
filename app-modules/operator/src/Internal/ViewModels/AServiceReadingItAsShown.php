<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

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
}
