<?php

declare(strict_types=1);

use Tests\Support\Tree;

// The identity is declared here, and a build cannot pick one.
//
// An application's identity is what the operating system installs it *as*. Two
// builds carrying two identities are two applications: they install beside one
// another, each with its own storage, and the pairing that survives a refused credential refuses to
// discard lives in that storage. A build arriving under a different identity
// does not update the operator's app — it appears next to it, empty, while the
// pairing stays in the one now orphaned, and from inside either app nothing is
// wrong.
//
// It was `env('NATIVEPHP_APP_ID')` with no default. That is a file which is not
// in this repository, which is the environment of whoever ran the build, which
// is the one thing named: two people building one release produced two
// applications, and a build where nobody had set it produced one with no
// identity at all.
//
// {@see WhoThisAppIs} holds the declaration and
// {@see \Modules\Kernel\Api\WhatThisBuildInstallsAs} refuses anything else. The
// two checks below are the join: that the application really runs under the
// declared identity, and that the config cannot go back to reading one.

/** Where the declared identity is applied over whatever the config says. */
const WHERE_THE_IDENTITY_IS_SET = 'bootstrap/Composition/CompositionRoot.php';

it('N1-R53 — the identity is not taken from whoever ran the build', function (): void {
    // Read here rather than at the config, which is the point of the whole
    // change: `config/nativephp.php` is written by `native:install` and is not
    // in this repository, so a rule reading it would be a rule reading a file a
    // clean checkout has not got — green on the one machine that has one.
    $said = (string) file_get_contents(Tree::at(WHERE_THE_IDENTITY_IS_SET));

    preg_match('/WHAT_THE_PLATFORM_INSTALLS_US_AS,\s*(.*?),\n/s', $said, $set);

    $written = $set[1] ?? '';

    // `toBeTrue` with a sentence rather than `toContain` with one: the second
    // argument to `toContain` is another needle, so a message there is a string
    // this rule would go looking for and never find.
    expect(str_contains($written, 'WhatThisBuildInstallsAs::orRefuse'))->toBeTrue(sprintf(
        "The identity in %s is set from `%s`.\n\n"
        . 'It must go through `WhatThisBuildInstallsAs::orRefuse()`, which answers the declared identity '
        . "and refuses a build configured as another application (`N1-R53`).\n",
        WHERE_THE_IDENTITY_IS_SET,
        $written === '' ? 'nothing this rule could read' : $written,
    ));
});
