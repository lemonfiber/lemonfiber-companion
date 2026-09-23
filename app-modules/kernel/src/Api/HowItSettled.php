<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The five things the core can say about how a capability came to be filled.
 *
 * The word, as a closed set. {@see WhatSettledIt} is the reading built from it
 * — the arm that carries the claimants of a contest, the arm that carries who
 * chose and what they chose over — and this is the vocabulary both that and the
 * wire are spelled in. {@see WhoSetIt} is the same pairing one noun over.
 *
 * **These are not degrees of the same thing and are not ordered.** Unlike
 * {@see Severity}, where the declaration order *is* the meaning, nothing here
 * ranks: a capability settled `Outright` is not better than one settled
 * `Chosen`, and a screen that sorted by this would be inventing a judgement the
 * core did not make. There is deliberately no `isWorseThan()`.
 *
 * **`Contested` is the case the whole surface exists for.** The core refuses to
 * pick, on purpose, and says so by sending this word with the claimants beside
 * it. An app that treated it as a failure to be retried, or picked the first
 * claimant to get a screen drawn, would be settling something the core declined
 * to settle — which is exactly what this surface may not do.
 *
 * The five are the core's own, named in `contract/web-api.contract.json`. They
 * are written out here rather than derived from it for {@see Severity}'s
 * reason: this module may not read a file and may not know the SDK exists.
 */
enum HowItSettled: string
{
    /** One service claimed it and nothing else did. */
    case Outright = 'outright';

    /** Several services each fill their own, so there was nothing to choose between. */
    case Each = 'each';

    /** More than one service claims it and the core has not picked — the operator's question. */
    case Contested = 'contested';

    /** It was contested and somebody settled it; the arm says who, and over what. */
    case Chosen = 'chosen';

    /** Nothing claims it. Not a fault — a state. */
    case Unfilled = 'unfilled';
}
