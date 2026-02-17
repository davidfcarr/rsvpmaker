/**
 * WordPress dependencies
 */
import { createSlotFill } from '@wordpress/components';

/**
 * Create our Slot and Fill components
 */
const { Fill, Slot } = createSlotFill( 'RSVPControlsInheritedQuery' );

const RSVPControlsInheritedQuery = ( { children } ) => <Fill>{ children }</Fill>;

RSVPControlsInheritedQuery.Slot = Slot;

export default RSVPControlsInheritedQuery;