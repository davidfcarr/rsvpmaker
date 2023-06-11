/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';
const { InspectorControls } = wp.blockEditor;
const { TextControl } = wp.components;

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps } from '@wordpress/block-editor';

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
	const { isSelected } = props;
    const {attributes: {youtubelink}, setAttributes} = props;
    let id = '1234';
    console.log('youtube link',youtubelink);
    if(youtubelink) {
        if(youtubelink.indexOf('watch?v=') > 0)
        {
            const match = youtubelink.match(/watch\?v=([^&]+)/);
            if(match && match.length > 1)
                id = match[1];
            console.log('watch= match',match);        
        }
        else {
            //https://youtu.be/Z7KsWatRVOg
            const match = youtubelink.match(/youtu.be\/([^?]+)/);
            if(match && match.length > 1)
                id = match[1];
            console.log('youtu.be match',match);
        }
    }
    const background = {display: 'block', marginLeft: 'auto', marginRight: 'auto', width: '500px', height: '283px', textAlign: 'center', paddingTop: '150px', marginBottom: '-140px', backgroundSize: 'contain', backgroundRepeat: 'no-repeat', textDecoration: 'none', backgroundImage: 'url(https://img.youtube.com/vi/'+id+'/mqdefault.jpg)'};
    const arrow = window.location.origin+'/wp-content/plugins/rsvpmaker/images/youtube-button-100px.png';
		return (			
<div { ...useBlockProps() }>
<InspectorControls key="yemail-inspector">
<TextControl
label={ __( 'YouTube URL', 'rsvpmaker' ) }
value={ youtubelink }
onChange={ ( value ) => setAttributes( {youtubelink: value}  ) }
/>
</InspectorControls>
<a href="#" style={background}><img style={{objectFit: 'contain', maxWidth: '100%', maxHeight: '100%', opacity: '0.6'}} src={arrow} /></a>
</div>
		);
}
