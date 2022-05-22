/**
 * BLOCK: limited time
 *
 */

const { __ } = wp.i18n;
const { registerBlockType } = wp.blocks;
const { InnerBlocks, RichText } = wp.blockEditor;
const { Fragment } = wp.element;
const { BlockControls } = wp.editor;
const { Component } = wp.element;
const { InspectorControls } = wp.blockEditor;
const { PanelBody, SelectControl, TextControl } = wp.components;
import { useBlockProps } from '@wordpress/block-editor';

const BTEMPLATE = [
	[ 'rsvpmaker/emailcontent', {}],
];

const CTEMPLATE = [
	[ 'core/paragraph', {}],
];

registerBlockType( 'rsvpmaker/emailbody', {
	title: ( 'RSVP Email Body Wrapper' ), // Block title.
	icon: 'admin-comments', 
	category: 'layout',
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
},

    edit: function( props ) {	

	const { attributes, className, setAttributes, isSelected } = props;
    const bodyStyle = {
        backgroundColor: attributes.backgroundColor,
        color: attributes.color,
        padding: attributes.padding,
    };
    const blockProps = useBlockProps( { style: bodyStyle } );

	return (
		<Fragment>
		<EmailBodyInspector { ...props } />
<div className={className}  { ...blockProps} >
	<InnerBlocks />
</div>
		</Fragment>
		);
    },
    save: function( { attributes, className } ) {
        const bodyStyle = {
            backgroundColor: attributes.backgroundColor,
            color: attributes.color,
            padding: attributes.padding,
        };
        const blockProps = useBlockProps.save({ style: bodyStyle });
        return <div className={className} { ...blockProps}><InnerBlocks.Content /></div>;
    }
});

class EmailBodyInspector extends Component {

	render() {
		
		const { attributes: {backgroundColor ,color, padding}, setAttributes, className } = this.props;
		return (
			<InspectorControls key="inspector">
			<PanelBody title={ __( 'Style', 'rsvpmaker' ) } >
            <TextControl
            label="Background Color"
            value={ backgroundColor }
            onChange={ ( backgroundColor ) => { setAttributes( { backgroundColor: backgroundColor } ) } }
            />
				</PanelBody>
			</InspectorControls>
		);
	}
}

registerBlockType( 'rsvpmaker/emailcontent', {
	title: ( 'RSVP Email Content Wrapper' ), // Block title.
	icon: 'admin-comments', 
	category: 'layout',
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
    <InnerBlocks />
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
        return <div className={className} { ...blockProps }><InnerBlocks.Content /></div>;
    }
});

class EmailContentInspector extends Component {

	render() {
		
		const { attributes: {backgroundColor ,color, padding, marginLeft, marginRight, maxWidth, border}, setAttributes, className } = this.props;
		return (
			<InspectorControls key="inspector">
			<PanelBody title={ __( 'Style', 'rsvpmaker' ) } >
            <TextControl
            label="Background Color"
            value={ backgroundColor }
            onChange={ ( backgroundColor ) => { setAttributes( { backgroundColor: backgroundColor } ) } }
            />
            <TextControl
            label="Text Color"
            value={ color }
            onChange={ ( color ) => { setAttributes( { color: color } ) } }
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