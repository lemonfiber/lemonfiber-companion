<?php

declare(strict_types=1);

namespace Modules\Dx\Internal;

use function basename;
use function dirname;
use function in_array;

use Lemonfiber\Sdk\Contract\Api;
use Saloon\Http\Faking\MockResponse;

/**
 * A title's picture as a stand-in serves it: bytes labelled as an image, at the core's picture paths.
 */
final readonly class APictureAsItIsServed
{
    /** The picture every picture path answers with: one clear pixel, as a PNG. */
    private const string ONE_PIXEL = "\x89\x50\x4e\x47\x0d\x0a\x1a\x0a\x00\x00\x00\x0d\x49\x48\x44\x52\x00\x00\x00\x01\x00\x00\x00\x01\x08\x06\x00\x00\x00\x1f\x15\xc4\x89\x00\x00\x00\x0b\x49\x44\x41\x54\x78\x9c\x63\x60\x00\x02\x00\x00\x05\x00\x01\x7a\x5e\xab\x3f\x00\x00\x00\x00\x49\x45\x4e\x44\xae\x42\x60\x82";

    /** The type a picture is labelled with. */
    private const string LABELLED = 'image/png';

    /** Whether a path is one of the core's picture paths, for whichever title it names. */
    public static function isServedAt(string $endpoint): bool
    {
        $title = basename(dirname($endpoint));

        return in_array($endpoint, [Api::poster($title), Api::backdrop($title), Api::askablePoster($title)], strict: true);
    }

    /** The picture, answered with this status. */
    public static function answered(int $status): MockResponse
    {
        return new MockResponse(self::ONE_PIXEL, $status, ['Content-Type' => self::LABELLED]);
    }
}
