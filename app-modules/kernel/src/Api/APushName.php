<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function mb_strtolower;
use function preg_match;
use function preg_replace;
use function rtrim;

/**
 * The name this phone's endpoint is registered under on a stack, which removing it names.
 *
 * Made from the name this phone's key carries on that stack, written as a stack
 * names an endpoint: lower-case letters, digits, dots, dashes and underscores.
 */
final readonly class APushName
{
    /** What a name is, as the stack refuses any other. */
    private const string SHAPE = '/\A[a-z0-9][a-z0-9._-]{0,63}\z/';

    /** The longest name the key's name begins, as the stack names an endpoint. */
    private const string LEADING = '/[a-z0-9][a-z0-9._-]{0,63}/';

    /** A run of what a name cannot hold, written as one dash. */
    private const string UNNAMEABLE = '/[^a-z0-9._-]+/';

    /** What is trimmed off the end, where nothing follows it. */
    private const string LOOSE_ENDS = '._-';

    /** The name where nothing of the key's name can be written. */
    private const string PHONE = 'phone';

    private function __construct(private string $name) {}

    /** The name kept as `$name`, refused where a stack would refuse it. */
    public static function of(string $name): self
    {
        if (preg_match(self::SHAPE, $name) !== 1) {
            throw AWakeIsUnfit::aName();
        }

        return new self($name);
    }

    /** The name the phone's key carries, as a stack names an endpoint. */
    public static function forThePhoneKey(APhoneKeysName $key): self
    {
        $found = [];

        if (preg_match(self::LEADING, (string) preg_replace(self::UNNAMEABLE, '-', mb_strtolower($key->shown())), $found) !== 1) {
            return new self(self::PHONE);
        }

        return new self(rtrim($found[0], self::LOOSE_ENDS));
    }

    /** The name as the stack lists it. */
    public function shown(): string
    {
        return $this->name;
    }
}
