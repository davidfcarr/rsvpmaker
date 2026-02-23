/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
const { useBlockProps, InnerBlocks } = wp.blockEditor;
/**
 * The save function defines the way in which the different attributes should
 * be combined into the final markup, which is then serialized by the block
 * editor into `post_content`.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#save
 *
 * @return {WPElement} Element to render.
 */
export default function save(props) {
	const { attributes, className } = props;
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
        const blockProps = useBlockProps.save({className, style: bodyStyle });
        return <div { ...blockProps }><InnerBlocks.Content /></div>;
}
