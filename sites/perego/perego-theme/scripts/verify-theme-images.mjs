#!/usr/bin/env node
/**
 * Verify the deterministic theme-image contract after `optimize-images.mjs`.
 */

import { existsSync, readdirSync, statSync } from 'node:fs';
import { basename, extname, join } from 'node:path';
import sharp from 'sharp';

const SRC = 'assets/src/images';
const OUT = 'assets/images';
const RASTER = new Set( [ '.jpg', '.jpeg', '.png' ] );
const TARGET_DIMENSIONS = new Map( [
	[ 'hero-bg.png', [ 1920, 1080 ] ],
	[ 'logo-full.png', [ 552, 170 ] ],
	[ 'wave-ribbon.png', [ 2560, 1376 ] ],
	[ 'wavy-corners.png', [ 2560, 1440 ] ],
] );
const OUTPUT_STEMS = new Map( [
	[ 'hero-bg.png', 'hero-bg-v2' ],
] );
const LIGHTHOUSE_BASELINE_BYTES = 3_294_944;
const MAX_TARGET_WEBP_BYTES = Math.floor( LIGHTHOUSE_BASELINE_BYTES * 0.4 );
const AUDITED_IMAGES = new Set( [ 'hero-bg.png', 'wave-ribbon.png', 'wavy-corners.png' ] );

function fail( message ) {
	throw new Error( message );
}

function rasterSourceFiles() {
	return readdirSync( SRC )
		.filter( ( file ) => RASTER.has( extname( file ).toLowerCase() ) )
		.sort();
}

function imagePaths( file ) {
	const extension = extname( file ).toLowerCase();
	const stem = basename( file, extension );
	const outputStem = OUTPUT_STEMS.get( file ) ?? stem;

	return {
		source: join( SRC, file ),
		fallback: join( OUT, `${ outputStem }${ extension }` ),
		webp: join( OUT, `${ outputStem }.webp` ),
	};
}

function requireOutputs( file, paths ) {
	if ( ! existsSync( paths.fallback ) || ! existsSync( paths.webp ) ) {
		fail( `${ file } is missing its fallback or WebP build output.` );
	}
}

function verifyDimensions( file, fallbackMeta, webpMeta ) {
	if ( fallbackMeta.width !== webpMeta.width || fallbackMeta.height !== webpMeta.height ) {
		fail( `${ file } fallback and WebP dimensions differ.` );
	}

	const expected = TARGET_DIMENSIONS.get( file );
	if ( expected && ( fallbackMeta.width !== expected[ 0 ] || fallbackMeta.height !== expected[ 1 ] ) ) {
		fail(
			`${ file } is ${ fallbackMeta.width }x${ fallbackMeta.height }; ` +
			`expected ${ expected[ 0 ] }x${ expected[ 1 ] }.`
		);
	}
}

async function verifyFile( file ) {
	const paths = imagePaths( file );
	requireOutputs( file, paths );
	const bytes = {
		source: statSync( paths.source ).size,
		fallback: statSync( paths.fallback ).size,
		webp: statSync( paths.webp ).size,
	};
	const fallbackMeta = await sharp( paths.fallback ).metadata();
	const webpMeta = await sharp( paths.webp ).metadata();

	if ( bytes.fallback > bytes.source ) {
		fail( `${ file } fallback grew from ${ bytes.source } to ${ bytes.fallback } bytes.` );
	}
	verifyDimensions( file, fallbackMeta, webpMeta );
	console.log(
		`${ file }: source=${ bytes.source } fallback=${ bytes.fallback } webp=${ bytes.webp } ` +
		`${ fallbackMeta.width }x${ fallbackMeta.height }`
	);

	return bytes.webp;
}

function verifyTargetBudget( targetWebpBytes ) {
	if ( targetWebpBytes > MAX_TARGET_WEBP_BYTES ) {
		fail(
			`Target WebP total is ${ targetWebpBytes } bytes; expected no more than ` +
			`${ MAX_TARGET_WEBP_BYTES } bytes (60% below the Lighthouse baseline).`
		);
	}
}

async function main() {
	const sourceFiles = rasterSourceFiles();
	let targetWebpBytes = 0;

	for ( const file of sourceFiles ) {
		const webpBytes = await verifyFile( file );
		targetWebpBytes += AUDITED_IMAGES.has( file ) ? webpBytes : 0;
	}

	verifyTargetBudget( targetWebpBytes );
	console.log( `Verified ${ sourceFiles.length } image contract(s); audited WebP total=${ targetWebpBytes } bytes.` );
}

main().catch( ( error ) => {
	console.error( error.message );
	process.exitCode = 1;
} );
