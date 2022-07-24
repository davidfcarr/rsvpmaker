/**
 * BLOCK: limited time
 *
 */

const { __ } = wp.i18n;
const { registerBlockType } = wp.blocks;
const { InnerBlocks, InspectorControls, MediaUpload, MediaUploadCheck } = wp.blockEditor;
const { Component, Fragment, useState, useEffect, RawHTML } = wp.element;
const { Panel, PanelBody, SelectControl, TextControl, ColorPicker, ColorPalette, Button, ResponsiveWrapper } = wp.components;
import { useBlockProps } from '@wordpress/block-editor';
import apiFetch from '@wordpress/api-fetch';

const CTEMPLATE = [
	[ 'core/paragraph', { placeholder: __('Email content','rsvpmaker')}],
];

registerBlockType( 'rsvpmaker/emailbody', {
	title: ( 'RSVP Email Body Wrapper' ), // Block title.
	icon: 'admin-comments', 
	category: 'rsvpmaker',
	keywords: [
		( 'RSVPMaker' ),
		( 'Email' ),
		( 'Background' ),
	],
attributes: {
        content: {
            type: 'array',
            source: 'children',
            selector: 'p',
        },
        backgroundColor: {
            type: 'string',
            default: '#efefef',
        },
        color: {
            type: 'string',
            default: '#000',
        },
        padding: {
            type: 'string',
            default: '5px',
        },
        mediaId: {
            type: 'number',
            default: 0,
        },
        mediaUrl: {
            type: 'string',
            default: '',
        }
},

    edit: function( props ) {	

	const { attributes, className, setAttributes, isSelected } = props;

    const bodyStyle = {
        backgroundColor: attributes.backgroundColor,
        color: attributes.color,
        padding: attributes.padding,
        backgroundImage: attributes.mediaUrl != '' ? 'url("' + attributes.mediaUrl + '")' : 'none',
        backgroundRepeat: 'no-repeat',
        backgroundSize: 'cover',
        backgroundAttachment: 'fixed',
    };
    const blockProps = useBlockProps({ style: bodyStyle});
	return (
		<Fragment>
		<EmailBodyInspector { ...props } />
<div { ...blockProps} >
	<InnerBlocks />
</div>
		</Fragment>
		);
    },
    save: function( { attributes, className } ) {
        
        const bodyStyle = (attributes.mediaUrl != '') ? {
            backgroundColor: attributes.backgroundColor,
            color: attributes.color,
            padding: attributes.padding,
            backgroundImage: attributes.mediaUrl != '' ? 'url("' + attributes.mediaUrl + '")' : 'none',
            backgroundRepeat: 'no-repeat',
            backgroundSize: 'contain',
        } :  {
            backgroundColor: attributes.backgroundColor,
            color: attributes.color,
            padding: attributes.padding,
        };
        const blockProps = useBlockProps.save({ style: bodyStyle });
        return <div { ...blockProps}><InnerBlocks.Content /></div>;
    }
});

class EmailBodyInspector extends Component {

	render() {
        const removeMedia = () => {
            setAttributes({
                mediaId: 0,
                mediaUrl: ''
            });
        }
     
         const onSelectMedia = (media) => {
            setAttributes({
                mediaId: media.id,
                mediaUrl: media.url
            });
        }
            
		const { media, attributes, attributes: {backgroundColor ,paletteBackgroundColor, padding, backgroundImage}, setAttributes, className } = this.props;
        const colors = wp.data.select('core/block-editor').getSettings().colors;
        console.log(media);
		return (
			<InspectorControls key="inspector">
			<PanelBody title={ __( 'Background Color', 'rsvpmaker' ) } >
            <ColorPalette 
            colors={ colors }
            value={ backgroundColor }
            onChange={ ( backgroundColor ) => setAttributes( {backgroundColor: backgroundColor} ) }
            />
            <ColorPicker
            label="Custom Background Color"
            color={backgroundColor}
            onChange={ ( backgroundColor ) => { setAttributes( { backgroundColor: backgroundColor } ) } }
            enableAlpha
        />  
				</PanelBody>
                <PanelBody
					title={__('Select block background image', 'rsvpmaker')}
					initialOpen={ true }
				>
					<div className="editor-post-featured-image background-image">
						<MediaUploadCheck>
							<MediaUpload
								onSelect={onSelectMedia}
								value={attributes.mediaId}
								allowedTypes={ ['image'] }
								render={({open}) => (
									<Button 
										className={attributes.mediaId == 0 ? 'editor-post-featured-image__toggle' : 'editor-post-featured-image__preview'}
										onClick={open}
									>
										{attributes.mediaId == 0 && __('Choose an image', 'awp')}
									</Button>
								)}
							/>
						</MediaUploadCheck>
						{attributes.mediaId != 0 && 
							<MediaUploadCheck>
								<MediaUpload
									title={__('Replace image', 'rsvpmaker')}
									value={attributes.mediaId}
									onSelect={onSelectMedia}
									allowedTypes={['image']}
									render={({open}) => (
										<Button onClick={open} variant="secondary" isLarge>{__('Replace image', 'awp')}</Button>
									)}
								/>
							</MediaUploadCheck>
						}
						{attributes.mediaId != 0 && 
							<MediaUploadCheck>
								<Button onClick={removeMedia} isLink isDestructive>{__('Remove image', 'awp')}</Button>
							</MediaUploadCheck>
						}
					</div>
				</PanelBody>
			</InspectorControls>
		);
	}
}

