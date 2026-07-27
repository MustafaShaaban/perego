/**
 * Styled select — progressive enhancement over the native `<select>` (client request 2026-07-26:
 * "the selectbox is not styled when it opens").
 *
 * WHY THIS EXISTS AND WHAT IT MUST NOT BREAK.
 * The option popup of a native `<select>` is drawn by the OS, so CSS cannot reach it — `corex-forms`
 * says exactly that in its own stylesheet and deliberately kept the control native, because the
 * accessible `CorexSelect` lives in the admin bundle and shipping it would put React on a public form.
 * That reasoning still holds, so this does NOT replace the control:
 *
 *   - the real `<select>` stays in the DOM and remains what submits; nothing here touches its value
 *     except through the option's `selected` + a dispatched `change`, so corex-forms' own validation
 *     still sees a normal field;
 *   - it is hidden with the clip technique, NOT `display: none`, so it stays focusable. Native
 *     constraint validation is already suppressed on these forms — main.js sets `form.noValidate` so
 *     Corex's inline `.corex-form__error` UI shows instead of the browser bubble — but Corex's own
 *     validator may still focus the offending field, and focus has to land somewhere visible;
 *   - the `invalid` listener is kept regardless, so this stays correct if `noValidate` is ever lifted;
 *   - with JavaScript off, none of this runs and the native control renders exactly as before.
 */

const OPEN_CLASS = 'is-open';

/** Marks the native control so CSS can hide it while leaving it focusable. */
function hideNative( select ) {
	select.classList.add( 'perego-select__native' );
	select.setAttribute( 'tabindex', '-1' );
	select.setAttribute( 'aria-hidden', 'true' );
}

/** The visible text for the trigger: the selection, or the placeholder-ish first option. */
function triggerLabel( select ) {
	const chosen = Array.from( select.selectedOptions );

	if ( ! chosen.length ) {
		return select.options[ 0 ] ? select.options[ 0 ].textContent : '';
	}

	// A multi-select collapses to a count once it stops being readable as a list.
	if ( select.multiple && chosen.length > 2 ) {
		const template = select.dataset.peregoSelectedTemplate || '%d selected';
		return template.replace( '%d', String( chosen.length ) );
	}

	return chosen.map( ( option ) => option.textContent ).join( ', ' );
}

/**
 * Give the field's `<label for>` an id so the trigger can point at it. Without this the trigger would
 * need a duplicated `aria-label`, which drifts from the visible label the moment copy changes.
 */
function labelIdFor( select ) {
	const label = select.id
		? document.querySelector( `label[for="${ CSS.escape( select.id ) }"]` )
		: null;

	if ( ! label ) return '';

	if ( ! label.id ) label.id = `${ select.id }-label`;

	return label.id;
}

function buildOption( select, option, index, listId ) {
	const item = document.createElement( 'li' );

	item.id = `${ listId }-opt-${ index }`;
	item.className = 'perego-select__option';
	item.setAttribute( 'role', 'option' );
	item.setAttribute( 'aria-selected', option.selected ? 'true' : 'false' );
	if ( option.disabled ) item.setAttribute( 'aria-disabled', 'true' );
	item.textContent = option.textContent;
	item.dataset.value = option.value;

	return item;
}

