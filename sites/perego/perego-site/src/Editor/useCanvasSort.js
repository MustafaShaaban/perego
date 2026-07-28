/**
 * Drag-sorting for real cards on the editor canvas (spec 023; owner 2026-07-28).
 *
 * WHY POINTER EVENTS, NOT HTML5 DRAG. `useBlockProps` puts `draggable="true"` on the block wrapper and
 * the editor installs document-level `dragover` handlers to draw its own block insertion indicator,
 * while the canvas is an iframe and the drag chip renders in the outer document. A nested HTML5 drag
 * source inside a selected block fights all of that. Pointer events sidestep it, cover touch and pen
 * in one path, and give the `getBoundingClientRect()` hit-testing the explicitly-placed mosaic needs
 * anyway (see `sorting.js`). No dependency is added: `perego-site` has no runtime deps and this keeps
 * it that way.
 *
 * ACCESSIBILITY IS NOT AN ADD-ON HERE. WCAG 2.2 **2.5.7 Dragging Movements** requires a single-pointer
 * alternative that is not a drag — a keyboard equivalent alone does not satisfy it. So every sortable
 * item also carries Move earlier / Move later buttons (see {@link SortableItem}), and this hook
 * exposes the same move through `moveBy` for them and for the keyboard. `RepeaterControls`' docblock
 * asked for exactly this: pointer sorting alongside the button controls, never replacing them.
 *
 * ONE COMMIT PER MOVE. `onReorder` fires on drop, never on `pointermove`, so a drag is one entry in
 * the editor's undo stack rather than a hundred.
 */
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
import { speak } from '@wordpress/a11y';
import { __, sprintf } from '@wordpress/i18n';

import { moveItem } from './collection';
import { keyboardTarget, passedThreshold, rectsFor, targetIndex } from './sorting';

/** Target-change announcements are throttled; core throttles its own `speak` calls the same way. */
const ANNOUNCE_MS = 300;

