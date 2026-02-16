const { __ } = wp.i18n;
const { InnerBlocks, InspectorControls, useBlockProps } = wp.blockEditor;
const { Fragment, useCallback } = wp.element;
const { Component } = wp.element;
const { PanelBody, DateTimePicker, SelectControl } = wp.components;
const { useDispatch } = wp.data;

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
	const { attributes, className, setAttributes, isSelected, clientId } = props;

	const blockProps = useBlockProps({ className });
	const { selectBlock } = useDispatch('core/block-editor');

	const onLabelClick = useCallback( ( e ) => {
		e.preventDefault();
		e.stopPropagation();
		selectBlock( clientId );
	}, [ clientId, selectBlock ] );

	const onLabelKeyDown = useCallback( ( e ) => {
		if ( e.key === 'Enter' || e.key === ' ' ) {
			e.preventDefault();
			selectBlock( clientId );
		}
	}, [ clientId, selectBlock ] );

	return (
		<Fragment>
			<TimeInspector { ...props } />
			<div { ...blockProps }>
					<div className="limited_label limited_border limited_label--start" role="button" tabIndex="0" onClick={ onLabelClick } onKeyDown={ onLabelKeyDown }>
						{ __( 'START Limited time content (click here to set start and end times)' ) }
					</div>
					<div className="limited_inner">
						<InnerBlocks />
					</div>
					<div className="limited_label limited_border limited_label--end" role="button" tabIndex="0" onClick={ onLabelClick } onKeyDown={ onLabelKeyDown }>
						{ __( 'END Limited time content' ) }
					</div>
			</div>
		</Fragment>
		);

}

class TimeInspector extends Component {

	render() {
		
		const { attributes, setAttributes, className } = this.props;
		return (
			<InspectorControls key="inspector">
			<PanelBody title={ __( 'Start Time', 'rsvpmaker' ) } >
					<SelectControl
							label={ __( 'Set Start Time', 'rsvpmaker' ) }
							value={ attributes.start_on }
							onChange={ ( start_on ) => setAttributes( { start_on } ) }
							options={ [
								{ value: 0, label: __( 'No', 'rsvpmaker' ) },
								{ value: 1, label: __( 'Yes', 'rsvpmaker' ) },
							] }
						/>
		
			{(attributes.start_on > 0) && (
			<DateTimePicker
		    is12Hour={ true }
		    currentDate={ attributes.start }
			onChange={ ( start ) => setAttributes( { start })}
		    />									 
			 )}
				</PanelBody>
			<PanelBody title={ __( 'End Time', 'rsvpmaker' ) } >
					<SelectControl
							label={ __( 'Set End Time', 'rsvpmaker' ) }
							value={ attributes.end_on }
							onChange={ ( end_on ) => setAttributes( { end_on } ) }
							options={ [
								{ value: 0, label: __( 'No', 'rsvpmaker' ) },
								{ value: 1, label: __( 'Yes', 'rsvpmaker' ) },
							] }
						/>
			{(attributes.end_on > 0) && (
			 <div id="endtime">
		<DateTimePicker
		    is12Hour={ true }
		    currentDate={ attributes.end }
			onChange={ ( end ) => setAttributes( { end })}
		    />
			</div>
			 )}
			 <SelectControl
				label={ __( 'Delete or Hide Expired Content', 'rsvpmaker' ) }
				value={ attributes.delete_expired }
				onChange={ ( delete_expired ) => setAttributes( { delete_expired } ) }
				options={ [
					{ value: 0, label: __( 'Hide', 'rsvpmaker' ) },
					{ value: 1, label: __( 'Delete', 'rsvpmaker' ) },
				] }
				/>
				</PanelBody>
			</InspectorControls>
		);
	}
}