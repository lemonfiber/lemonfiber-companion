<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;

use Closure;

use function is_array;
use function is_bool;
use function is_string;

use Modules\Kernel\Api\ACredentialGrant;
use Modules\Kernel\Api\ACredentialRevoke;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Sdk\Api\CredentialsIsUnreadable;
use Modules\Sdk\Api\Fields\CredentialsField;
use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\WireField;

use function trim;

/**
 * The grants the `credentials` envelope lists, and what ending one would come to or came to.
 *
 * Apart from {@see \Modules\Sdk\Api\CredentialsKept} for its size: a grant is
 * read through {@see GrantRows}, as the plugins record's are, and refused with
 * the credentials envelope's own words.
 */
final readonly class CredentialGrantsRead
{
    /**
     * Every grant of a credential to a third-party plugin; none at all is an answer.
     *
     * @param  array<mixed>           $data
     * @return list<ACredentialGrant>
     */
    public static function listed(array $data): array
    {
        if (! array_key_exists(WireField::Grants->value, $data) || ! is_array($data[WireField::Grants->value])) {
            throw CredentialsIsUnreadable::missing(WireField::Grants);
        }

        $found = [];

        foreach (InTheOrderSent::rows($data[WireField::Grants->value]) as $position => $row) {
            $found[] = self::grant($row, static fn(NamesAWireField $field): CredentialsIsUnreadable => CredentialsIsUnreadable::grant(WireField::Grants, $field, $position));
        }

        return $found;
    }

    /**
     * What ending one grant would come to or came to, which an answer to ending one must carry.
     *
     * @param array<mixed> $data
     */
    public static function revoked(array $data): ACredentialRevoke
    {
        if (! array_key_exists(WireField::Revoked->value, $data) || ! is_array($data[WireField::Revoked->value])) {
            throw CredentialsIsUnreadable::missing(WireField::Revoked);
        }

        $revoked = $data[WireField::Revoked->value];

        $agreement = self::agreement($revoked);
        if (! array_key_exists(CredentialsField::Grant->value, $revoked)) {
            throw CredentialsIsUnreadable::missing(CredentialsField::Grant);
        }

        $grant = self::grant($revoked[CredentialsField::Grant->value], static fn(NamesAWireField $field): CredentialsIsUnreadable => CredentialsIsUnreadable::within(CredentialsField::Grant, $field));
        $restarts = Services::these(...self::restarts($revoked));

        return self::ended($revoked)
            ? ACredentialRevoke::ended($agreement, $grant, $restarts, self::unrestarted($revoked))
            : ACredentialRevoke::read($agreement, $grant, $restarts);
    }

    /**
     * The name the reading goes by.
     *
     * @param array<mixed> $revoked
     */
    private static function agreement(array $revoked): string
    {
        if (! array_key_exists(WireField::Agreement->value, $revoked) || ! is_string($revoked[WireField::Agreement->value])) {
            throw CredentialsIsUnreadable::missing(WireField::Agreement);
        }

        return $revoked[WireField::Agreement->value];
    }

    /**
     * Whether the grant was ended, rather than read.
     *
     * @param array<mixed> $revoked
     */
    private static function ended(array $revoked): bool
    {
        if (! array_key_exists(WireField::Ended->value, $revoked) || ! is_bool($revoked[WireField::Ended->value])) {
            throw CredentialsIsUnreadable::missing(WireField::Ended);
        }

        return $revoked[WireField::Ended->value];
    }

    /**
     * Why the services did not restart, blank where the stack said nothing.
     *
     * @param array<mixed> $revoked
     */
    private static function unrestarted(array $revoked): string
    {
        if (! array_key_exists(WireField::Unrestarted->value, $revoked) || $revoked[WireField::Unrestarted->value] === null) {
            return '';
        }

        if (! is_string($revoked[WireField::Unrestarted->value])) {
            throw CredentialsIsUnreadable::missing(WireField::Unrestarted);
        }

        return $revoked[WireField::Unrestarted->value];
    }

    /**
     * The plugin's services ending the grant restarts.
     *
     * @param  array<mixed>    $revoked
     * @return list<ServiceId>
     */
    private static function restarts(array $revoked): array
    {
        if (! array_key_exists(WireField::Restarts->value, $revoked) || ! is_array($revoked[WireField::Restarts->value])) {
            throw CredentialsIsUnreadable::missing(WireField::Restarts);
        }

        $found = [];

        foreach ($revoked[WireField::Restarts->value] as $service) {
            $found[] = is_string($service) && trim($service) !== '' ? ServiceId::called($service) : throw CredentialsIsUnreadable::missing(WireField::Restarts);
        }

        return $found;
    }

    /**
     * One grant, on the list or the one a revoke ends, refused in the words of where it sits.
     *
     * @param Closure(NamesAWireField): CredentialsIsUnreadable $unreadable
     */
    private static function grant(mixed $row, Closure $unreadable): ACredentialGrant
    {
        if (! is_array($row)) {
            throw $unreadable(WireField::Plugin);
        }

        return GrantRows::one($row, GrantRows::plugin($row, $unreadable), $unreadable);
    }
}
