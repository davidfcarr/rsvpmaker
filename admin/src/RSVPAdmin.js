import React, {useState, useEffect, Suspense} from "react"
import { TabPanel, SelectControl, RadioControl } from '@wordpress/components';
import General from './General.js'
import Security from './Security.js'
import Payment from './Payment.js'
import Email from './Email.js'
import GroupEmail from './GroupEmail.js'
import Forms from './Forms.js'

export default function RSVPAdmin () {
    const onSelect = ( tabName ) => {
        console.log( 'Selecting tab', tabName );
    };

    const url = window.location.href;
    const tabarg = url.match(/tab=([^&]+)/);
    const start = (tabarg) ? tabarg[1] : 'general';

    const MyTabPanel = () => (
        <TabPanel
            className="rsvpmaker-tab-panel"
            activeClass="nav-tab-active"
            onSelect={ onSelect }
            initialTabName={start}
            tabs={ [
                {
                    name: 'general',
                    title: 'General Settings',
                    className: 'nav-tab',
                },
                {
                    name: 'security',
                    title: 'Security',
                    className: 'nav-tab',
                },
                {
                    name: 'payment',
                    title: 'Payment',
                    className: 'nav-tab',
                },
                {
                    name: 'email',
                    title: 'Email and Mailing List',
                    className: 'nav-tab',
                },
                {
                    name: 'groupemail',
                    title: 'Group Email',
                    className: 'nav-tab',
                },
                {
                    name: 'forms',
                    title: 'Forms',
                    className: 'nav-tab',
                },
            ] }
        >
            { ( tab ) => {
                if('general' == tab.name)
                    return <General />
                if('security' == tab.name)
                    return <Security />
                if('payment' == tab.name)
                    return <Payment />
                if('email' == tab.name)
                    return <Email />
                if('groupemail' == tab.name)
                    return <GroupEmail />
                if('forms' == tab.name)
                    return <Forms />
               else
                return <section><p>{ tab.title }</p></section>
        } }
        </TabPanel>
    );

    return <MyTabPanel />
}