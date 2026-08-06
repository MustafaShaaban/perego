#!/usr/bin/env node
/**
 * Perego theme image build.
 *
 * Canonical raster sources live in `assets/src/images`. The build writes a compressed fallback in
 * the original format and a same-stem WebP sibling to `assets/images`, matching the public
 * `Corex\Assets\Image::picture()` contract. Media Library uploads remain CoreX Media's concern.
 */

import { existsSync, mkdirSync, readdirSync } from 'node:fs';
import { readFile, writeFile } from 'node:fs/promises';
import { basename, extname, join } from 'node:path';

const SRC = 'assets/src/images';
const OUT = 'assets/images';
const RASTER = new Set( [ '.jpg', '.jpeg', '.png' ] );

const PNG_OPTIONS = {
	adaptiveFiltering: true,
	compressionLevel: 9,
	effort: 10,
	palette: true,
	quality: 90,
};

const JPEG_OPTIONS = {
	mozjpeg: true,
	quality: 82,
};

const DEFAULT_WEBP = {
	alphaQuality: 100,
	effort: 6,
	quality: 72,
	smartSubsample: true,
};

// Smooth hero gradients need a true-colour fallback and a near-lossless visual encode. Palette PNG
// reduction plus the default WebP quality creates visible banding at full-screen size. The waves
// are transparent line illustrations, where lossless WebP avoids dark artifacts at stroke crossings.
const PROFILES = new Map( [
	[
		'hero-bg.png',
		{
			outputStem: 'hero-bg-v2',
			png: { ...PNG_OPTIONS, palette: false },
			webp: { ...DEFAULT_WEBP, quality: 95 },
		},
	],
	[ 'logo-full.png', { webp: { effort: 6, lossless: true } } ],
	[ 'wave-ribbon.png', { width: 2560, webp: { effort: 6, lossless: true } } ],
	[ 'wavy-corners.png', { width: 2560, webp: { effort: 6, lossless: true } } ],
] );

function pipeline( sharp, input, profile ) {
	const image = sharp( input );

	return profile.width
		? image.resize( { width: profile.width, withoutEnlargement: true } )
		: image;
}

async function fallbackBuffer( sharp, input, extension, profile ) {
	const image = pipeline( sharp, input, profile );

	if ( extension === '.png' ) {
		return image.png( profile.png ?? PNG_OPTIONS ).toBuffer();
	}

	return image.jpeg( JPEG_OPTIONS ).toBuffer();
}

function rasterSourceFiles() {
	return readdirSync( SRC )
		.filter( ( file ) => RASTER.has( extname( file ).toLowerCase() ) )
		.sort();
}

function preferredFallback( source, compressed, profile ) {
	if ( ! profile.width && compressed.byteLength > source.byteLength ) {
		return source;
	}

	return compressed;
}

async function optimizeFile( sharp, file ) {
	const extension = extname( file ).toLowerCase();
	const stem = basename( file, extension );
	const input = join( SRC, file );
	const profile = PROFILES.get( file ) ?? {};
	const outputStem = profile.outputStem ?? stem;
	const source = await readFile( input );
	const compressed = await fallbackBuffer( sharp, input, extension, profile );
	const fallback = preferredFallback( source, compressed, profile );
	const webp = await sharp( fallback ).webp( profile.webp ?? DEFAULT_WEBP ).toBuffer();

	await writeFile( join( OUT, `${ outputStem }${ extension }` ), fallback );
	await writeFile( join( OUT, `${ outputStem }.webp` ), webp );
	console.log( `optimized ${ file } -> ${ outputStem }${ extension } + ${ outputStem }.webp` );
}

async function main() {
	if ( ! existsSync( SRC ) ) {
		console.log( `No ${ SRC }/ directory - nothing to optimize.` );
		return;
	}

	const { default: sharp } = await import( 'sharp' );
	const sourceFiles = rasterSourceFiles();
	mkdirSync( OUT, { recursive: true } );

	for ( const file of sourceFiles ) {
		await optimizeFile( sharp, file );
	}

	console.log( `Done: ${ sourceFiles.length } image(s) -> ${ OUT }/.` );
}

main().catch( ( error ) => {
	console.error( error );
	process.exitCode = 1;
} );
