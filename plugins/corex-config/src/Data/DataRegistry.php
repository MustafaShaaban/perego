<?php

/**
 * @package Corex\Config
 */

declare(strict_types=1);

namespace Corex\Config\Data;

defined('ABSPATH') || exit;

use Corex\Access\CorexAbility;
use Corex\Data\DataField;
use Corex\Data\DataSourceCapabilities;

/**
 * The registered data sources behind the Corex → Data screen, keyed by source key. The
 * framework registers the submissions source; add-ons register their custom-table sources
 * (spec 030).
 */
final class DataRegistry
{
    /** @var array<string,DataSource> */
    private array $sources = [];

    /** @var list<callable():list<DataSource>> */
    private array $deferred = [];

    public function register(DataSource $source): void
    {
        $this->sources[$source->key()] = $source;
    }

    /**
     * Contribute sources that are only knowable later, resolved on first read.
     *
     * The managed-table sources used to be looped in while this registry was being constructed. The
     * registry is a singleton built during boot — the Overview renderer resolves it — so the table list
     * was sealed at that instant, and any plugin that registered a ManagedTable afterwards (or during
     * its own boot, which may run after corex-config's) never appeared on the Data screen. There is no
     * ordering an app can win: the framework boots before the apps that extend it.
     *
     * Deferring to first read moves the snapshot from build time to use time — an admin request, long
     * after every plugin has registered. Resolution still happens exactly once.
     *
     * @param callable():list<DataSource> $provider
     */
    public function defer(callable $provider): void
    {
        $this->deferred[] = $provider;
    }

    /**
     * @return list<DataSource>
     */
    public function all(): array
    {
        $this->resolveDeferred();

        return array_values($this->sources);
    }

    public function find(string $key): ?DataSource
    {
        $this->resolveDeferred();

        return $this->sources[$key] ?? null;
    }

    /** Drain the deferred providers once; registering never re-enters because the queue clears first. */
    private function resolveDeferred(): void
    {
        if ($this->deferred === []) {
            return;
        }

        $providers = $this->deferred;
        $this->deferred = [];

        foreach ($providers as $provider) {
            foreach ($provider() as $source) {
                $this->register($source);
            }
        }
    }

    public function capabilities(string $key): ?DataSourceCapabilities
    {
        $source = $this->find($key);

        if ($source === null) {
            return null;
        }

        if ($source instanceof CapabilityAwareDataSource) {
            return $source->capabilities();
        }

        return new DataSourceCapabilities(
            sourceKey: $source->key(),
            read: true,
            query: $source instanceof QueryableDataSource,
            schema: $source instanceof SchemaAwareDataSource,
            detail: $source instanceof QueryableDataSource,
            create: false,
            update: false,
            delete: false,
            bulkUpdate: false,
            bulkDelete: false,
            importDryRun: false,
            importCommit: false,
            exportCsv: false,
            exportXlsx: false,
            migrations: false,
            rollback: false,
            maxPageSize: 100,
            permissionMap: ['read' => CorexAbility::MANAGE_DATA],
        );
    }

    /** @return list<DataField> */
    public function fields(string $key): array
    {
        $source = $this->find($key);

        if ($source === null) {
            return [];
        }

        if ($source instanceof FieldAwareDataSource) {
            return $source->fields();
        }

        return array_map(
            static fn (array $column): DataField => new DataField(
                key: (string) $column['id'],
                label: (string) $column['label'],
                type: DataField::TYPE_TEXT,
                required: false,
                nullable: true,
                readOnly: true,
                filterOperators: ['equals', 'contains'],
                sortable: true,
                personalDataClass: DataField::PERSONAL_NONE,
                validation: [],
                importAliases: [],
            ),
            $source->columns(),
        );
    }

    /** @return list<array{key:string,label:string,capabilities:array<string,mixed>,fields:list<array<string,mixed>>}> */
    public function describe(): array
    {
        return array_map(
            fn (DataSource $source): array => [
                'key'          => $source->key(),
                'label'        => $source->label(),
                'capabilities' => $this->capabilities($source->key())?->toArray() ?? [],
                'fields'       => array_map(
                    static fn (DataField $field): array => $field->toArray(),
                    $this->fields($source->key()),
                ),
            ],
            $this->all(),
        );
    }
}
