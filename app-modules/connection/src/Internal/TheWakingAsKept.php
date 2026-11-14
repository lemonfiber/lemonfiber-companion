<?php

declare(strict_types=1);

namespace Modules\Connection\Internal;

use function array_key_exists;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;

use Modules\Kernel\Api\ADistributor;
use Modules\Kernel\Api\AnEndpoint;
use Modules\Kernel\Api\APushName;
use Modules\Kernel\Api\AWakeIsUnfit;
use Modules\Kernel\Api\AWakeToRegister;
use Modules\Kernel\Api\AWakingKey;
use Modules\Kernel\Api\AWakingSecret;
use Modules\Kernel\Api\HowThisPhoneIsWoken;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\Unsealed;

use function sodium_base642bin;
use function sodium_bin2base64;

use SodiumException;

/**
 * Whether a stack wakes this phone, written down and read back in every shape ever kept.
 *
 * What does not read degrades toward telling the stack to stop: waking that
 * does not read whole, with its name readable, is read as a removal owed, so
 * the stack is told to forget the endpoint; without a name it is read as off.
 */
final readonly class TheWakingAsKept
{
    /** Where the endpoint's name is written. */
    private const string NAME = 'push_name';

    /** Where the distributor's package is written, while waking is on. */
    private const string DISTRIBUTOR = 'distributor_package';

    /** Where what the distributor is called is written. */
    private const string CALLED = 'distributor_called';

    /** Where the endpoint is written. */
    private const string ENDPOINT = 'push_endpoint';

    /** Where the public key is written, in base64. */
    private const string KEY = 'webpush_key';

    /** Where the secret is written, in base64. */
    private const string SECRET = 'webpush_secret';

    /** What is kept: on, or a removal owed; off is kept as nothing. */
    public static function written(HowThisPhoneIsWoken $waking): Unsealed
    {
        return $waking->either(
            off: static fn(): Unsealed => self::encoded([]),
            on: static fn(ADistributor $through, AWakeToRegister $wake): Unsealed => self::encoded([
                self::NAME => $wake->name->shown(),
                self::DISTRIBUTOR => $through->package(),
                self::CALLED => $through->called(),
                self::ENDPOINT => $wake->endpoint->address(),
                self::KEY => self::inBase64($wake->key->bytes()),
                self::SECRET => self::inBase64($wake->secret->bytes()),
            ]),
            owed: static fn(APushName $name): Unsealed => self::encoded([self::NAME => $name->shown()]),
        );
    }

    public static function read(Shape $shape, Unsealed $value): HowThisPhoneIsWoken
    {
        return match ($shape) {
            Shape::One => self::inShapeOne(json_decode($value->inTheClear(), associative: true)),
        };
    }

    private static function inShapeOne(mixed $written): HowThisPhoneIsWoken
    {
        $fields = is_array($written) ? $written : [];

        try {
            $name = APushName::of(self::textIn($fields, self::NAME));
        } catch (AWakeIsUnfit) {
            return HowThisPhoneIsWoken::off();
        }

        return self::onOrOwed($fields, $name);
    }

    /**
     * On, with everything it was registered with, or owed its removal where any of it does not read.
     *
     * @param array<mixed> $written
     */
    private static function onOrOwed(array $written, APushName $name): HowThisPhoneIsWoken
    {
        try {
            return HowThisPhoneIsWoken::on(
                ADistributor::of(self::textIn($written, self::DISTRIBUTOR), self::textIn($written, self::CALLED)),
                new AWakeToRegister(
                    $name,
                    AnEndpoint::of(self::textIn($written, self::ENDPOINT)),
                    AWakingKey::of(self::bytesIn($written, self::KEY)),
                    AWakingSecret::of(self::bytesIn($written, self::SECRET)),
                ),
            );
        } catch (AWakeIsUnfit) {
            return HowThisPhoneIsWoken::owed($name);
        }
    }

    private static function inBase64(string $bytes): string
    {
        return sodium_bin2base64($bytes, SODIUM_BASE64_VARIANT_ORIGINAL);
    }

    /** @param array<string, string> $fields */
    private static function encoded(array $fields): Unsealed
    {
        return Unsealed::of((string) json_encode($fields));
    }

    /**
     * The text written under this name, or empty where there is none.
     *
     * @param array<mixed> $written
     */
    private static function textIn(array $written, string $name): string
    {
        if (! array_key_exists($name, $written)) {
            return '';
        }

        return is_string($written[$name]) ? $written[$name] : '';
    }

    /**
     * The bytes written under this name in base64, or none where they do not read.
     *
     * @param array<mixed> $written
     */
    private static function bytesIn(array $written, string $name): string
    {
        try {
            return sodium_base642bin(self::textIn($written, $name), SODIUM_BASE64_VARIANT_ORIGINAL);
        } catch (SodiumException) {
            return '';
        }
    }
}
