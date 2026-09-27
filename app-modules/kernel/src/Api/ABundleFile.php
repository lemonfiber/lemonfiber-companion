<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use JsonSerializable;

use function mb_strlen;
use function sprintf;

/**
 * A support bundle's file, fetched whole from the stack that wrote it.
 *
 * The archive exactly as the stack served it: nothing here opens it, adds to it
 * or reads it. It is made from an {@see AWrittenBundle} and the bytes that
 * arrived for it, so the only file this can be is one the stack wrote and
 * served; a caller holding some other name and some other bytes has no way to
 * spell one.
 *
 * **It stays in this process.** The bundle is already redacted, and it still
 * holds whatever settings the operator agreed to reveal and every log line the
 * stack took, and it leaves the device only by the operator's own act through
 * {@see Sharing::handOver()}. So a debugger, `json_encode` and a log line see
 * its name and its size and nothing of its contents, and `serialize()` is
 * refused for {@see MustNotLeaveThisProcess}'s reason.
 */
final readonly class ABundleFile implements JsonSerializable
{
    private function __construct(private string $named, private string $bytes) {}

    /**
     * What a debugger prints.
     *
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['named' => $this->named, 'bytes' => $this->hidden()];
    }

    /**
     * What `serialize()` writes, which is nothing, for {@see MustNotLeaveThisProcess}'s reason.
     *
     * @return array<string, never>
     */
    public function __serialize(): array
    {
        throw MustNotLeaveThisProcess::aBundle();
    }

    /**
     * What `unserialize()` reads, which is nothing either.
     *
     * @param array<string, never> $data
     */
    public function __unserialize(array $data): void
    {
        throw MustNotLeaveThisProcess::aBundle();
    }

    /** The file the stack served for the bundle it wrote. */
    public static function fetched(AWrittenBundle $written, string $bytes): self
    {
        return new self($written->name(), $bytes);
    }

    /** What the file is called, which is what the operator's sheet names it. */
    public function named(): string
    {
        return $this->named;
    }

    /** The archive, byte for byte, for the sheet and for nothing else. */
    public function bytes(): string
    {
        return $this->bytes;
    }

    /** What `json_encode` writes: its name, and its contents hidden. */
    public function jsonSerialize(): string
    {
        return sprintf('%s %s', $this->named, $this->hidden());
    }

    /** What stands in for the contents wherever something other than the sheet reads it. */
    private function hidden(): string
    {
        // Counted in bytes, which is what an archive is made of: the `8bit`
        // encoding is the multibyte extension's name for not reading them as
        // characters.
        return sprintf('(%d bytes, hidden)', mb_strlen($this->bytes, '8bit'));
    }
}
