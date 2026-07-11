<?php

/**
 * Base TestCase for perego-site headless unit tests. Sets up Brain Monkey so WordPress/Polylang
 * functions can be stubbed without a booted WordPress — same approach as the framework's own
 * Corex\Tests\Unit\TestCase.
 *
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Tests;

use Brain\Monkey;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }
}
