/**
 * LabeledRepeater — a card-styled editable list for the Perego block Inspector (spec 021).
 *
 * Renders each item in a bordered card with the shared move/duplicate/remove actions
 * ({@link RepeaterControls}) and a consistent add button + empty state, so every list in the Inspector
 * (nav links, contact channels, social links, legal links) looks and behaves the same. The caller supplies
 * `renderItem(item, index)` (usually a `LinkControl` or a couple of fields) and the item factories; this
 * owns only presentation and the add action, delegating reorder/remove/duplicate to `collection.js`.
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { RepeaterControls } from './RepeaterControls';

export function LabeledRepeater( {
	items,
	onChange,
	renderItem,
	createItem,
	createCopy,
	itemLabel,
	addLabel,
	emptyLabel,
} ) {
	return (
		<div className="perego-editor-repeater">
			{ items.length === 0 && emptyLabel && (
				<p className="perego-editor-help">{ emptyLabel }</p>
			) }
			{ items.map( ( item, index ) => (
				<div className="perego-editor-repeater__item" key={ index }>
					{ renderItem( item, index ) }
					<RepeaterControls items={ items } index={ index } onChange={ onChange }
						createCopy={ createCopy } itemLabel={ itemLabel } canDuplicate={ !! createCopy } />
				</div>
			) ) }
			{ createItem && (
				<Button className="perego-editor-repeater__add" variant="primary"
					onClick={ () => onChange( [ ...items, createItem() ] ) }>
					{ addLabel || __( 'Add item', 'perego-site' ) }
				</Button>
			) }
		</div>
	);
}
