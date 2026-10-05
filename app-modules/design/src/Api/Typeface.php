<?php

declare(strict_types=1);

namespace Modules\Design\Api;

/**
 * Every face the app bundles, named as a template's `font` attribute names it.
 *
 * Interface text is set in Golos Text, and figures, identifiers, timestamps
 * and log text in DM Mono. Each case is a file in `resources/fonts`, with
 * its family's licence beside it, copied into each platform's bundle when the
 * app is built and never fetched while it runs. One face per weight: a text
 * element names the face its weight is drawn in, so neither platform has a
 * weight to synthesise.
 *
 * Bricolage Grotesque is the brand's display face and is not one of these: it
 * appears only inside the outlined wordmark, which is drawn rather than set.
 */
enum Typeface: string
{
    /** Running text, and the face every widget is drawn in. */
    case Interface = 'GolosText-Regular';

    /** A line that is tapped. */
    case InterfaceMedium = 'GolosText-Medium';

    /** A weighted line, and a heading in the kit. */
    case InterfaceSemiBold = 'GolosText-SemiBold';

    /** A screen's lead line, and the line carrying the weight in a group. */
    case InterfaceBold = 'GolosText-Bold';

    /** A figure, an identifier, a timestamp or log text: what a machine wrote, for a person to read or copy. */
    case Figures = 'DMMono-Regular';

    /** A log line with its level marked beside it. */
    case FiguresMedium = 'DMMono-Medium';
}
