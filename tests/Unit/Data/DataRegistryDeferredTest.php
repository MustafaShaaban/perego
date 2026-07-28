<?php

/**
 * Deferred data sources resolve on first read, not at registry construction.
 *
 * The managed-table sources used to be looped into the registry while the singleton was being built —
 * during boot, because the Overview renderer resolves it there. That sealed the source list before any
 * app booting later could register a ManagedTable, and there is no ordering an app can win: the
 * framework boots before the apps extending it. Deferring moves the snapshot to first use.
 *
 * @package Corex\Tests\Unit\Data
 */

declare(strict_types=1);

use Corex\Config\Data\DataRegistry;
use Corex\Config\Data\DataSource;

function deferredSource(string $key): DataSource
{
    return new class ($key) implements DataSource {
        public function __construct(private readonly string $sourceKey)
        {
        }

        public function key(): string
        {
            return $this->sourceKey;
        }

        public function label(): string
        {
            return ucfirst($this->sourceKey);
        }

        public function columns(): array
        {
            return [['id' => 'name', 'label' => 'Name']];
        }

        public function rows(int $page, int $perPage): array
        {
            return [];
        }

        public function total(): int
        {
            return 0;
        }

        public function delete(int $id): bool
        {
            return false;
        }
    };
}

it('includes sources registered after defer() but before the first read', function () {
    $registry = new DataRegistry();
    $late = [];

    $registry->defer(static function () use (&$late): array {
        return array_map(deferredSource(...), $late);
    });

    // The app registers its table only now — after the registry object already exists.
    $late[] = 'applications';

    expect(array_map(static fn (DataSource $s): string => $s->key(), $registry->all()))
        ->toBe(['applications']);
});

it('resolves the deferred provider exactly once, however many reads happen', function () {
    $registry = new DataRegistry();
    $calls = 0;

    $registry->defer(static function () use (&$calls): array {
        $calls++;

        return [deferredSource('applications')];
    });

    $registry->all();
    $registry->all();
    $registry->find('applications');

    expect($calls)->toBe(1);
});

it('finds a deferred source by key without a prior all() call', function () {
    $registry = new DataRegistry();
    $registry->defer(static fn (): array => [deferredSource('applications')]);

    expect($registry->find('applications'))->not->toBeNull()
        ->and($registry->find('nope'))->toBeNull();
});

it('keeps eagerly registered sources alongside deferred ones', function () {
    $registry = new DataRegistry();
    $registry->register(deferredSource('submissions'));
    $registry->defer(static fn (): array => [deferredSource('applications')]);

    expect(array_map(static fn (DataSource $s): string => $s->key(), $registry->all()))
        ->toBe(['submissions', 'applications']);
});
