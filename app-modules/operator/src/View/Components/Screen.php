<?php

declare(strict_types=1);

namespace Modules\Operator\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Operator\Internal\WhereAStackIs;

use function view;

/**
 * The chrome every screen sits in.
 *
 * Twelve screens opened with the same column, the same heading, and the same two
 * branches — the session that has ended (`N1-R44`) and the obstacle that stopped
 * the reading (`N1-R10`, `N1-R3`). Written out twelve times those are twelve
 * places for one of them to drift, and `N1-R1` asks for parity across surfaces
 * rather than parity by everybody remembering.
 *
 * **A class rather than an anonymous template.** Blade resolves an anonymous
 * component by guessing a class first, and guessing reads the application
 * namespace off an `app/` directory this repository does not have — so the guess
 * raises before the template is ever looked for. Declaring the class is also
 * what lets the arguments be typed: a destination arrives as
 * {@see WhereAStackIs} and is asked for a route, rather than arriving as a
 * string somebody assembled.
 */
final class Screen extends Component
{
    /**
     * @param  string  $title  what this screen is about, which is the machine
     * @param  string  $met  the obstacle, as the key a screen translates
     * @param  string  $signInGoesTo  where a device with no session is sent
     * @param  string  $askAgain  the screen's own method, named for `@tap`
     * @param  string  $here  which of the four this screen is, or `''` for none
     */
    public function __construct(
        public readonly string $title,
        public readonly bool $signedIn = true,
        public readonly string $met = '',
        public readonly string $remedy = '',
        public readonly string $signInGoesTo = '',
        public readonly string $askAgain = 'again()',
        public readonly string $here = '',
        public readonly ?WhereAStackIs $goes = null,
    ) {}

    public function render(): View
    {
        return view('operator::components.screen');
    }
}
