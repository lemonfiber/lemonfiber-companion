<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use function escapeshellarg;
use function preg_match;
use function sprintf;

/** The commands the app names for what is changed at the machine and never from a phone. */
final readonly class TheCommandsAtTheMachine
{
    /** The name the stack's hosting read gives what starts the stack with the machine. */
    public const string AUTOSTART = 'boot';

    private const string INSTALL = 'lemonfiber hosting install %s';

    private const string REMOVE = 'lemonfiber hosting remove %s';

    private const string SET = 'lemonfiber config set %s <value>';

    private const string A_PLAIN_NAME = '/^[A-Za-z_][A-Za-z0-9_]*\z/';

    public static function installingAutostart(): string
    {
        return sprintf(self::INSTALL, self::AUTOSTART);
    }

    public static function removingAutostart(): string
    {
        return sprintf(self::REMOVE, self::AUTOSTART);
    }

    public static function settingACredential(string $key): string
    {
        return sprintf(self::SET, preg_match(self::A_PLAIN_NAME, $key) === 1 ? $key : escapeshellarg($key));
    }
}
