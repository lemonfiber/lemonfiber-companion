<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One download client, flattened for a template.
 *
 * Whether it was reached is said by its own field, and a client the stack
 * could not reach carries why, so it is named rather than left off the list.
 */
final readonly class ADownloadClientAsShown
{
    /**
     * @param string $name    the client, by the name the stack knows it under
     * @param string $wasSaid the line for what it said it was doing before it was asked
     * @param string $nowSaid the line for what it read back after, or empty where nothing was read back
     * @param bool   $reached whether the stack reached it
     * @param string $why     why it could not be reached, in its own words, or empty
     */
    public function __construct(
        public string $name,
        public string $wasSaid,
        public string $nowSaid,
        public bool $reached,
        public string $why,
    ) {}
}