registerBlockType( 'rsvpmaker/emailcontent', {
	title: ( 'RSVP Email Content Wrapper' ), // Block title.
	icon: 'admin-comments', 
	category: 'rsvpmaker',
	keywords: [
		( 'RSVPMaker' ),
		( 'Email' ),
		( 'Background' ),
	],
attributes: {
        content: {
            type: 'array',
            source: 'children',
            selector: 'p',
        },
        backgroundColor: {
            type: 'string',
            default: '#fff',
        },
        color: {
            type: 'string',
            default: '#000',
        },
        padding: {
            type: 'string',
            default: '5px',
        },
        maxWidth: {
            type: 'string',
            default: '600px',
        },
        border: {
            type: 'string',
            default: 'thin solid gray',
        },
        marginLeft: {
            type: 'string',
            default: 'auto',
        },
        marginRight: {
            type: 'string',
            default: 'auto',
        },
},

    edit: function( props ) {	

	const { attributes, className, setAttributes, isSelected } = props;
    const bodyStyle = {
        backgroundColor: attributes.backgroundColor,
        color: attributes.color,
        padding: attributes.padding,
        marginLeft: attributes.marginLeft,
        marginRight: attributes.marginRight,
        maxWidth: attributes.maxWidth,
        border: attributes.border,
        minHeight: '20px',
    };
    const blockProps = useBlockProps( { style: bodyStyle } );

	return (
		<Fragment>
		<EmailContentInspector { ...props } />
<div className={className}  { ...blockProps } >
    <InnerBlocks template={CTEMPLATE} />
</div>
		</Fragment>
		);
    },
    save: function( { attributes, className } ) {
        const bodyStyle = {
            backgroundColor: attributes.backgroundColor,
            color: attributes.color,
            padding: attributes.padding,
            marginLeft: attributes.marginLeft,
            marginRight: attributes.marginRight,
            maxWidth: attributes.maxWidth,
            border: attributes.border,
            minHeight: '20px',
            marginBottom: '5px',
        };
        const blockProps = useBlockProps.save({ style: bodyStyle });
        return <div { ...blockProps } className={className}><InnerBlocks.Content /></div>;
    }
});

class EmailContentInspector extends Component {
    
	render() {
            
		const { attributes: {backgroundColor ,color, padding, marginLeft, marginRight, maxWidth, border}, setAttributes, className } = this.props;
        const colors = wp.data.select('core/block-editor').getSettings().colors;
		return (
			<InspectorControls key="inspector">
			<PanelBody title={ __( 'Style', 'rsvpmaker' ) } >
            <h3>Background Color</h3>
            <ColorPalette 
            colors={ colors }
            value={ backgroundColor }
            onChange={ ( backgroundColor ) => setAttributes( {backgroundColor: backgroundColor} ) }
            />
            <ColorPicker
            label="Background Color"
            color={backgroundColor}
            onChange={ ( backgroundColor ) => { setAttributes( { backgroundColor: backgroundColor } ) } }
            enableAlpha
            />
            <h3>Text Color</h3>
            <ColorPalette 
            colors={ colors }
            value={ color }
            onChange={ ( color ) => setAttributes( {color: color} ) }
            />
            <ColorPicker
            color={color}
            onChange={ ( color ) => { setAttributes( { color: color } ) } }
            enableAlpha
        />  
            <TextControl
            label="Border"
            value={ border }
            onChange={ ( border ) => { setAttributes( { border: border } ) } }
            />
            <TextControl
            label="Margin Left"
            value={ marginLeft }
            onChange={ ( marginLeft ) => { setAttributes( { marginLeft: marginLeft } ) } }
            />
            <TextControl
            label="Margin Right"
            value={ marginRight }
            onChange={ ( marginRight ) => { setAttributes( { marginRight: marginRight } ) } }
            />
            <TextControl
            label="Max Width"
            value={ maxWidth }
            onChange={ ( maxWidth ) => { setAttributes( { maxWidth: maxWidth } ) } }
            />
            <TextControl
            label="Padding"
            value={ padding }
            onChange={ ( padding ) => { setAttributes( { padding: padding } ) } }
            />
				</PanelBody>
			</InspectorControls>
		);
	}

}

const postsPath = 'rsvpmaker/v1/preview/emailpostorposts'; 
const catsPath = "rsvpmaker/v1/postsorcategories";

