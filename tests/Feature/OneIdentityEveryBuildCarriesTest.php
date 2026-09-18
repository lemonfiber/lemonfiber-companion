<?php

declare(strict_types=1);

use Bootstrap\Composition\ThisBuildIsSomebodyElse;
use Bootstrap\Composition\WhatThisBuildInstallsAs;
use Modules\Kernel\Api\WhoThisAppIs;

// One identity, declared here, that a build cannot choose.
//
// Two builds carrying two identities are two applications: they install beside
// one another, each with its own storage, and the pairing that survives a refused credential refuses to
// discard lives in that storage. A build under the wrong identity does not
// update the operator's app — it appears next to it, empty, while the pairing
// stays in the one now orphaned, and from inside either app nothing is wrong.

it('N1-R52 — a build that names nothing installs as this application', function (): void {
    // The ordinary case. A build that names nothing is not a build with no
    // identity: the declaration is what it installs as, and an environment that
    // says nothing has nothing to disagree with.
    expect(WhatThisBuildInstallsAs::orRefuse(null))->toBe(WhoThisAppIs::IDENTITY)
        ->and(WhatThisBuildInstallsAs::orRefuse(''))->toBe(WhoThisAppIs::IDENTITY)
        ->and(WhatThisBuildInstallsAs::orRefuse('   '))->toBe(WhoThisAppIs::IDENTITY);
});

it('N1-R52 — a build that names this application agrees with it', function (): void {
    expect(WhatThisBuildInstallsAs::orRefuse(WhoThisAppIs::IDENTITY))->toBe(WhoThisAppIs::IDENTITY);
});

it('N1-R53 — a build that names another application is refused, naming both', function (): void {
    // Refused rather than ignored, and that is the point: a configured value
    // silently dropped is the same failure as one silently used, because
    // somebody set it believing it did something.
    expect(fn(): string => WhatThisBuildInstallsAs::orRefuse('app.somebody.else'))
        ->toThrow(ThisBuildIsSomebodyElse::class, 'app.somebody.else')
        ->and(fn(): string => WhatThisBuildInstallsAs::orRefuse('app.somebody.else'))
        ->toThrow(ThisBuildIsSomebodyElse::class, WhoThisAppIs::IDENTITY);
});

it('N1-R52 — a value that is not text names nothing', function (): void {
    // An environment carries text and an env file can carry anything; a number
    // is *nothing configured* rather than a second identity.
    expect(WhatThisBuildInstallsAs::orRefuse(42))->toBe(WhoThisAppIs::IDENTITY);
});

it('N1-R52 — the identity the application runs under is the one declared once', function (): void {
    // The join between the declaration and what the platform is actually
    // handed. Without it this file would describe a constant nothing reads.
    expect(config('nativephp.app_id'))->toBe(WhoThisAppIs::IDENTITY);
});
