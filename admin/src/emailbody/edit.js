
const { __ } = wp.i18n;
const { registerBlockType } = wp.blocks;
const { InnerBlocks, InspectorControls, MediaUpload, MediaUploadCheck } = wp.blockEditor;
const { Component, Fragment, useState, useEffect, RawHTML } = wp.element;
const { Panel, PanelBody, SelectControl, TextControl, ColorPicker, ColorPalette, Button, ResponsiveWrapper } = wp.components;
import { useBlockProps } from '@wordpress/block-editor';
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
	const CTEMPLATE = [
	[ 'core/paragraph', { placeholder: __('Email content','rsvpmaker')}],
	];
	const { attributes, className, setAttributes, isSelected, attributes: {backgroundColor, color, padding, backgroundImage, backgroundRepeat, backgroundSize} } = props;

    const bodyStyle = {
        backgroundColor: attributes.backgroundColor,
        color: attributes.color,
        padding: attributes.padding,
        backgroundImage: attributes.mediaUrl != '' ? 'url("' + attributes.mediaUrl + '")' : 'none',
        backgroundRepeat: attributes.backgroundRepeat,
        backgroundSize: attributes.backgroundSize,
        backgroundAttachment: 'fixed',
    };
    const blockProps = useBlockProps({ className, style: bodyStyle});

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
	const colors = wp.data.select('core/block-editor').getSettings().colors;
        
	return (
<div { ...blockProps} >
	<InspectorControls key="emailbodycontrols" >
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
					title={__('Select email background image', 'rsvpmaker')}
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
                    <SelectControl
                                label={__("Background Repeat", "rsvpmaker")}
                                options={[{'label':'None (no-repeat)','value':'no-repeat'},{'label':'Repeat Vertical (repeat-y)','value':'repeat-y'},{'label':'Repeat Horizontal (repeat-x)','value':'repeat-x'},{'label':'Repeat (repeat)','value':'repeat'}]}
                                value={backgroundRepeat}
                                onChange={(backgroundRepeat) => setAttributes({backgroundRepeat: backgroundRepeat})}
                    />
                    <SelectControl
                                label={__("Background Sizing", "rsvpmaker")}
                                options={[{'label':'Contain','value':'contain'},{'label':'Cover','value':'cover'}]}
                                value={backgroundSize}
                                onChange={(backgroundSize) => setAttributes({backgroundSize: backgroundSize})}
                    />
					</PanelBody>
	</InspectorControls>
	<InnerBlocks />
</div>
	);
}
