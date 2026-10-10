<?php

declare(strict_types=1);

use Tests\Support\Tree;

/** The one screen that may show a member the address a join link carries. */
const ASKS_WHETHER_TO_TRUST_IT = 'app-modules/operator/src/Internal/Screens/JoiningAHouse.php';

it('reads the address a join link carries only on the screen that asks whether to trust it', function (): void {
    $readers = [];

    foreach ([...Tree::filesUnder(Tree::at('app-modules'), '.php'), ...Tree::filesUnder(Tree::at('bootstrap'), '.php'), ...Tree::filesUnder(Tree::at('resources'), '.php')] as $file) {
        $relative = str_replace(sprintf('%s/', Tree::root()), '', $file);

        if (! str_contains($relative, '/tests/') && $relative !== 'app-modules/kernel/src/Api/Address.php'
            && str_contains((string) file_get_contents($file), 'forThePersonAskedToTrustIt(')) {
            $readers[] = $relative;
        }
    }

    expect($readers)->toBe([ASKS_WHETHER_TO_TRUST_IT], sprintf(
        "`forThePersonAskedToTrustIt()` is read somewhere other than %s.\n"
        . 'The address is where somebody lives. A member is shown it only where they decide whether '
        . 'somebody in their house sent the link that names it, and never in a log or a report.',
        ASKS_WHETHER_TO_TRUST_IT,
    ));
});
