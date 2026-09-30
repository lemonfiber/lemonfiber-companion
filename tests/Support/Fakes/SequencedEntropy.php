<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Device\Api\SystemEntropy;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\KeyMaterial;
use Modules\Kernel\Api\Nonce;

use function sprintf;
use function str_pad;

use const STR_PAD_LEFT;

/**
 * Entropy a test can predict, and that still looks like the real thing.
 *
 * Counting rather than random, which is the point: a test that asserts a
 * command was sent with a particular key has to know what the key will be.
 *
 * The shape is deliberately the adapter's — thirty-two hexadecimal characters
 * — rather than something short and readable like `nonce-1`. A fake that
 * produced a shorter value would be exercising a `Nonce` the application never
 * builds, and the first thing to break on a real device would be a length
 * assumption nothing here had ever met.
 */
final class SequencedEntropy implements Entropy
{
    private int $answered = 0;
    private int $keys = 0;

    private function __construct() {}

    public static function counting(): self
    {
        return new self();
    }

    public function nonce(): Nonce
    {
        $this->answered++;

        return Nonce::of(str_pad(sprintf('%x', $this->answered), SystemEntropy::BYTES * 2, '0', STR_PAD_LEFT));
    }

    /**
     * A key a test can predict, counted apart from the nonces.
     *
     * The adapter's width, thirty-two bytes, for the reason a nonce here is the
     * adapter's width: a shorter one is a key no cipher takes. Counted on its
     * own so that a test asserting how many nonces were handed out is not
     * answered by the keys a seal drew.
     */
    public function aKey(): KeyMaterial
    {
        $this->keys++;

        return KeyMaterial::of(str_pad(sprintf('key-%x', $this->keys), KeyMaterial::BYTES, '0', STR_PAD_LEFT));
    }

    /** How many nonces have been handed out, for a test that cares. */
    public function answered(): int
    {
        return $this->answered;
    }
}
