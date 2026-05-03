import { lazy, Suspense } from 'react';
import { TabPanel } from '@wordpress/components';

const Forms = lazy(() => import('./Forms.js'));
const RsvpmakerSettings = lazy(() =>
    import('./components/rsvpmaker-settings').then((module) => ({ default: module.RsvpmakerSettings }))
);
const PaymentSettings = lazy(() =>
    import('./components/payment-settings').then((module) => ({ default: module.PaymentSettings }))
);
const PostmarkSettings = lazy(() =>
    import('./components/postmark-settings').then((module) => ({ default: module.PostmarkSettings }))
);
const ConfirmationSettings = lazy(() =>
    import('./components/confirmation-settings').then((module) => ({ default: module.ConfirmationSettings }))
);
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
                    return <Suspense fallback={<p>Loading tab ...</p>}><RsvpmakerSettings /></Suspense>
                if('payment' == tab.name)
                    return <Suspense fallback={<p>Loading tab ...</p>}><PaymentSettings /></Suspense>
                if('confirmation' == tab.name)
                    return <Suspense fallback={<p>Loading tab ...</p>}><ConfirmationSettings /></Suspense>
                if('forms' == tab.name)
                    return <Suspense fallback={<p>Loading tab ...</p>}><Forms form_id={props.form_id} /></Suspense>
                if('postmark' == tab.name)
                    return <Suspense fallback={<p>Loading tab ...</p>}><PostmarkSettings /></Suspense>
                return <section><p>{ tab.title }</p></section>
        } }
        </TabPanel>
    );

    return <MyTabPanel />
}