/**
 * Step 2 of the per-shape crop seed — cuts the four ProjectThumbnails::SHAPES from each project's
 * featured image (owner, 2026-07-28).
 *
 *   1. SEED_CROPS_MODE=manifest wp eval 'require ".../seed-project-crops.php";' --path=wp
 *   2. node sites/perego/perego-site/scripts/generate-project-crops.mjs --manifest <printed path>
 *   3. SEED_CROPS_MODE=import   wp eval 'require ".../seed-project-crops.php";' --path=wp
 *
 * PHP owns the shape table and the eligibility rules; this script owns only the pixels. Every target
 * size and the output directory arrive in the manifest, so the two never disagree about either.
 *
 * `attention` rather than a centre crop, because a centre crop is exactly what the feature exists to
 * escape: the tall 0.55:1 slot is where a centred landscape still loses its subject, and that is the
 * tile these seeds are meant to make convincing.
 */
import { createRequire } from 'node:module';
import { mkdir, readFile } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const HERE = dirname( fileURLToPath( import.meta.url ) );

/*
 * `sharp` is declared and installed by perego-theme (its image pipeline uses it) and resolves from
 * nowhere above this directory, so it is loaded from there by path rather than duplicated into a
 * second package.json. A dev-only seed script may reach sideways for a build tool; nothing at runtime
 * does.
 */
const THEME_MODULES = join( HERE, '..', '..', 'perego-theme', 'node_modules' );
let sharp;
try {
	sharp = createRequire( join( THEME_MODULES, 'index.js' ) )( 'sharp' );
} catch {
	console.error(
		`Could not load sharp from ${ THEME_MODULES }.\n` +
		'Run `npm --prefix sites/perego/perego-theme install` first.'
	);
	process.exit( 1 );
}

const flag = ( name ) => {
	const at = process.argv.indexOf( `--${ name }` );
	return at > -1 ? process.argv[ at + 1 ] : undefined;
};

const manifestPath = flag( 'manifest' );
if ( ! manifestPath ) {
	console.error( 'Usage: node generate-project-crops.mjs --manifest <path to manifest.json>' );
	process.exit( 1 );
}

const manifest = JSON.parse( await readFile( manifestPath, 'utf8' ) );
const outDir = manifest.dir || dirname( manifestPath );
await mkdir( outDir, { recursive: true } );

let written = 0;
let failed = 0;

for ( const project of manifest.projects ) {
	for ( const [ shape, size ] of Object.entries( project.shapes ) ) {
		const target = join( outDir, `${ project.id }-${ shape }.webp` );

		try {
			await sharp( project.source )
				.resize( size.width, size.height, { fit: 'cover', position: sharp.strategy.attention } )
				.webp( { quality: 82 } )
				.toFile( target );
			written++;
		} catch ( error ) {
			console.error( `  ${ project.id } ${ shape }: ${ error.message }` );
			failed++;
		}
	}

	console.log( `${ project.title } (#${ project.id }) — ${ Object.keys( project.shapes ).join( ', ' ) }` );
}

console.log( `\n${ written } crop(s) written to ${ outDir }${ failed ? `, ${ failed } failed` : '' }` );
process.exit( failed ? 1 : 0 );
