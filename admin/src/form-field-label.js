export function applyFieldLabelChange( {
	label,
	attributes,
	setAttributes,
	setGuestform = true,
} ) {
	
	if ( attributes.slug && (('first' === attributes.slug) || ('last' === attributes.slug)  || ('email' === attributes.slug) || attributes.slug.length > 25)) {
		setAttributes( { label: label } );
		return;
	}

	let simpleSlug = label.toLowerCase().replaceAll( /[^A-Za-z0-9]+/g, '_' );
	if ( simpleSlug.length > 20 ) {
		simpleSlug = simpleSlug.substring( 0, 20 );
	}
	simpleSlug += '_' + Date.now();

	const update = {
		slug: simpleSlug,
		label: label,
	};
	if ( setGuestform ) {
		update.guestform = true;
	}

	setAttributes( update );
}