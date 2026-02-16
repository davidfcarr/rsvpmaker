/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
const { useBlockProps } = wp.blockEditor;
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
	const { attributes: { label, checked }, className } = props;
	let slug = 'email_list_ok';
	let profilename = 'profile['+slug+']';
	const blockProps = useBlockProps.save({ className });
		// server render
			return (
			<div { ...blockProps }>
<p><input className={slug} type="checkbox" name={profilename} id={slug} value="1" checked={checked} /> {label}</p>
			</div>
			);
}
