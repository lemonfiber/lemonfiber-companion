<?php

declare(strict_types=1);

namespace Modules\Dx\Internal;

use function array_key_exists;
use function is_array;
use function is_string;

use Lemonfiber\Sdk\Contract\Api;
use Modules\Kernel\Api\AWakeFingerprint;
use Modules\Sdk\Api\WireField;
use Saloon\Enums\Method;
use Saloon\Http\PendingRequest;

/**
 * A push endpoint a stand-in stack registered, as a stack answers the registration: named by the fingerprint of the address the phone sent.
 *
 * The `push-endpoint` envelope's own declaration types the fingerprint only as
 * text, and the phone confirms its registration by it: a synthesised word is
 * the fingerprint of no address.
 */
final readonly class AWakeAsItReads
{
    /** What a registration calls the address it asks a stack to keep. */
    private const string SENT = 'endpoint';

    /** The address the request asks the stack to keep, or nothing where it registers no push endpoint. */
    public static function registeredBy(PendingRequest $asked): string
    {
        $body = $asked->body()?->all();

        if ($asked->getMethod() !== Method::POST || $asked->getRequest()->resolveEndpoint() !== Api::PUSH_ENDPOINTS_ENDPOINT || ! is_array($body) || ! array_key_exists(self::SENT, $body)) {
            return '';
        }

        return is_string($body[self::SENT]) ? $body[self::SENT] : '';
    }

    /**
     * The envelope, naming the address sent by its fingerprint.
     *
     * @template TKey of array-key
     *
     * @param array<TKey, mixed> $envelope the envelope as synthesised from the declaration
     *
     * @return array<TKey|string, mixed>
     */
    public static function from(array $envelope, string $sent): array
    {
        $data = $envelope['data'];
        $envelope['data'] = [...is_array($data) ? $data : [], WireField::Fingerprint->value => AWakeFingerprint::ofTheAddress($sent)->shown()];

        return $envelope;
    }
}