export function useCanvasSort( {
	length,
	isEnabled = true,
	onReorder,
	describeItem = () => '',
	describeLanding = () => '',
} ) {
	const [ dragging, setDragging ] = useState( null );
	const itemRefs = useRef( [] );
	const state = useRef( { pointerId: null, startX: 0, startY: 0, from: -1, to: -1, active: false } );
	const lastAnnounced = useRef( 0 );

	const setItemRef = useCallback( ( index ) => ( element ) => {
		itemRefs.current[ index ] = element;
	}, [] );

	const announce = useCallback( ( message, assertive = false ) => {
		if ( message ) {
			speak( message, assertive ? 'assertive' : 'polite' );
		}
	}, [] );

	/** The one place a move is committed, whatever triggered it. */
	const commit = useCallback( ( from, to ) => {
		if ( from < 0 || to < 0 || from === to ) {
			return;
		}

		onReorder( ( order ) => moveItem( order, from, to ) );
		announce(
			sprintf(
				/* translators: 1: project name. 2: new position. 3: total. 4: what the new position does to the tile. */
				__( '%1$s moved to position %2$d of %3$d. %4$s', 'perego-site' ),
				describeItem( from ),
				to + 1,
				length,
				describeLanding( to )
			),
			true
		);
	}, [ announce, describeItem, describeLanding, length, onReorder ] );

	const moveBy = useCallback( ( index, action ) => {
		const to = keyboardTarget( index, action, length );
		if ( to === -1 ) {
			return;
		}

		commit( index, to );
	}, [ commit, length ] );

	const onPointerDown = useCallback( ( index ) => ( event ) => {
		// Only a selected block is a sorting surface. An unselected one behaves like a block, so a
		// first click still selects it the way every other block in the editor does.
		if ( ! isEnabled || event.button !== 0 ) {
			return;
		}

		// preventDefault stops a native drag ever initiating; stopPropagation keeps the editor's own
		// block drag from claiming the gesture.
		event.preventDefault();
		event.stopPropagation();

		state.current = {
			pointerId: event.pointerId,
			startX: event.clientX,
			startY: event.clientY,
			from: index,
			to: index,
			active: false,
		};

		event.currentTarget.setPointerCapture?.( event.pointerId );
	}, [ isEnabled ] );

	const onPointerMove = useCallback( ( event ) => {
		const current = state.current;
		if ( current.from === -1 || current.pointerId !== event.pointerId ) {
			return;
		}

		if ( ! current.active ) {
			if ( ! passedThreshold( current.startX, current.startY, event.clientX, event.clientY ) ) {
				return;
			}

			current.active = true;
			setDragging( { from: current.from, to: current.from, x: event.clientX, y: event.clientY } );
			announce( sprintf(
				/* translators: 1: project name. 2: current position. 3: total. */
				__( 'Grabbed %1$s. Position %2$d of %3$d. Use Move earlier and Move later, or press Escape to cancel.', 'perego-site' ),
				describeItem( current.from ),
				current.from + 1,
				length
			) );
		}

		const next = targetIndex( rectsFor( itemRefs.current.slice( 0, length ) ), event.clientX, event.clientY );
		if ( next !== -1 && next !== current.to ) {
			current.to = next;

			const now = Date.now();
			if ( now - lastAnnounced.current > ANNOUNCE_MS ) {
				lastAnnounced.current = now;
				announce( sprintf(
					/* translators: 1: target position. 2: total. */
					__( 'Moving to position %1$d of %2$d.', 'perego-site' ),
					next + 1,
					length
				) );
			}
		}

		setDragging( { from: current.from, to: current.to, x: event.clientX, y: event.clientY } );
	}, [ announce, describeItem, length ] );

	const endDrag = useCallback( ( event, cancelled = false ) => {
		const current = state.current;
		if ( current.from === -1 ) {
			return;
		}

		event?.currentTarget?.releasePointerCapture?.( current.pointerId );

		if ( current.active && ! cancelled ) {
			commit( current.from, current.to );
		} else if ( cancelled ) {
			announce( __( 'Move cancelled.', 'perego-site' ) );
		}

		state.current = { pointerId: null, startX: 0, startY: 0, from: -1, to: -1, active: false };
		setDragging( null );
	}, [ announce, commit ] );

	const onPointerUp = useCallback( ( event ) => endDrag( event, false ), [ endDrag ] );
	const onPointerCancel = useCallback( ( event ) => endDrag( event, true ), [ endDrag ] );

	// Escape abandons an in-flight drag rather than committing it. Bound to the item's own document
	// because the canvas is an iframe and `document` here is the outer one.
	useEffect( () => {
		if ( ! dragging ) {
			return undefined;
		}

		const owner = itemRefs.current[ dragging.from ]?.ownerDocument;
		if ( ! owner ) {
			return undefined;
		}

		const onKeyDown = ( event ) => {
			if ( event.key === 'Escape' ) {
				event.preventDefault();
				endDrag( null, true );
			}
		};

		owner.addEventListener( 'keydown', onKeyDown );
		return () => owner.removeEventListener( 'keydown', onKeyDown );
	}, [ dragging, endDrag ] );

	/**
	 * Ctrl/Cmd + arrow moves by one, Home/End to either end.
	 *
	 * Modifier-qualified because an unmodified arrow inside the editor canvas is caret movement and
	 * block navigation, and stealing it would break both.
	 */
	const onKeyDown = useCallback( ( index ) => ( event ) => {
		if ( ! isEnabled ) {
			return;
		}

		const withModifier = event.ctrlKey || event.metaKey;
		const action = ( () => {
			if ( withModifier && ( event.key === 'ArrowLeft' || event.key === 'ArrowUp' ) ) return 'earlier';
			if ( withModifier && ( event.key === 'ArrowRight' || event.key === 'ArrowDown' ) ) return 'later';
			if ( event.key === 'Home' ) return 'first';
			if ( event.key === 'End' ) return 'last';
			return null;
		} )();

		if ( ! action ) {
			return;
		}

		event.preventDefault();
		event.stopPropagation();
		moveBy( index, action );
	}, [ isEnabled, moveBy ] );

	return {
		dragging,
		setItemRef,
		moveBy,
		itemProps: ( index ) => ( {
			ref: setItemRef( index ),
			draggable: false,
			onDragStart: ( event ) => {
				event.preventDefault();
				event.stopPropagation();
			},
			onPointerDown: onPointerDown( index ),
			onPointerMove,
			onPointerUp,
			onPointerCancel,
			onKeyDown: onKeyDown( index ),
			'data-sort-index': index,
			'data-sort-state': dragging?.from === index
				? 'grabbed'
				: ( dragging?.to === index ? 'target' : undefined ),
		} ),
	};
}
