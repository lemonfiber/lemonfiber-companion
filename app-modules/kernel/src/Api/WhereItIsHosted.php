<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use Closure;

use function trim;

/** Whether a back-end is a hosted service, and if it is, its host and what is sent to use it, word for word. */
final readonly class WhereItIsHosted
{
    /** @param list<string> $leaves */
    private function __construct(private string $host, private array $leaves) {}

    public static function notHosted(): self
    {
        return new self('', []);
    }

    public static function at(string $host, string ...$leaves): self
    {
        if (trim($host) === '') {
            throw WhereAlertsGoSaysNothing::about('a hosted back-end\'s host');
        }

        return new self($host, array_values($leaves));
    }

    /**
     * @template TNotHosted of object
     * @template THosted of object
     *
     * @param  Closure(): TNotHosted                  $notHosted
     * @param  Closure(string, list<string>): THosted $hosted
     * @return TNotHosted|THosted
     */
    public function either(Closure $notHosted, Closure $hosted): object
    {
        return $this->host === '' ? $notHosted() : $hosted($this->host, $this->leaves);
    }
}
