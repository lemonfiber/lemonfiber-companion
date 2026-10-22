<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * The paths this app does not read on the actions of the rollback reports the `plugins` envelope carries, and why.
 *
 * Apart from {@see UnreadOnThePluginsEnvelope} for its size. An install that
 * did not hold, a removal and an update each carry a rollback report, read by
 * the reader that reads the `undo` envelope's, so every row here repeats an
 * `UndoEnvelope` row's decision at another place, and says so.
 *
 * Part of {@see WhatThisAppDoesNotRead::rows()}.
 */
final readonly class UnreadOnThePluginRollbacks
{
    /** @var list<array{path: string, because: string}> */
    public const array ROWS = [
        [
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.id',
            'because' => 'For `UndoEnvelope.reversed[].action.id`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
        ],
        [
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.resource',
            'because' => 'For `UndoEnvelope.reversed[].action.resource`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
        ],
        [
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.key',
            'because' => 'For `UndoEnvelope.reversed[].action.key`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
        ],
        [
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.value',
            'because' => 'For `UndoEnvelope.reversed[].action.value`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
        ],
        [
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.wrote',
            'because' => 'For `UndoEnvelope.reversed[].action.wrote`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
        ],
        [
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.path',
            'because' => 'For `UndoEnvelope.reversed[].action.path`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
        ],
        [
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.owner',
            'because' => 'For `UndoEnvelope.reversed[].action.owner`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
        ],
        [
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.written',
            'because' => 'For `UndoEnvelope.reversed[].action.written`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
        ],
        [
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.previous',
            'because' => 'For `UndoEnvelope.reversed[].action.previous`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
        ],
        [
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.current',
            'because' => 'For `UndoEnvelope.reversed[].action.current`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
        ],
        [
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.field',
            'because' => 'For `UndoEnvelope.reversed[].action.field`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
        ],
        [
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.name',
            'because' => 'For `UndoEnvelope.reversed[].action.name`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
        ],
        [
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.recorded',
            'because' => 'For `UndoEnvelope.reversed[].action.recorded`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
        ],
        [
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.earlier',
            'because' => 'For `UndoEnvelope.reversed[].action.earlier`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
        ],
        [
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.filed',
            'because' => 'For `UndoEnvelope.reversed[].action.filed`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
        ],
        [
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.kept',
            'because' => 'For `UndoEnvelope.reversed[].action.kept`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
        ],
        [
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.from',
            'because' => 'For `UndoEnvelope.reversed[].action.from`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
        ],
        [
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.to',
            'because' => 'For `UndoEnvelope.reversed[].action.to`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
        ],
        [
            'path' => 'PluginsEnvelope.install.reversed.reversed[].action.volume',
            'because' => 'For `UndoEnvelope.reversed[].action.volume`\'s reason: the same rollback report, carried inside a plugin install that did not hold.',
        ],
        [
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.id',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.id`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.resource',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.resource`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.key',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.key`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.value',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.value`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.wrote',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.wrote`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.path',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.path`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.owner',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.owner`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.written',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.written`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.previous',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.previous`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.current',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.current`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.field',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.field`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.name',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.name`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.recorded',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.recorded`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.earlier',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.earlier`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.filed',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.filed`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.kept',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.kept`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.from',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.from`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.to',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.to`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.removal.went_back.reversed[].action.volume',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.volume`: what taking the plugin\'s changes off the machine came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.id',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.id`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.resource',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.resource`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.key',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.key`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.value',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.value`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.wrote',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.wrote`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.path',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.path`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.owner',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.owner`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.written',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.written`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.previous',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.previous`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.current',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.current`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.field',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.field`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.name',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.name`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.recorded',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.recorded`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.earlier',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.earlier`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.filed',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.filed`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.kept',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.kept`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.from',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.from`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.to',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.to`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.went_back.reversed[].action.volume',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.volume`: what putting the installed version\'s changes back came to is the same rollback report, read by the same reader.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.id',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.id`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.resource',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.resource`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.key',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.key`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.value',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.value`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.wrote',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.wrote`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.path',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.path`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.owner',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.owner`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.written',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.written`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.previous',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.previous`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.current',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.current`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.field',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.field`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.name',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.name`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.recorded',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.recorded`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.earlier',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.earlier`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.filed',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.filed`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.kept',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.kept`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.from',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.from`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.to',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.to`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
        [
            'path' => 'PluginsEnvelope.update.install.reversed.reversed[].action.volume',
            'because' => 'As `PluginsEnvelope.install.reversed.reversed[].action.volume`: an update carries the new version\'s install account whole, and the one reader reads both.',
        ],
    ];
}
