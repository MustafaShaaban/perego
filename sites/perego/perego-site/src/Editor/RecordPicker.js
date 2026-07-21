/**
 * RecordPicker — choose published records by their own title, never a raw ID (spec 021; DECISIONS 2026-07-21).
 *
 * Generalized out of the header's Services-dropdown picker for reuse by every dynamic/query surface (header
 * Services menu, Service portfolio, clients, …). Two modes:
 *  - `manual`: an ordered chosen list (move up/down, remove) plus an add list of the remaining records.
 *  - `automatic`: every record with a per-item show/hide (an exclusion list).
 * The caller owns the record source and the `order`/`excluded` arrays; this stays presentational and
 * schema-agnostic. Reordering uses {@link moveItem} from `collection.js`.
 */
import { Button, CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { chevronDown, chevronUp, closeSmall, plus } from '@wordpress/icons';

import { moveItem, partitionRecords } from './collection';

export function RecordPicker( {
	records,
	order = [],
	excluded = [],
	mode = 'manual',
	onChangeOrder,
	onChangeExcluded,
	getId = ( record ) => record.id,
	getLabel,
	emptyLabel,
	selectedHeading,
	addHeading,
	noneSelectedLabel,
} ) {
	const { selected, available } = partitionRecords( records, order, getId );

	const add = ( id ) => onChangeOrder( [ ...order, id ] );
	const remove = ( id ) => onChangeOrder( order.filter( ( selectedId ) => selectedId !== id ) );
	const move = ( index, delta ) => onChangeOrder( moveItem( order, index, index + delta ) );
	const setShown = ( id, isShown ) => onChangeExcluded(
		isShown ? excluded.filter( ( excludedId ) => excludedId !== id ) : [ ...excluded, id ]
	);

	if ( records.length === 0 ) {
		return <p className="perego-editor-help">{ emptyLabel || __( 'No published records yet.', 'perego-site' ) }</p>;
	}

	if ( mode === 'automatic' ) {
		return (
			<div className="perego-editor-picker">
				{ records.map( ( record ) => (
					<CheckboxControl __nextHasNoMarginBottom key={ getId( record ) }
						label={ getLabel( record ) }
						checked={ ! excluded.includes( getId( record ) ) }
						onChange={ ( isShown ) => setShown( getId( record ), isShown ) } />
				) ) }
			</div>
		);
	}

	return (
		<div className="perego-editor-picker">
			<p className="perego-editor-picker__heading">{ selectedHeading || __( 'Shown', 'perego-site' ) }</p>
			{ selected.length === 0 && (
				<p className="perego-editor-help">{ noneSelectedLabel || __( 'None selected yet.', 'perego-site' ) }</p>
			) }
			{ selected.map( ( record, index ) => (
				<div className="perego-editor-picker__row" key={ getId( record ) }>
					<span className="perego-editor-picker__title">{ getLabel( record ) }</span>
					<span className="perego-editor-picker__actions">
						<Button size="small" icon={ chevronUp } label={ __( 'Move up', 'perego-site' ) }
							disabled={ index === 0 } onClick={ () => move( index, -1 ) } />
						<Button size="small" icon={ chevronDown } label={ __( 'Move down', 'perego-site' ) }
							disabled={ index === selected.length - 1 } onClick={ () => move( index, 1 ) } />
						<Button size="small" icon={ closeSmall } isDestructive label={ __( 'Remove', 'perego-site' ) }
							onClick={ () => remove( getId( record ) ) } />
					</span>
				</div>
			) ) }

			{ available.length > 0 && (
				<>
					<p className="perego-editor-picker__heading">{ addHeading || __( 'Add', 'perego-site' ) }</p>
					{ available.map( ( record ) => (
						<div className="perego-editor-picker__row" key={ getId( record ) }>
							<span className="perego-editor-picker__title">{ getLabel( record ) }</span>
							<Button size="small" variant="secondary" icon={ plus }
								onClick={ () => add( getId( record ) ) }>{ __( 'Add', 'perego-site' ) }</Button>
						</div>
					) ) }
				</>
			) }
		</div>
	);
}
