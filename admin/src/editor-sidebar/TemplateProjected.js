import React, {useState} from "react"
const { Modal } = wp.components;
import Checkbox from './Checkbox';
import { SanitizedHTML } from "../SanitizedHTML.js";
const { subscribe } = wp.data;
import apiClient from '../http-common.js';
import {useQuery, useMutation, useQueryClient} from 'react-query';
import { useSelect } from '@wordpress/data';

export default function TemplateProjected (props) {
    if(-1 == window.location.href.indexOf('post='))
        return;//don't display if still under construction
    const [ isOpen, setOpen ] = useState( false );
    const [isCheckAll, setIsCheckAll] = useState(false);
    const [isCheck, setIsCheck] = useState([]);
      function close() {
        setOpen(false);
        if(props.setOpenModal)
            props.setOpenModal(false);
    }
    function open() {
        setOpen(true);
    }

    let wasSavingPost = wp.data.select( 'core/editor' ).isSavingPost();
    let wasAutosavingPost = wp.data.select( 'core/editor' ).isAutosavingPost();
    let wasPreviewingPost = wp.data.select( 'core/editor' ).isPreviewingPost();
    // determine whether to show notice
    subscribe( () => {
        const isSavingPost = wp.data.select( 'core/editor' ).isSavingPost();
        const isAutosavingPost = wp.data.select( 'core/editor' ).isAutosavingPost();
        const isPreviewingPost = wp.data.select( 'core/editor' ).isPreviewingPost();
        const hasActiveMetaBoxes = wp.data.select( 'core/edit-post' ).hasMetaBoxes();
        
        // Save metaboxes on save completion, except for autosaves that are not a post preview.
        const shouldTriggerTemplateNotice = (
                ( wasSavingPost && ! isSavingPost && ! wasAutosavingPost ) ||
                ( wasAutosavingPost && wasPreviewingPost && ! isPreviewingPost )
            );

        // Save current state for next inspection.
        wasSavingPost = isSavingPost;
        wasAutosavingPost = isAutosavingPost;
        wasPreviewingPost = isPreviewingPost;

        if ( shouldTriggerTemplateNotice ) {
            setOpen(true);
        }
});

    function fetchProjected() {
        const parts = window.location.href.split('?');
        return apiClient.get('template_projected?'+parts[1]);
    }
    const {data,isLoading,isError} = useQuery(['template_projected'], fetchProjected, { enabled: true, retry: 2, onSuccess: (data, error, variables, context) => {
        console.log('template_projected',data);
    }, onError: (err, variables, context) => {
       console.log('error retrieving template_projected',err);
    }, refetchInterval: false });

    if(isError)
        return <p>Error loading projected dates.</p>

    const handleSelectAll = e => {
        setIsCheckAll(!isCheckAll);
        const mod = tdata.dates.map(li => li.id);
        console.log('selectall dates',tdata.dates);
        console.log('selectall mod is check',mod); 
        setIsCheck(mod);
        if (isCheckAll) {
          setIsCheck([]);
        }
      };
    
      const handleClick = e => {
        const { id, checked } = e.target;
        console.log('handleclick id',id);
        setIsCheck([...isCheck, id]);
        if (!checked) {
          setIsCheck(isCheck.filter(item => item !== id));
        }
      };

    const tdata = (data) ? data.data : null;
    let catalog = [];

    if(!isLoading && tdata) {
        console.log('tdata loaded',tdata);
        console.log('tdata dates',tdata.dates);
        catalog = tdata.dates.map(
            (item,i) => {
                if('existing' == item.type)
                    return (<p><Checkbox id={item.id} isChecked={isCheck.includes(item.id)} handleClick={handleClick} name={'update_from_template['+i+']'} type="checkbox" value={item.event} /> {item.prettydate+' '+item.note}
                {item.dups.sametime.length > 0 && <div className="alertnotice">Scheduled for the same time: {item.dups.sametime.map((d) => { return <div>{d.post_title} <a href={d.permalink}>View</a> <a href={d.edit}>Edit</a> </div> })}</div>}
                {item.dups.sameday.length > 0 && <div className="alertnotice">Scheduled for the same day: {item.dups.sameday.map((d) => { return <div>{d.post_title} {d.prettydate} <a href={d.permalink}>View</a> <a href={d.edit}>Edit</a> </div> })}</div>}
                </p>);
                else 
                    return (<p><Checkbox id={item.id} isChecked={isCheck.includes(item.id)} handleClick={handleClick} name={'recur_check['+i+']'} type="checkbox" value={i} /> <input size="4" type="text" name={'recur_year['+i+']'} defaultValue={item.year} /> <input size="2" type="text" name={'recur_month['+i+']'} defaultValue={item.month} /> <input size="2" type="text" name={'recur_day['+i+']'} defaultValue={item.day} /> <input type="text" name={'recur_title['+i+']'} defaultValue={tdata.title} /> {item.note && <span className="alertnotice">{item.note}</span>}
                    {item.dups.sametime.length > 0 && <div>Scheduled for the same time: {item.dups.sametime.map((d) => { return <span>{d.post_title} <a  target="_blank" href={d.permalink}>View</a> <a target="_blank" href={d.edit}>Edit</a> </span> })}</div>}
                    {item.dups.sameday.length > 0 && <div>Scheduled for the same day: {item.dups.sameday.map((d) => { return <span>{d.post_title} {d.prettydate} <a  target="_blank" href={d.permalink}>View</a> <a target="_blank" href={d.edit}>Edit</a> </span> })}</div>}
                </p>)        
            }
        )
    }

if(catalog.length == 0)
    return null;
const rsvpmaker_rest = useSelect( ( select ) => {
    const rs = select( 'rsvpmaker' );
    if(!rs)
    {
        
        return {};
    }
    const rsvpmaker_rest = rs.getSettings();
    return rsvpmaker_rest;
} );

return (
    <div>
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-calendar-check" viewBox="0 0 16 16">
  <path d="M10.854 7.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 9.793l2.646-2.647a.5.5 0 0 1 .708 0z"/>
  <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
</svg>   
{!isOpen && <p><button onClick={open} >Create/Update</button><br /><em>Click to see more event options</em></p>}
{isOpen && <Modal className="rsvpmaker-create-update" title="Create/Update from Template" onRequestClose={ close } >
{isLoading && <p>Loading ...</p>}
{!isLoading && tdata && 
    <form id="create-update-form" style={{'width': '800px','paddingBottom': '100px'}} method="post" action={tdata.action}>
    <div id="create-update-controls" style={{'width': '150px','position': 'sticky','float':'right','backgroundColor':'#ffffff'}}>
    <p>New post status<br /><input type="radio" name="newstatus" value="publish" checked="checked" /> Published <br /><input type="radio" name="newstatus" value="draft" /> Draft</p>
 
    <input type="hidden" name="timelord" value={rsvpmaker_rest.timelord} />
    <button>Create/Update</button>
    </div>
    <Checkbox
        type="checkbox"
        name="selectAll"
        id="selectAll"
        handleClick={handleSelectAll}
        isChecked={isCheckAll}
        value={1}
      /> Check all
    <div><input type="checkbox" name="metadata_only" value="1" /> Update Metadata Only (price, form etc. but not title or content)</div>
    <div className="dates">{catalog}</div>
</form>}

</Modal>}
    </div>
)

}
