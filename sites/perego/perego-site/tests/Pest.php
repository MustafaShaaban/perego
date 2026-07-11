<?php

/**
 * Pest configuration for perego-site. Headless unit tests only (no WordPress) — see
 * tests/bootstrap.php for the PeregoSite\ autoloading.
 *
 * @package PeregoSite
 */

declare(strict_types=1);

uses(\PeregoSite\Tests\TestCase::class)->in('.');
