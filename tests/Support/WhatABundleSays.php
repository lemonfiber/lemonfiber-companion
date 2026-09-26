<?php

declare(strict_types=1);

namespace Tests\Support;

use function implode;

use Modules\Kernel\Api\ABundle;
use Modules\Kernel\Api\APieceOfABundle;
use Modules\Kernel\Api\ASettingToReveal;
use Modules\Kernel\Api\Remarks;
use Modules\Kernel\Api\SettingsToReveal;
use Modules\Kernel\Api\ThePiecesOfABundle;
use Modules\Kernel\Api\TheTermsOfABundle;
use Modules\Kernel\Api\WhatFilenamesShow;
use Modules\Kernel\Api\WhenABundleWasTaken;
use Modules\Kernel\Api\WhereABundleIs;

use function sprintf;

/**
 * A support bundle, as a stack sends one and as one line of text.
 *
 * One bundle for every suite that reads one, so the payload a contract test
 * sends, the value a fake hands back and the line a case compares all describe
 * the same bundle.
 */
final readonly class WhatABundleSays
{
    /** Where the bundle a stack describes would be written. */
    public const string WOULD_GO = '/home/op/.config/lemonfiber/bundles/lemonfiber-support-2026-09-26T10-00-00Z.tar.gz';

    /** The sentence a stack refuses a bundle holding a credential with. */
    public const string A_LEAK = 'The bundle still held something that reads as a credential';

    private function __construct(public string $said) {}

    /**
     * The `data` of a described bundle, changed where a case says.
     *
     * @param  array<string, mixed> $changed
     * @return array<string, mixed>
     */
    public static function payload(array $changed = []): array
    {
        return [
            'bytes' => 48_213,
            'would_go' => self::WOULD_GO,
            'contents' => self::contents(),
            ...$changed,
        ];
    }

    /**
     * The `data` of a described bundle, with its contents changed where a case says.
     *
     * @param  array<string, mixed> $changed
     * @return array<string, mixed>
     */
    public static function payloadWhoseContents(array $changed): array
    {
        return self::payload(['contents' => [...self::contents(), ...$changed]]);
    }

    /**
     * That bundle in the envelope it arrives in.
     *
     * @param  array<string, mixed> $changed
     * @return array<string, mixed>
     */
    public static function envelope(array $changed = []): array
    {
        return ['api_version' => 1, 'kind' => 'bundle', 'data' => self::payload($changed)];
    }

    /** The bundle {@see payload()} describes, as the kernel holds it. */
    public static function described(): ABundle
    {
        return self::at(WhereABundleIs::wouldGo(self::WOULD_GO));
    }

    /** The same bundle, written where it said it would go. */
    public static function written(): ABundle
    {
        return self::at(WhereABundleIs::writtenAt(self::WOULD_GO));
    }

    /**
     * Every part of a bundle folded to one line, so two bundles can be compared as text.
     */
    public static function of(ABundle $bundle): string
    {
        $pieces = [];

        foreach ($bundle->pieces() as $piece) {
            $pieces[] = sprintf('%s=%s', $piece->name(), $piece->body());
        }

        return sprintf(
            '%d|%s|%s|%s|%s|%s|%s|%s|%s,%s,%s',
            $bundle->bytes(),
            $bundle->where()->either(
                wouldGo: static fn(string $path): self => new self(sprintf('would go %s', $path)),
                written: static fn(string $path): self => new self(sprintf('written %s', $path)),
                unsaid: static fn(): self => new self('unsaid'),
            )->said,
            $bundle->terms()->window(),
            $bundle->terms()->filenames()->value,
            implode(',', self::namesOf($bundle->terms()->revealed())),
            implode(';', $pieces),
            implode(',', [...$bundle->missing()]),
            'taken',
            $bundle->taken()->moment(),
            $bundle->taken()->lemonfiber(),
            $bundle->taken()->stack(),
        );
    }

    /**
     * The names of the settings, in their order.
     *
     * @return list<string>
     */
    public static function namesOf(SettingsToReveal $settings): array
    {
        $names = [];

        foreach ($settings as $setting) {
            $names[] = $setting->name();
        }

        return $names;
    }

    /**
     * What the described bundle holds.
     *
     * @return array<string, mixed>
     */
    private static function contents(): array
    {
        return [
            'pieces' => [
                ['name' => 'diagnosis.txt', 'body' => "storage.one-filesystem: passed\n\nvpn.killswitch: failed"],
                ['name' => 'services.txt', 'body' => 'sonarr running'],
            ],
            'missing' => ['the container engine could not be reached'],
            'taken' => ['at' => '2026-09-26T10:00:00Z', 'lemonfiber' => '1.4.0', 'stack' => '2026.09'],
            'terms' => ['window' => 'the last 200 lines of each service', 'filenames' => false, 'revealed' => ['SONARR_URL']],
        ];
    }

    private static function at(WhereABundleIs $where): ABundle
    {
        return ABundle::reported(
            48_213,
            $where,
            TheTermsOfABundle::stated(
                'the last 200 lines of each service',
                WhatFilenamesShow::Replaced,
                SettingsToReveal::none()->with(ASettingToReveal::named('SONARR_URL')),
            ),
            ThePiecesOfABundle::of(
                APieceOfABundle::of('diagnosis.txt', "storage.one-filesystem: passed\n\nvpn.killswitch: failed"),
                APieceOfABundle::of('services.txt', 'sonarr running'),
            ),
            Remarks::of('the container engine could not be reached'),
            WhenABundleWasTaken::at('2026-09-26T10:00:00Z', '1.4.0', '2026.09'),
        );
    }
}
