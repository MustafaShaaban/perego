<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Services;

defined('ABSPATH') || exit;

use PeregoSite\Models\Example;
use PeregoSite\Repositories\ExampleRepository;

/**
 * Example business logic — orchestrates and applies rules; it never queries the database
 * directly (the repository does). Part of the --starter example; delete per REMOVE-EXAMPLE.md.
 */
final class ExampleService
{
    public function __construct(private readonly ExampleRepository $repository)
    {
    }

    /**
     * @return list<Example>
     */
    public function all(): array
    {
        return $this->repository->all();
    }
}
