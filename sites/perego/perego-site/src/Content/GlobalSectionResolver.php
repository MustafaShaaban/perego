<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Content;

defined('ABSPATH') || exit;

/**
 * Pure resolution of which `perego_global_section` record renders for a given role + current
 * language (spec Phase 5). The one hard rule this enforces is **no silent language mixing**: a role
 * is resolved only against records in the *exact* requested locale — a missing translation returns
 * null (the caller surfaces an admin-visible notice) rather than falling back to the other language.
 *
 * When a role+locale is duplicated (a singleton role should have exactly one record per language,
 * but nothing at the DB level forbids two), it picks the lowest post id so rendering is stable and
 * deterministic regardless of query order.
 */
final class GlobalSectionResolver
{
    /**
     * @param list<array{id:int,role:string,locale:string}> $candidates
     */
    public function resolve(string $role, string $locale, array $candidates): ?int
    {
        $ids = [];
        foreach ($candidates as $candidate) {
            if ($candidate['role'] === $role && $candidate['locale'] === $locale) {
                $ids[] = $candidate['id'];
            }
        }

        if ($ids === []) {
            return null;
        }

        return min($ids);
    }
}
