<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;
use Modules\Sdk\Internal\TheWordsItReads;

use function sprintf;

final class PluginCatalogueIsUnreadable extends InvalidArgumentException
{
    /** A field it needs is missing or not what the contract says, named by its path. */
    public static function missing(string $path): self
    {
        return new self(sprintf(
            'The plugin-catalogue envelope has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $path,
        ));
    }

    /** A word it has no case for. */
    public static function word(NamesAWireField $field, string $said, string ...$accepted): self
    {
        return new self(sprintf(
            'The plugin-catalogue envelope says its `%s` is `%s`, and this app reads %s.',
            $field->value,
            $said,
            TheWordsItReads::quoted(...$accepted),
        ));
    }
}
