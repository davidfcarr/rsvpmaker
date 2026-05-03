import React, {useState, lazy, Suspense} from "react"
import { TabPanel, SelectControl, RadioControl } from '@wordpress/components';

const General = lazy(() => import('./General.js'));
const Security = lazy(() => import('./Security.js'));
const Payment = lazy(() => import('./Payment.js'));
const Email = lazy(() => import('./Email.js'));
const Forms = lazy(() => import('./Forms.js'));

export default function RSVPAdmin (props) {
    const onSelect = ( tabName ) => {
        console.log( 'Selecting tab', tabName );
    };

    const url = window.location.href;
    const tabarg = url.match(/tab=([^&]+)/);
    const start = (tabarg) ? tabarg[1] : 'general';
    const [changes,setChanges] = useState([]);

    function addChange(key,value,type='rsvp_options') {
        console.log('addchange key',key);
        console.log('addchange value',value);
        console.log('addchange type',type);
        manageChanges(key,value,type);
    }
    
    function manageChanges(key=null,value=null,type='rsvp_options') {
        if(!key)
            return changes;
        if('reset' == key)
            {
                setChanges([]);
            }
        setChanges((ch) => {
            if(!ch || !Array.isArray(ch)) 
                return [].push(value);
            console.log('changeset start',ch);
            console.log('changeset new value',value);
            const exists = ch.findIndex( (item) => (item.key==key && item.type==type) );
            console.log('changeset exists test',exists);
            if(exists > -1)
                ch[exists].value = value;
            else
                ch.push({'key':key,'value':value,'type':type});      
            console.log('changeset',ch);
            return(ch);
        });
    }
    
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
                    name: 'forms',
                    title: 'Forms',
                    className: 'nav-tab',
                },
                {
                    name: 'email',
                    title: 'Email',
                    className: 'nav-tab',
                },
            ] }
        >
            { ( tab ) => {
                if('general' == tab.name)
                    return <Suspense fallback={<p>Loading tab ...</p>}><General addChange={addChange} setChanges={setChanges} changes={changes} /></Suspense>
                if('security' == tab.name)
                    return <Suspense fallback={<p>Loading tab ...</p>}><Security  addChange={addChange} setChanges={setChanges} changes={changes} /></Suspense>
                if('payment' == tab.name)
                    return <Suspense fallback={<p>Loading tab ...</p>}><Payment  addChange={addChange} setChanges={setChanges} changes={changes} /></Suspense>
                if('forms' == tab.name)
                    return <Suspense fallback={<p>Loading tab ...</p>}><Forms form_id={props.form_id} addChange={addChange} setChanges={setChanges} changes={changes} /></Suspense>
                if('email' == tab.name)
                    return <Suspense fallback={<p>Loading tab ...</p>}><Email addChange={addChange} setChanges={setChanges} changes={changes} /></Suspense>
               else
                return <section><p>{ tab.title }</p></section>
        } }
        </TabPanel>
    );

    return <MyTabPanel />
}