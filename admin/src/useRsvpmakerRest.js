import { useSelect } from '@wordpress/data';
export function useRsvpmakerRest() {
    return useSelect( ( select ) => {
        const rs = select( 'rsvpmaker' );
        if(!rs) {
            console.log('useRsvpmakerRest: rsvpmaker store not found');
            return {};
        }
        return rs.getSettings();
    } );
}
