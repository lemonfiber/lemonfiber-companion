<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

/**
 * Why the camera read nothing, and whether asking again could change that.
 *
 * Two facts that are only ever true together. {@see Scanned::nothing()} takes
 * both and {@see Scanned::either()} hands both back, so the pairing is already
 * what the public surface says — this is the same pairing said once more where
 * the state lives, instead of as two fields that happen to be set at the same
 * time.
 *
 * **Two loose fields leave one of them unholdable.** With `mayAskAgain` beside
 * a nullable reason, the arm that reads a code has to name a boolean too — and
 * on that arm nothing reads it. Any value does; no test can tell one from
 * another. That is the same defect the constant in `Scanned` is written
 * against, arriving through the other field: a line whose change nothing
 * notices. Bound to the reason, it cannot be set where there is no reason to
 * set it.
 *
 * Read-only and public, because it is a pair rather than an object with a job.
 * Nothing outside {@see Scanned} builds one — the camera's two facts reach a
 * screen through `either()`, which is the check that cannot be skipped.
 */
final readonly class TheRefusal
{
    public function __construct(
        public WhyNothingWasRead $why,
        public bool $mayAskAgain,
    ) {}
}
