<?php

/**
 * @package Corex\Media
 */

declare(strict_types=1);

namespace Corex\Media;

defined('ABSPATH') || exit;

use Throwable;

/**
 * Converts an image to WebP per a {@see ConversionPlan} (spec 048) using GD (preferred) or
 * Imagick. Fail-safe boundary: a corrupt/unreadable/oversized image returns false and leaves
 * the original untouched — never a fatal. The original is always preserved (the WebP is a
 * sibling file).
 */
final class WebpConverter
{
    /** Output quality (1-100). Defaults to the Media settings default; injected from settings at boot. */
    public function __construct(private readonly int $quality = MediaSettings::DEFAULT_QUALITY)
    {
    }

    public function convert(ConversionPlan $plan, string $mime): bool
    {
        if (! $plan->convert) {
            return false;
        }

        // The output path doubles as the source-derived base; the source is its non-.webp form.
        $source = (string) preg_replace('/\.webp$/i', '', $plan->outputPath);
        $source = $this->sourceFor($source, $mime);

        if ($source === '' || ! is_file($source)) {
            return false;
        }

        try {
            if (function_exists('imagewebp')) {
                return $this->convertWithGd($source, $mime, $plan->outputPath);
            }

            if (class_exists('Imagick')) {
                return $this->convertWithImagick($source, $plan->outputPath);
            }
        } catch (Throwable) {
            return false; // fail-safe — keep the original
        }

        return false;
    }

    private function sourceFor(string $base, string $mime): string
    {
        foreach ($this->extensions($mime) as $ext) {
            $candidate = $base . '.' . $ext;
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return '';
    }

    /**
     * @return list<string>
     */
    private function extensions(string $mime): array
    {
        return strtolower($mime) === 'image/png' ? ['png'] : ['jpg', 'jpeg'];
    }

    private function convertWithGd(string $source, string $mime, string $output): bool
    {
        $image = strtolower($mime) === 'image/png'
            ? @imagecreatefrompng($source)
            : @imagecreatefromjpeg($source);

        if ($image === false) {
            return false;
        }

        // `imagewebp()` throws a FATAL "Palette image not supported by webp" on an indexed-colour
        // image — it is not a warning and not a false return, so it takes the whole request down
        // mid-upload. Palette PNGs are exactly what an exported logo or flat-colour graphic usually
        // is, so this is a routine upload, not an edge case. Promoting to truecolour first is what
        // the function needs; alpha is preserved explicitly because the default for a new truecolour
        // canvas is to flatten it, and a logo without transparency is a white box on a dark page.
        if (! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }
        imagealphablending($image, false);
        imagesavealpha($image, true);

        $ok = imagewebp($image, $output, $this->quality);
        imagedestroy($image);

        return $ok;
    }

    private function convertWithImagick(string $source, string $output): bool
    {
        $image = new \Imagick($source);
        $image->setImageFormat('webp');
        $image->setImageCompressionQuality($this->quality);
        $ok = $image->writeImage($output);
        $image->clear();

        return $ok;
    }
}
