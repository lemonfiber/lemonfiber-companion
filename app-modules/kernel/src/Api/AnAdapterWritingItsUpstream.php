<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * An adapter handed the whole configuration directory of the service it fronts, which writes that service's files.
 */
final readonly class AnAdapterWritingItsUpstream
{
    private function __construct(private string $adapter, private string $upstream, private string $at) {}

    /** The adapter, the service whose files it writes, and where that service's directory lands inside it; a blank one is refused. */
    public static function by(string $adapter, string $upstream, string $at): self
    {
        foreach (['adapter' => $adapter, 'upstream' => $upstream, 'at' => $at] as $field => $said) {
            if (trim($said) === '') {
                throw PluginSaysNothing::about($field);
            }
        }

        return new self($adapter, $upstream, $at);
    }

    /** The adapter. */
    public function adapter(): string
    {
        return $this->adapter;
    }

    /** The service whose files it writes. */
    public function upstream(): string
    {
        return $this->upstream;
    }

    /** Where that service's configuration directory lands inside the adapter. */
    public function at(): string
    {
        return $this->at;
    }
}
