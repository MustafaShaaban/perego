import { Button, Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import FieldValue from '../components/FieldValue.js';

/**
 * The two record shapes a source may return, and why both are handled here.
 *
 * A managed table (`TableDataSource::record()`) returns a flat column => value map, which is what
 * this modal was written against. The submissions source returns `{id, date, form, fields: [{label,
 * value}]}` — a labelled list, because a submission's payload is not the source's column set. The
 * modal indexed `record[field.key]` unconditionally, so for submissions every row read "—".
 */
function entriesFor( explorer, record ) {
	if ( Array.isArray( record?.fields ) ) {
		return record.fields.map( ( field, index ) => ( {
			key: `${ field.label }-${ index }`,
			label: field.label,
			value: field.value,
		} ) );
	}

	return ( explorer.source?.fields || [] ).map( ( field ) => ( {
		key: field.key,
		label: field.label,
		value: record[ field.key ],
	} ) );
}

export default function RecordDetail( { explorer, record, close, edit } ) {
	return <Modal title={ __( 'Record detail', 'corex' ) } onRequestClose={ close }>
		<dl className="corex-data__fields">{ entriesFor( explorer, record ).map( ( entry ) => <div key={ entry.key } className="corex-data__field">
			<dt>{ entry.label }</dt><dd><FieldValue value={ entry.value } /></dd>
		</div> ) }</dl>
		<div className="corex-data__dialog-actions"><Button variant="tertiary" onClick={ close }>{ __( 'Close', 'corex' ) }</Button>
			{ explorer.can( 'update' ) && <Button variant="secondary" onClick={ edit }>{ __( 'Edit', 'corex' ) }</Button> }
			{ explorer.can( 'delete' ) && <Button isDestructive variant="secondary" onClick={ () => {
				explorer.previewMutation( 'delete', [ record.id ] ); close();
			} }>{ __( 'Delete', 'corex' ) }</Button> }
		</div>
	</Modal>;
}
