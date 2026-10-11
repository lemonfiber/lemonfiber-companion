<?php

declare(strict_types=1);

namespace Modules\Dx\Internal;

use function array_key_exists;
use function count;
use function is_array;

use Modules\Kernel\Api\HowAKeyIsScoped;
use Modules\Sdk\Api\Fields\KeysField;
use Modules\Sdk\Api\WireField;

use function sprintf;

/**
 * The integration keys a stand-in stack lists, as a reader can take them: each with a scope the core writes.
 *
 * The `keys` envelope's own declaration types a scope only as text, and a
 * synthesised word is no scope; each key is given one of the three the core
 * writes, in turn.
 */
final readonly class KeysAsTheyRead
{
    /**
     * The envelope, corrected.
     *
     * @param array<string, mixed> $envelope the envelope as synthesised from the declaration
     *
     * @return array<string, mixed>
     */
    public static function from(array $envelope): array
    {
        $data = $envelope['data'];
        $listing = is_array($data) ? $data : [];
        $under = KeysField::Keys->value;
        $scoped = [];

        foreach (array_key_exists($under, $listing) && is_array($listing[$under]) ? $listing[$under] : [] as $key) {
            $scoped[] = [...is_array($key) ? $key : [], WireField::Scope->value => self::scopeAt(count($scoped))];
        }

        $listing[$under] = $scoped;
        $envelope['data'] = $listing;

        return $envelope;
    }

    private static function scopeAt(int $position): string
    {
        $kind = HowAKeyIsScoped::cases()[$position % count(HowAKeyIsScoped::cases())];

        return $kind === HowAKeyIsScoped::AMembersOwn ? sprintf('%s:%s', $kind->value, 'Sanne') : $kind->value;
    }
}
