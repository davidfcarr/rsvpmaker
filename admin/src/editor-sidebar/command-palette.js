//not yet working
import { __ } from '@wordpress/i18n';
import {calendar} from '@wordpress/icons';

    if(wp.commands)
    wp.commands.useCommand( {
        name: 'rsvpmaker/show-options',
        label: __( 'Show RSVPMaker Options' ),
        icon: calendar,
        callback: ({ close }) => {
            alert('rsvp options clicked');
            //setOpenModal(true);
            close();
        },
    } );
