const { __ } = wp.i18n;
const { registerBlockType } = wp.blocks;
const { InnerBlocks, InspectorControls, MediaUpload, MediaUploadCheck } = wp.blockEditor;
const { Component, Fragment, useState, useEffect, RawHTML } = wp.element;
const { Panel, PanelBody, SelectControl, TextControl, ColorPicker, ColorPalette, Button, ResponsiveWrapper } = wp.components;
const { withSelect } = wp.data;
import { useBlockProps } from '@wordpress/block-editor';
import apiFetch from '@wordpress/api-fetch';
 
const BlockEdit = (props) => {
	const { attributes, attributes: {backgroundColor,paletteBackgroundColor, padding}, setAttributes } = props;
	const colors = wp.data.select('core/block-editor').getSettings().colors;

	const removeMedia = () => {
		props.setAttributes({
			mediaId: 0,
			mediaUrl: ''
		});
	}
 
 	const onSelectMedia = (media) => {
		props.setAttributes({
			mediaId: media.id,
			mediaUrl: media.url
		});
	}
 
    const bodyStyle = {
        backgroundColor: attributes.backgroundColor,
        color: attributes.color,
        padding: attributes.padding,
        backgroundImage: attributes.mediaUrl != '' ? 'url("' + attributes.mediaUrl + '")' : 'none',
    };
	
	return (
		<Fragment>
			<InspectorControls>
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
										{props.media != undefined && 
						            			<ResponsiveWrapper
									    		naturalWidth={ props.media.media_details.width }
											naturalHeight={ props.media.media_details.height }
									    	>
									    		<img src={props.media.source_url} />
									    	</ResponsiveWrapper>
						            		}
									</Button>
								)}
							/>
						</MediaUploadCheck>
						{attributes.mediaId != 0 && 
							<MediaUploadCheck>
								<MediaUpload
									title={__('Replace image', 'awp')}
									value={attributes.mediaId}
									onSelect={onSelectMedia}
									allowedTypes={['image']}
									render={({open}) => (
										<Button onClick={open} isDefault isLarge>{__('Replace image', 'awp')}</Button>
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
			<div style={blockStyle}>
				... Your block content here...
			</div>
		</Fragment>
	);
};
 
 
registerBlockType('awp/imageselectinspector', {
	title: 'AWP Imageselect',
	icon: 'smiley',
	category: 'layout',
	supports: {
		align: true
	},
	attributes: {
		mediaId: {
			type: 'number',
			default: 0
		},
		mediaUrl: {
			type: 'string',
			default: ''
		}
	}, 
	edit: withSelect((select, props) => {
		return { media: props.attributes.mediaId ? select('core').getMedia(props.attributes.mediaId) : undefined };
	})(BlockEdit),
	save: (props) => {
		const { attributes } = props;
		const blockStyle = {
			backgroundColor: attributes.backgroundColor,
			color: attributes.color,
			padding: attributes.padding,
		};
		return (
			<div style={blockStyle}>
				... Your block content here...
			</div>
		);
	}
});