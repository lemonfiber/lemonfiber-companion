<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Where the operator finds a UnifiedPush distributor to install, for a phone that has none.
 *
 * ntfy's own page: the self-hosted distributor lemonfiber points to first.
 */
final readonly class ADistributorToInstall
{
    /** ntfy's page. */
    private const string NTFY = 'https://ntfy.sh/';

    private function __construct(private string $page) {}

    public static function ntfy(): self
    {
        return new self(self::NTFY);
    }

    /** What a code of it carries, which is the page's address. */
    public function carried(): string
    {
        return $this->page;
    }
}
