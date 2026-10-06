<?php

declare(strict_types=1);

namespace Bootstrap\Composition\NativePHP;

use function array_key_exists;

use Closure;

use function is_string;

use Modules\Household\Internal\Screens\WhatAMemberWouldSee;
use Modules\Household\Internal\Screens\WhatYouCanWatch;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackIsUnidentified;
use Modules\Operator\Internal\Screens\SignIntoAStack;
use Modules\Wayfinding\Api\WhereTappingLeads;

use function str_starts_with;

/**
 * Every screen the navigation stack builds, or the member's own where a member
 * asks for one of the operator's.
 *
 * An operator's screen about a stack is built only where this phone holds the
 * operator's session for that stack, or none: for a stack it holds a member's
 * session for, the member's Home is built in its place, and
 * nothing of the operator's screen is built or asked to read anything. A link,
 * a step back, a kept tab or a route typed by hand all come here first.
 *
 * The core refuses what a member's session may not ask, and that stays the
 * line. This keeps the operator's surface from being drawn for a member at
 * all, so what a member is never shown does not rest on every menu and every
 * link leaving it out.
 *
 * It is asked before the lock, and {@see BehindTheLock} has the last word: a
 * member's screen put in place of an operator's is still the lock while the
 * lock stands. What it reads is whose session the phone holds, as the theme
 * reads it for every screen, and never the session itself.
 *
 * The operator's preview of the member's side is drawn by the household's
 * screens and is the operator's all the same, so it is kept from a member's
 * session by name: a member is somebody, and their side is their own.
 *
 * The navigation stack keeps the path it was asked for beside what this built.
 */
final readonly class OutOfTheOperatorsScreens
{
    /**
     * The operator's screens about a stack that every session opens, and why.
     *
     * @var array<class-string, string>
     */
    public const array EVERY_SESSION_OPENS = [
        SignIntoAStack::class => 'signing in is where a session that ended is renewed, whoever held it',
    ];

    /**
     * Screens outside the operator's namespace that only the operator's session opens, and why.
     *
     * @var array<class-string, string>
     */
    public const array ONLY_THE_OPERATOR_OPENS = [
        WhatAMemberWouldSee::class => 'the preview of the member\'s side is the operator\'s, and a member\'s side is their own',
    ];

    /** Where the operator's screens live, by namespace. */
    private const string THE_OPERATORS = 'Modules\\Operator\\';

    /**
     * @param array<mixed>           $params the route's parameters, as the navigation stack hands them over
     * @param Closure(string): mixed $make   how a screen is made, given its name
     *
     * @param-later-invoked-callable $make
     */
    public function __construct(
        private SecureStorage $keychain,
        private array $params,
        private Closure $make,
    ) {}

    /** The screen asked for, or the member's own in place of the operator's. */
    public function screen(string $asked): mixed
    {
        return ($this->make)($this->isKeptFromAMember($asked) ? WhatYouCanWatch::class : $asked);
    }

    /**
     * Whether the screen asked for is the operator's, about a stack this phone
     * holds a member's session for.
     *
     * A route that names no stack, or names one by anything but text, is about
     * no stack, as {@see TheTheme} reads it.
     */
    private function isKeptFromAMember(string $asked): bool
    {
        $theOperators = (str_starts_with($asked, self::THE_OPERATORS) && ! array_key_exists($asked, self::EVERY_SESSION_OPENS))
            || array_key_exists($asked, self::ONLY_THE_OPERATOR_OPENS);

        if (! $theOperators || ! array_key_exists('stack', $this->params)) {
            return false;
        }

        $named = $this->params['stack'];

        try {
            return is_string($named) && WhereTappingLeads::for($this->keychain, StackId::rememberedAs($named)) === WhereTappingLeads::TheirHome;
        } catch (StackIsUnidentified) {
            return false;
        }
    }
}