function enhance( select ) {
	if ( select.dataset.peregoSelect === 'on' ) return;

	// Skip controls that are not rendered. The contact form's `services[]` select is one: it is a
	// sr-only data carrier for the visual `contact-service-chooser` block, hidden by
	// `[data-corex-field="services"] *`. Enhancing it would build a trigger nobody can reach and
	// duplicate a picker the page already has.
	if ( ! select.getClientRects().length ) return;

	select.dataset.peregoSelect = 'on';

	const listId = `${ select.id || `perego-select-${ Math.random().toString( 36 ).slice( 2 ) }` }-list`;

	const wrap = document.createElement( 'div' );
	wrap.className = 'perego-select';
	if ( select.multiple ) wrap.classList.add( 'perego-select--multiple' );

	const trigger = document.createElement( 'button' );
	trigger.type = 'button';
	trigger.className = 'perego-select__trigger';
	trigger.setAttribute( 'aria-haspopup', 'listbox' );
	trigger.setAttribute( 'aria-expanded', 'false' );
	trigger.setAttribute( 'aria-controls', listId );

	const labelId = labelIdFor( select );
	if ( labelId ) trigger.setAttribute( 'aria-labelledby', `${ labelId } ${ listId }-value` );

	const describedBy = select.getAttribute( 'aria-describedby' );
	if ( describedBy ) trigger.setAttribute( 'aria-describedby', describedBy );

	const value = document.createElement( 'span' );
	value.className = 'perego-select__value';
	value.id = `${ listId }-value`;
	trigger.appendChild( value );

	const list = document.createElement( 'ul' );
	list.className = 'perego-select__list';
	list.id = listId;
	list.setAttribute( 'role', 'listbox' );
	list.hidden = true;
	if ( select.multiple ) list.setAttribute( 'aria-multiselectable', 'true' );

	Array.from( select.options ).forEach( ( option, index ) => {
		list.appendChild( buildOption( select, option, index, listId ) );
	} );

	hideNative( select );
	select.parentNode.insertBefore( wrap, select );
	wrap.append( select, trigger, list );

	let active = Math.max( 0, select.selectedIndex );

	const items = () => Array.from( list.children );

	function syncFromNative() {
		value.textContent = triggerLabel( select );
		items().forEach( ( item, index ) => {
			item.setAttribute( 'aria-selected', select.options[ index ].selected ? 'true' : 'false' );
		} );
	}

	function setActive( index ) {
		const all = items();
		if ( ! all.length ) return;

		active = Math.max( 0, Math.min( index, all.length - 1 ) );
		all.forEach( ( item, i ) => item.classList.toggle( 'is-active', i === active ) );
		trigger.setAttribute( 'aria-activedescendant', all[ active ].id );
		all[ active ].scrollIntoView( { block: 'nearest' } );
	}

	function open() {
		if ( ! list.hidden ) return;
		list.hidden = false;
		wrap.classList.add( OPEN_CLASS );
		trigger.setAttribute( 'aria-expanded', 'true' );
		setActive( select.multiple ? active : Math.max( 0, select.selectedIndex ) );
	}

	function close( { focusTrigger = true } = {} ) {
		if ( list.hidden ) return;
		list.hidden = true;
		wrap.classList.remove( OPEN_CLASS );
		trigger.setAttribute( 'aria-expanded', 'false' );
		trigger.removeAttribute( 'aria-activedescendant' );
		if ( focusTrigger ) trigger.focus();
	}

	function choose( index ) {
		const option = select.options[ index ];
		if ( ! option || option.disabled ) return;

		if ( select.multiple ) {
			option.selected = ! option.selected;
		} else {
			select.selectedIndex = index;
		}

		// Let corex-forms' own validation and any listeners see a normal change.
		select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		syncFromNative();

		if ( ! select.multiple ) close();
	}

	// Typeahead: jump to the next option starting with what was typed, as a native select does.
	let typed = '';
	let typedAt = 0;

	function typeahead( key ) {
		const now = Date.now();
		typed = now - typedAt > 800 ? key : typed + key;
		typedAt = now;

		const all = items();
		const from = typed.length === 1 ? active + 1 : active;

		for ( let i = 0; i < all.length; i++ ) {
			const index = ( from + i ) % all.length;
			if ( all[ index ].textContent.toLowerCase().startsWith( typed.toLowerCase() ) ) {
				setActive( index );
				return;
			}
		}
	}

	trigger.addEventListener( 'click', () => ( list.hidden ? open() : close() ) );

	trigger.addEventListener( 'keydown', ( event ) => {
		switch ( event.key ) {
			case 'ArrowDown':
			case 'ArrowUp':
				event.preventDefault();
				if ( list.hidden ) open();
				else setActive( active + ( event.key === 'ArrowDown' ? 1 : -1 ) );
				break;
			case 'Home':
				if ( ! list.hidden ) { event.preventDefault(); setActive( 0 ); }
				break;
			case 'End':
				if ( ! list.hidden ) { event.preventDefault(); setActive( items().length - 1 ); }
				break;
			case 'Enter':
			case ' ':
				event.preventDefault();
				if ( list.hidden ) open();
				else choose( active );
				break;
			case 'Escape':
				if ( ! list.hidden ) { event.preventDefault(); close(); }
				break;
			case 'Tab':
				close( { focusTrigger: false } );
				break;
			default:
				if ( event.key.length === 1 && ! event.metaKey && ! event.ctrlKey && ! event.altKey ) {
					if ( list.hidden ) open();
					typeahead( event.key );
				}
		}
	} );

	list.addEventListener( 'click', ( event ) => {
		const item = event.target.closest( '.perego-select__option' );
		if ( ! item ) return;
		choose( items().indexOf( item ) );
	} );

	list.addEventListener( 'mousemove', ( event ) => {
		const item = event.target.closest( '.perego-select__option' );
		if ( item ) setActive( items().indexOf( item ) );
	} );

	document.addEventListener( 'click', ( event ) => {
		if ( ! wrap.contains( event.target ) ) close( { focusTrigger: false } );
	} );

	// The native control is off-screen; if the browser flags it, put the user on the visible trigger.
	select.addEventListener( 'invalid', () => {
		wrap.classList.add( 'is-invalid' );
		trigger.focus();
	} );
	select.addEventListener( 'change', () => wrap.classList.remove( 'is-invalid' ) );

	syncFromNative();
}

/** Enhance every Corex form select on the page. Safe to call more than once. */
export function initCustomSelects() {
	document.querySelectorAll( 'select.corex-form__input' ).forEach( enhance );
}