registerBlockType( 'rsvpmaker/embedposts', {
	title: ( 'RSVP Email Embed Post or Post List' ), // Block title.
	icon: 'email-alt2', 
	category: 'rsvpmaker',
	keywords: [
		( 'RSVPMaker' ),
		( 'Email' ),
		( 'Posts' ),
	],
attributes: {
    "selection": {
        "type": "string"
    },
    "posts_per_page": {
        "type": "integer",
        "default" : 1
    },
},
    edit: function( {attributes, setAttributes} ) {
        const [cats, setCats] = useState([]);
        const [preview, setPreview] = useState('');
    
        const {selection, posts_per_page} = attributes;
    
        useEffect( async () => {
            if(cats.length == 0) {
                const fetchedCats = await apiFetch({path: catsPath});
                setCats( fetchedCats );	
            }
        });
    
        const fetchPosts = async () => {
            const path = selection ? `${postsPath}?selection=${selection}&posts_per_page=${posts_per_page}` : postsPath;
            const result = await apiFetch({path});
            setPreview(result);
        }
    
        useEffect( () => { fetchPosts(); }, [selection, posts_per_page]);
    
        if ( '' == preview ) {
            return <div {...useBlockProps()}>Loading posts</div>;
        }
    
        return (
            <div>
                <InspectorControls>
                    <Panel>
                        <PanelBody>
                            <SelectControl
                                label={__("Select Post or Category", "rsvpmaker")}
                                options={cats}
                                value={selection}
                                onChange={(val) => setAttributes({selection: val})}
                            />
                            <SelectControl
                                label={__("Number of Posts for Listings", "rsvpmaker")}
                                options={[
                                {"label":'1',value:1},
                                {"label":'2',value:2},
                                {"label":'3',value:3},
                                {"label":'4',value:4},
                                {"label":'5',value:5},
                                {"label":'6',value:6},
                                {"label":'7',value:7},
                                {"label":'8',value:8},
                                {"label":'9',value:9},
                                {"label":'10',value:10}
                            ]}
                                value={posts_per_page}
                                onChange={(val) => setAttributes({posts_per_page: val})}
                            />
                        </PanelBody>
                    </Panel>
                </InspectorControls>
                <div {...useBlockProps()}>
                    <div className="mylatests-list">
                        <RawHTML>{preview}</RawHTML>
                    </div>
                </div>
    
            </div>
        );        
    },
    save: function () {
        return null;
    }
});

registerBlockType( 'rsvpmaker/emailguestsignup', {
	title: ( 'RSVP Email Guest List Signup' ), // Block title.
	icon: 'admin-comments', 
	category: 'rsvpmaker',
	keywords: [
		( 'RSVPMaker' ),
		( 'Email' ),
		( 'Signup' ),
	],
attributes: {
        fields: {
            type: 'string',
            default: '',
        },
},

    edit: function( props ) {	

	const { attributes: {fields}, className, setAttributes, isSelected } = props;
    const bodyStyle = {
        backgroundColor: '#fff',
        padding: '5px',
    };
    const blockProps = useBlockProps( { style: bodyStyle, className: 'wp-block-rsvpmaker-emailguestsignup' } );

    console.log(blockProps);

	return (
		<Fragment>
                <InspectorControls>
                    <Panel>
                        <PanelBody>
                            <SelectControl
                                label={__("Fields to Display", "rsvpmaker")}
                                options={[
                                {"label":__('First Name, Last Name, Email'),value:''},
                                {"label":__('First Name, Email'),value:'first'},
                                {"label":__('Email'),value:'email'},
                            ]}
                                value={fields}
                                onChange={(val) => setAttributes({fields: val})}
                            />
                        </PanelBody>
                    </Panel>
                </InspectorControls>
<div className={className}  { ...blockProps} >
{'' == fields && (
<div>
<h4>{__('Email List Signup','rsvpmaker')}</h4>
<p><label>{__('First Name','rsvpmaker')}</label> <input type="text" id="rsvpguest_list_first"  name="rsvpguest_list_first" /></p>
<p><label>{__('Last Name','rsvpmaker')}</label> <input type="text" id="rsvpguest_list_last"  name="rsvpguest_list_last" /></p>
<p><label>{__('Email','rsvpmaker')}</label> <input type="text" id="rsvpguest_list_email"  name="rsvpguest_list_email" /></p>
</div>
)}
{'first' == fields && (
<div>
<h4>{__('Email List Signup','rsvpmaker')}</h4>
<p><label>{__('First Name','rsvpmaker')}</label><input type="text" id="rsvpguest_list_first"  name="rsvpguest_list_first" /></p>
<input type="hidden" id="rsvpguest_list_last"  name="rsvpguest_list_last" />
<p><label>{__('Email','rsvpmaker')}</label><input type="text" id="rsvpguest_list_email"  name="rsvpguest_list_email" /></p>
</div>
)}
{'email' == fields && (
<div>
<h4>{__('Email List Signup','rsvpmaker')}</h4>
<input type="hidden" id="rsvpguest_list_first"  name="rsvpguest_list_first" />
<input type="hidden" id="rsvpguest_list_last"  name="rsvpguest_list_last" />
<p><label>{__('Email','rsvpmaker')}</label><input type="text" id="rsvpguest_list_email"  name="rsvpguest_list_email" /></p>
</div>
)}

</div>
		</Fragment>
		);
    },
    save: function( ) {
        return null;
    }
});
