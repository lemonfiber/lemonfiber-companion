<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Closure;
use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\AKeysName;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\KeepingKeysForApps;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\TheKeysForApps;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhatWasFoundOfTheKeys;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowTheKeysRead;
use Modules\Operator\Internal\ViewModels\TheKeysTurnedOutToBe;
use Modules\Wayfinding\Api\Screens\AsksAgain;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function trim;

/**
 * The keys a stack has minted for apps: what each admits and is for, where it stands, and revoking one.
 *
 * A revoke is asked on this page first, where the question can say that the
 * key stops working at once and that a revoke cannot be undone. No secret is
 * read or shown; the listing carries none.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnlyWhenAsked)]
final class TheKeysAppsHold extends NativeComponent
{
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;
    use DrawsItsTemplate;
    use AsksAgain;

    public const string TEMPLATE = 'operator::the-keys-apps-hold';

    /**
     * What came back, once the frame has asked or a revoke answered.
     *
     * `public`, for the reason {@see WhatIsRunningHere::$answered} gives.
     */
    public ?TheKeysTurnedOutToBe $answered = null;

    /** The name of the key the operator asked to revoke and has not confirmed yet, or empty. */
    public string $revoking = '';

    public function __construct(
        private readonly KeepingKeysForApps $keeping,
        private readonly SecureStorage $storage,
        private readonly Clock $clock,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}

    /** What came back, asked once per frame. */
    public function answer(): TheKeysTurnedOutToBe
    {
        return $this->answered ??= $this->sent(fn(Stack $stack, Session $session): WhatWasFoundOfTheKeys => $this->keeping->listedOn($stack, $session));
    }

    /**
     * The operator asked to revoke the key in that row, which is confirmed before anything is sent.
     *
     * The row and not the name, because the name is the stack's to write and a
     * tap carries its arguments as a quoted expression. A row it does not have,
     * or one whose key cannot be revoked, asks nothing.
     */
    public function askToRevoke(int $which): void
    {
        foreach ($this->answer()->keys as $at => $key) {
            if ($at === $which && $key->mayBeRevoked) {
                $this->revoking = $key->name;
            }
        }
    }

    /** The operator kept the key. */
    public function keepIt(): void
    {
        $this->revoking = '';
    }

    /** The operator confirmed: revoke the key, and show the listing the stack answers with. */
    public function revoke(): void
    {
        $name = $this->revoking;
        $this->revoking = '';

        if (trim($name) === '') {
            return;
        }

        $this->answered = $this->sent(fn(Stack $stack, Session $session): WhatWasFoundOfTheKeys => $this->keeping->revokedOn($stack, $session, AKeysName::of($name)));
    }

    /**
     * Resume the session, ask the stack on it, and flatten what came back.
     *
     * @param Closure(Stack, Session): WhatWasFoundOfTheKeys $asking
     */
    private function sent(Closure $asking): TheKeysTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheKeysTurnedOutToBe => $asking($stack, $session)->either(
                found: fn(TheKeysForApps $keys): TheKeysTurnedOutToBe => new HowTheKeysRead()->these($keys, $this->clock->now()),
                met: $this->lettingGoIfRefused($stack, new HowTheKeysRead()->met(...)),
            ),
            notHeld: static fn(): TheKeysTurnedOutToBe => new HowTheKeysRead()->signedOut(),
        );
    }
}
