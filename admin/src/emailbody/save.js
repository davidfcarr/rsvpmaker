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
export default function save({attributes, className}) {
       const bodyStyle = (attributes.mediaUrl != '') ? {
            backgroundColor: attributes.backgroundColor,
            color: attributes.color,
            padding: attributes.padding,
            backgroundImage: attributes.mediaUrl != '' ? 'url("' + attributes.mediaUrl + '")' : 'none',
            backgroundRepeat: attributes.backgroundRepeat,
            backgroundSize: attributes.backgroundSize,
        } :  {
            backgroundColor: attributes.backgroundColor,
            color: attributes.color,
            padding: attributes.padding,
        };
        const blockProps = useBlockProps.save({ className, style: bodyStyle });
        return <div { ...blockProps}><InnerBlocks.Content /></div>;
 }
