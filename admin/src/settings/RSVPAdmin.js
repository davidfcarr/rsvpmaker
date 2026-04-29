import { TabPanel } from '@wordpress/components';
import Forms from './Forms.js'
import { RsvpmakerSettings, PaymentSettings, PostmarkSettings, ConfirmationSettings } from "./components";
import './style.css';

export default function RSVPAdmin (props) {
    const onSelect = ( tabName ) => {
        console.log( 'Selecting tab', tabName );
    };

    const url = window.location.href;
    const tabarg = url.match(/tab=([^&]+)/);
    const start = (tabarg) ? tabarg[1] : 'rsvp_options';
        
    const MyTabPanel = () => (
        <TabPanel
            className="rsvpmaker-tab-panel"
            orientation="vertical"
            activeClass="nav-tab-active"
            onSelect={ onSelect }
            initialTabName={start}
            tabs={ [
                {
                    name: 'rsvp_options',
                    title: 'Basics and Defaults',
                    className: 'nav-tab',
                },
                {
                    name: 'confirmation',
                    title: 'Confirmation Message',
                    className: 'nav-tab',
                },
                {
                    name: 'payment',
                    title: 'Payment',
                    className: 'nav-tab',
                },
                {
                    name: 'forms',
                    title: 'Forms',
                    className: 'nav-tab',
                },
                {
                    name: 'postmark',
                    title: 'Postmark Setup',
                    className: 'nav-tab',
                },
            ] }
        >
            { ( tab ) => {
                if('rsvp_options' == tab.name)
                    return <RsvpmakerSettings />
                if('payment' == tab.name)
                    return <PaymentSettings />
                if('confirmation' == tab.name)
                    return <ConfirmationSettings />
                if('forms' == tab.name)
                    return <Forms form_id={props.form_id} />
                if('postmark' == tab.name)
                    return <PostmarkSettings />
                return <section><p>{ tab.title }</p></section>
        } }
        </TabPanel>
    );

    return <MyTabPanel />
}