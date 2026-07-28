import { Button, Flex, FlexItem } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

import { duplicateItem, moveItem, removeItem } from './collection';

/**
 * Accessible repeater actions shared by visual Perego blocks. Native keyboard controls are offered
 * alongside future pointer sorting so ordering is never mouse-only.
 */
export function RepeaterControls( { items, index, onChange, createCopy, itemLabel, canDuplicate = true, canRemove = true } ) {
	const label = itemLabel || __( 'item', 'perego-site' );
	const move = ( offset ) => onChange( moveItem( items, index, index + offset ) );

	return (
		<Flex className="perego-editor-repeater__actions" gap={ 1 } justify="flex-start">
			<FlexItem>
				<Button size="small" variant="tertiary" onClick={ () => move( -1 ) } disabled={ index === 0 }>
					{ __( 'Move up', 'perego-site' ) }
				</Button>
			</FlexItem>
			<FlexItem>
				<Button size="small" variant="tertiary" onClick={ () => move( 1 ) } disabled={ index === items.length - 1 }>
					{ __( 'Move down', 'perego-site' ) }
				</Button>
			</FlexItem>
			<FlexItem>
				<Button size="small" variant="tertiary" onClick={ () => onChange( duplicateItem( items, index, createCopy ) ) } disabled={ ! canDuplicate }>
					{ __( 'Duplicate', 'perego-site' ) }
				</Button>
			</FlexItem>
			<FlexItem>
				<Button size="small" isDestructive variant="tertiary" onClick={ () => onChange( removeItem( items, index ) ) } disabled={ ! canRemove }>
					{ sprintf( __( 'Remove %s', 'perego-site' ), label ) }
				</Button>
			</FlexItem>
		</Flex>
	);
}
