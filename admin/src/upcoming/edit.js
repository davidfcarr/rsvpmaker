/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
const { Component, Fragment } = wp.element;
const { Panel, PanelBody, SelectControl, TextControl, ColorPalette, FontSizePicker } = wp.components;
import apiFetch from '@wordpress/api-fetch';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit(props) {
	const { attributes: { calendar, days, posts_per_page, hideauthor, no_events, nav, type, exclude_type, author, itemcolor, itembg, itemfontsize }, setAttributes, isSelected } = props;
    const rsvptypes = [{value: '', label: 'None selected (optional)'}];
    apiFetch( {path: 'rsvpmaker/v1/types'} ).then( types => {
        if(Array.isArray(types))
                types.map( function(type) { if(type.slug && type.name) rsvptypes.push({value: type.slug, label: type.name }) } );
            else {
                var typesarray = Object.values(types);
                typesarray.map( function(type) { if(type.slug && type.name) rsvptypes.push({value: type.slug, label: type.name }) } );
            }
    }).catch(err => {
        console.log(err);
    });	
    
    const rsvpauthors = [{value: '', label: 'Any'}];
    apiFetch( {path: 'rsvpmaker/v1/authors'} ).then( authors => {
        if(Array.isArray(authors))
                authors.map( function(author) { if(author.ID && author.name) rsvpauthors.push({value: author.ID, label: author.name }) } );
            else {
                authors = Object.values(authors);
                authors.map( function(author) { if(author.ID && author.name) rsvpauthors.push({value: author.ID, label: author.name }) } );
            }
    }).catch(err => {
        console.log(err);
    });	


    function showSampleCalendar () {
        return <div><p><em>Sample Calendar</em></p>
            <table id="cpcalendar" style={{"backgroundColor": '#fff', "color": "#000", "margin": "5px"}} width="100%" cellspacing="0" cellpadding="3">
            <caption><b>September 2023</b></caption>
        <tr>        
        <th>Sunday</th> 
        
        <th>Monday</th> 
        
        <th>Tuesday</th> 
        
        <th>Wednesday</th> 
        
        <th>Thursday</th> 
        
        <th>Friday</th> 
        
        <th>Saturday</th> 
        
        </tr>
        
        <tr id="rsvprow1"><td class="notaday">&nbsp;</td><td class="notaday">&nbsp;</td><td class="notaday">&nbsp;</td><td class="notaday">&nbsp;</td><td class="notaday">&nbsp;</td><td valign="top" class="day past"><div class="day past">1</div><p>&nbsp;</p></td><td valign="top" class="day past"><div class="day past">2</div><p>&nbsp;</p></td></tr>
        <tr id="rsvprow2"><td valign="top" class="day past"><div class="day past">3</div><p>&nbsp;</p></td><td valign="top" class="day past"><div class="day past">4</div><p>&nbsp;</p></td><td valign="top" class="day past">5<div><a  style={{"color":itemcolor,"backgroundColor":itembg,"fontSize":itemfontsize+"px"}} class="rsvpmaker-item rsvpmaker-tooltip Two_Day" href="http://delta.local/rsvpmaker/weekend/" title="Two Day">Two Day<br />&nbsp;7:00 PM EDT</a></div>
        </td><td valign="top" class="day past"><div class="day past">6</div><p>&nbsp;</p></td><td valign="top" class="day past"><div class="day past">7</div><p>&nbsp;</p></td><td valign="top" class="day past">8<div><a  style={{"color":itemcolor,"backgroundColor":itembg,"fontSize":itemfontsize+"px"}} class="rsvpmaker-item rsvpmaker-tooltip Multday_Event" href="http://delta.local/rsvpmaker/multday-event/" title="Multday Event">Multday Event<br />&nbsp;7:00 PM EDT</a></div>
        </td><td valign="top" class="today day"><div class="today day">9</div><p>&nbsp;</p></td></tr>
        <tr id="rsvprow3"><td valign="top" class="day future"><div class="day future">10</div><p>&nbsp;</p></td><td valign="top" class="day future"><div class="day future">11</div><p>&nbsp;</p></td><td valign="top" class="day future"><div class="day future">12</div><p>&nbsp;</p></td><td valign="top" class="day future">13<div><a  style={{"color":itemcolor,"backgroundColor":itembg,"fontSize":itemfontsize+"px"}} class="rsvpmaker-item rsvpmaker-tooltip This_is_a_test" href="http://delta.local/rsvpmaker/this-is-a-test/" title="This is a test">This is a test<br />&nbsp;7:00 PM EDT</a></div>
        </td><td valign="top" class="day future"><div class="day future">14</div><p>&nbsp;</p></td><td valign="top" class="day future"><div class="day future">15</div><p>&nbsp;</p></td><td valign="top" class="day future"><div class="day future">16</div><p>&nbsp;</p></td></tr>
        <tr id="rsvprow4"><td valign="top" class="day future"><div class="day future">17</div><p>&nbsp;</p></td><td valign="top" class="day future"><div class="day future">18</div><p>&nbsp;</p></td><td valign="top" class="day future"><div class="day future">19</div><p>&nbsp;</p></td><td valign="top" class="day future"><div class="day future">20</div><p>&nbsp;</p></td><td valign="top" class="day future"><div class="day future">21</div><p>&nbsp;</p></td><td valign="top" class="day future"><div class="day future">22</div><p>&nbsp;</p></td><td valign="top" class="day future"><div class="day future">23</div><p>&nbsp;</p></td></tr>
        <tr id="rsvprow5"><td valign="top" class="day future"><div class="day future">24</div><p>&nbsp;</p></td><td valign="top" class="day future"><div class="day future">25</div><p>&nbsp;</p></td><td valign="top" class="day future"><div class="day future">26</div><p>&nbsp;</p></td><td valign="top" class="day future"><div class="day future">27</div><p>&nbsp;</p></td><td valign="top" class="day future"><div class="day future">28</div><p>&nbsp;</p></td><td valign="top" class="day future"><div class="day future">29</div><p>&nbsp;</p></td><td valign="top" class="day future"><div class="day future">30</div><p>&nbsp;</p></td></tr>
        <tr id="rsvprow6">
        </tr>
        
        </table>
        </div>;
    }
    
        class UpcomingInspector extends Component {
	
            render() {
                const { attributes: { calendar, excerpt, days, posts_per_page, hideauthor, no_events, nav, type, exclude_type, author, itemcolor, itembg, itemfontsize }, setAttributes, isSelected } = this.props;
                const fontSizes = [
                    {
                        name: __( 'Small' ),
                        slug: 'small',
                        size: 10,
                    },
                    {
                        name: __( 'Medium' ),
                        slug: 'medium',
                        size: 12,
                    },
                    {
                        name: __( 'Large' ),
                        slug: 'large',
                        size: 13,
                    },
                    {
                        name: __( 'Extra Large' ),
                        slug: 'xlarge',
                        size: 14,
                    }
                ];
                const fallbackFontSize = 10;
                    return (
                        <div>
                    <InspectorControls key="upcominginspector">
                    <PanelBody title={ __( 'RSVPMaker Upcoming Options', 'rsvpmaker' ) } >
                    <form  >
                            <SelectControl
                label={__("Display Calendar",'rsvpmaker')}
                value={ calendar }
                options={ [{value: 1, label: __('Yes - Calendar plus events listing')},{value: 0, label:  __('No - Events listing only')},{value: 2, label: __('Calendar only')}] }
                onChange={ ( calendar ) => { console.log('calendar choice '+typeof calendar); setAttributes( { calendar: calendar } ) } }
            />
                            <SelectControl
                label={__("Format",'rsvpmaker')}
                value={ excerpt }
                options={ [{value: 0, label: __('Full Text')},{value: 1, label:  __('Excerpt')}] }
                onChange={ ( excerpt ) => { setAttributes( { excerpt: excerpt } ) } }
            />
                            <SelectControl
                label={__("Events Per Page",'rsvpmaker')}
                value={ posts_per_page }
                options={ [{value: 5, label: 5},
                    {value: 10, label: 10},
                    {value: 15, label: 15},
                    {value: 20, label: 20},
                    {value: 25, label: 25},
                    {value: 30, label: 30},
                    {value: 35, label: 35},
                    {value: 40, label: 40},
                    {value: 45, label: 45},
                    {value: 50, label: 50},
                    {value: '-1', label: 'No limit'}]}
                onChange={ ( posts_per_page ) => { setAttributes( { posts_per_page: posts_per_page } ) } }
            />
                            <SelectControl
                label={__("Date Range",'rsvpmaker')}
                value={ days }
                options={ [{value: 5, label: 5},
                    {value: 30, label: '30 Days'},
                    {value: 60, label: '60 Days'},
                    {value: 90, label: '90 Days'},
                    {value: 180, label: '180 Days'},
                    {value: 366, label: '1 Year'}] }
                onChange={ ( days ) => { setAttributes( { days: days } ) } }
            />
                            <SelectControl
                label={__("Event Type",'rsvpmaker')}
                value={ type }
                options={ rsvptypes }
                onChange={ ( type ) => { setAttributes( { type: type } ) } }
            />
                            <SelectControl
                label={__("Author",'rsvpmaker')}
                value={ author }
                options={ rsvpauthors }
                onChange={ ( author ) => { setAttributes( { author: author } ) } }
            />
                            <SelectControl
                label={__("Exclude Event Type",'rsvpmaker')}
                value={ exclude_type }
                options={ rsvptypes }
                onChange={ ( exclude_type ) => { setAttributes( { exclude_type: exclude_type } ) } }
            />
                            <SelectControl
                label={__("Calendar Navigation",'rsvpmaker')}
                value={ nav }
                options={ [{value: 'top', label: __('Top')},{value: 'bottom', label: __('Bottom')},{value: 'both', label: __('Both')}] }
                onChange={ ( nav ) => { setAttributes( { nav: nav } ) } }
            />
                        <SelectControl
                label={__("Show Event Author",'rsvpmaker')}
                value={ hideauthor }
                options={ [
                    { label: 'No', value: true },
                    { label: 'Yes', value: false },
                ] }
                onChange={ ( hideauthor ) => { setAttributes( { hideauthor: hideauthor } ) } }
            />
                        <TextControl
                label={__("Text to show for no events listed",'rsvpmaker')}
                value={ no_events }
                onChange={ ( no_events ) => { setAttributes( { no_events: no_events } ) } }
            />
        
                        </form>
            </PanelBody>
            <Panel header="Calendar Colors">
            <PanelBody title={ __( 'Calendar Item Text Color', 'rsvpmaker' ) } >
            <ColorPalette 
                label={__("Calendar item text color",'rsvpmaker')}
                colors = {wp.data.select ("core/editor").getEditorSettings ().colors}
                value={ itemcolor }
                defaultValue={ itemcolor }
                onChange={ ( itemcolor ) => { setAttributes( { itemcolor } ) } }	
            />
            </PanelBody>
            <PanelBody title={ __( 'Calendar Item Background Color', 'rsvpmaker' ) } >
            <ColorPalette 
                colors = {wp.data.select ("core/editor").getEditorSettings ().colors}
                label={__("Calendar item background color",'rsvpmaker')}
                value={ itembg }
                defaultValue={ itembg }
                onChange={ ( itembg ) => { setAttributes( { itembg } ) } }	
            />
            <div><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="24" height="24" aria-hidden="true" focusable="false"><path d="M12 4c-4.4 0-8 3.6-8 8v.1c0 4.1 3.2 7.5 7.2 7.9h.8c4.4 0 8-3.6 8-8s-3.6-8-8-8zm0 15V5c3.9 0 7 3.1 7 7s-3.1 7-7 7z"></path></svg> <em>See the styles tab for the overall text and background color settings.</em></div>
            </PanelBody>
            </Panel>
            <Panel header="Calendar Fonts">
            <PanelBody title={ __( 'Calendar Item Font Size', 'rsvpmaker' ) }  >             
            <FontSizePicker 
                label={__("Calendar item text size",'rsvpmaker')}
                value={ itemfontsize }
                fontSizes={ fontSizes }
                fallbackFontSize={ fallbackFontSize }
                onChange={ ( itemfontsize ) => { setAttributes( { itemfontsize: itemfontsize } ) } }		
            />
            </PanelBody>
            </Panel>
            </InspectorControls>
            </div>
        );	} }
        

    return (
				<Fragment>
                <div { ...useBlockProps() }>
                        <UpcomingInspector {...props}/>
                    <p  class="dashicons-before dashicons-calendar-alt"><strong>RSVPMaker</strong>: Add an Events Listing and/or Calendar Display
                    </p>
                    <p><strong>{__('Click here to set options.','rsvpmaker')}</strong></p>
                    { isSelected && ( <p><strong>{__('Set options from properties sidebar.','rsvpmaker')}</strong></p> ) }
                    { (calendar > 0) && showSampleCalendar() }
                    { (!calendar || 1 == calendar) && <p><em>Events will be displayed here.</em></p> }
                   </div>
                 </Fragment>
    );
}
