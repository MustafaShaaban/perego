import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { Button, ResponsiveWrapper } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/** A labelled media-library field that displays a real thumbnail, never an attachment ID. */
export function MediaField( { value, media, label, onSelect, onRemove, allowedTypes = [ 'image' ] } ) {
	const hasMedia = media && media.url;
	return (
		<div className="perego-editor-media-field">
			<p className="perego-editor-media-field__label">{ label }</p>
			{ hasMedia && (
				<ResponsiveWrapper naturalWidth={ media.width || 1 } naturalHeight={ media.height || 1 }>
					<img src={ media.url } alt={ media.alt || '' } />
				</ResponsiveWrapper>
			) }
			<MediaUploadCheck>
				<MediaUpload allowedTypes={ allowedTypes } value={ value } onSelect={ onSelect }
					render={ ( { open } ) => <Button variant="secondary" onClick={ open }>{ hasMedia ? __( 'Replace media', 'perego-site' ) : __( 'Select media', 'perego-site' ) }</Button> } />
			</MediaUploadCheck>
			{ hasMedia && <Button variant="tertiary" isDestructive onClick={ onRemove }>{ __( 'Remove media', 'perego-site' ) }</Button> }
		</div>
	);
}
