<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;
use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\AskingWhatIsLeftAlone;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowTheAreasLeftAloneRead;
use Modules\Operator\Internal\ViewModels\TheAreasLeftAloneAsShown;
use Modules\Wayfinding\Api\Screens\AsksAgain;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\ReadsAStackOnceAFrame;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/** Every area the operator told the stack to leave alone, each with their reason and what it covers now, to the operator alone and to read only. */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnlyWhenAsked)]
final class WhatIsLeftAloneHere extends NativeComponent
{
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;
    use ReadsAStackOnceAFrame;
    use AsksAgain;

    public ?TheAreasLeftAloneAsShown $answered = null;

    public function __construct(
        private readonly AskingWhatIsLeftAlone $asking,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}

    public function render(): View
    {
        $this->aFrameBegins();

        return view('operator::what-is-left-alone-here');
    }

    /** What the stack said it leaves alone, asked once a frame. */
    public function answer(): TheAreasLeftAloneAsShown
    {
        if (! $this->answered instanceof TheAreasLeftAloneAsShown) {
            $this->readsItsStack();
            $stack = $this->stack();
            $this->answered = $this->storage->resume($stack->id())->either(
                held: fn(Session $session): TheAreasLeftAloneAsShown => $this->asked($stack, $session),
                notHeld: static fn(): TheAreasLeftAloneAsShown => new HowTheAreasLeftAloneRead()->signedOut(),
            );
        }

        return $this->answered;
    }

    private function asked(Stack $stack, Session $session): TheAreasLeftAloneAsShown
    {
        return $this->asking->leftAloneOn($stack, $session)->either(
            listed: new HowTheAreasLeftAloneRead()->these(...),
            notCarried: new HowTheAreasLeftAloneRead()->notCarried(...),
            met: $this->lettingGoIfRefused($stack, new HowTheAreasLeftAloneRead()->met(...)),
        );
    }
}
