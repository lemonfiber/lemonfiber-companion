<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;

use Closure;
use InvalidArgumentException;

use function is_array;
use function is_string;

use Modules\Kernel\Api\ACredentialGrant;
use Modules\Kernel\Api\AGrantAsked;
use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\APluginId;
use Modules\Kernel\Api\ServiceId;
use Modules\Sdk\Api\Fields\PluginsField;
use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\PluginsAreUnreadable;
use Modules\Sdk\Api\WireField;

/**
 * One grant of a credential to a third-party plugin, as the plugins record and the credentials read both carry it.
 *
 * One reader for both, because a grant is the same four words in each place,
 * and two readers would come to read them differently. Each envelope says how
 * it refuses a row it cannot read.
 */
final readonly class GrantRows
{
    /**
     * One grant to that plugin, refusing a blank word through `$unreadable`.
     *
     * @param array<mixed>                         $row
     * @param Closure(NamesAWireField): InvalidArgumentException $unreadable
     */
    public static function one(array $row, APluginId $plugin, Closure $unreadable): ACredentialGrant
    {
        return ACredentialGrant::of(
            $plugin,
            self::text($row, WireField::Credential, $unreadable),
            ServiceId::called(self::text($row, WireField::Of, $unreadable)),
            self::text($row, WireField::For, $unreadable),
            self::approved($row),
        );
    }

    /**
     * Each credential of another service granted to it, none where the record keeps no list.
     *
     * @param  array<mixed> $row
     * @return list<ACredentialGrant>
     */
    public static function onTheRecord(array $row, APluginId $plugin): array
    {
        if (! array_key_exists(PluginsField::Granted->value, $row) || $row[PluginsField::Granted->value] === null) {
            return [];
        }

        if (! is_array($row[PluginsField::Granted->value])) {
            throw PluginsAreUnreadable::missing(PluginsField::Granted);
        }

        $found = [];

        foreach (InTheOrderSent::rows($row[PluginsField::Granted->value]) as $at => $grant) {
            $unreadable = static fn(NamesAWireField $field): PluginsAreUnreadable => PluginsAreUnreadable::entry(PluginsField::Granted, $field, $at);
            $found[] = is_array($grant) ? self::one($grant, $plugin, $unreadable) : throw $unreadable(WireField::Credential);
        }

        return $found;
    }

    /**
     * Each credential of another service an install would hand the plugin, each with the line it is approved by; none where a stack says nothing of it.
     *
     * @param  array<mixed>      $install
     * @return list<AGrantAsked>
     */
    public static function askedBy(array $install, APluginId $plugin): array
    {
        if (! array_key_exists(PluginsField::Granting->value, $install)) {
            return [];
        }

        if (! is_array($install[PluginsField::Granting->value])) {
            throw PluginsAreUnreadable::missing(PluginsField::Granting);
        }

        $found = [];

        foreach (InTheOrderSent::rows($install[PluginsField::Granting->value]) as $at => $asked) {
            $unreadable = static fn(NamesAWireField $field): PluginsAreUnreadable => PluginsAreUnreadable::entry(PluginsField::Granting, $field, $at);
            $found[] = is_array($asked) ? AGrantAsked::of(self::one($asked, $plugin, $unreadable), self::text($asked, WireField::Approval, $unreadable)) : throw $unreadable(WireField::Credential);
        }

        return $found;
    }

    /**
     * The plugin a grant on the credentials read is to.
     *
     * @param array<mixed>                         $row
     * @param Closure(NamesAWireField): InvalidArgumentException $unreadable
     */
    public static function plugin(array $row, Closure $unreadable): APluginId
    {
        return APluginId::called(self::text($row, WireField::Plugin, $unreadable));
    }

    /**
     * When it was approved, as the record stamps every change: whole seconds since the epoch, unreadable where it is anything else.
     *
     * @param array<mixed> $row
     */
    private static function approved(array $row): AMomentAsWritten
    {
        if (! array_key_exists(WireField::At->value, $row) || ! is_string($row[WireField::At->value])) {
            return AMomentAsWritten::unwritten();
        }

        return AMomentAsWritten::inSeconds($row[WireField::At->value]);
    }

    /**
     * A word a grant must carry.
     *
     * @param array<mixed>                         $row
     * @param Closure(NamesAWireField): InvalidArgumentException $unreadable
     */
    private static function text(array $row, NamesAWireField $field, Closure $unreadable): string
    {
        return Required::text($row, $field, $unreadable($field));
    }
}
