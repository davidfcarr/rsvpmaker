<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'dcarr_kses_form' ) ) {
	function dcarr_kses_form() {
		return array_merge(
			wp_kses_allowed_html( 'post' ),
			array(
				'style' => array(
					'type'  => true,
					'media' => true,
					'scoped' => true,
				),
				'script' => array(
					'type'  => true,
					'src'   => true,
					'async' => true,
					'defer' => true,
					'integrity' => true,
					'crossorigin' => true,
					'nomodule' => true,
				),
				'form' => array(
					'action'         => true,
					'accept'         => true,
					'accept-charset' => true,
					'autocomplete'   => true,
					'class'          => true,
					'enctype'        => true,
					'form'           => true,
					'id'             => true,
					'method'         => true,
					'name'           => true,
					'novalidate'     => true,
					'rel'            => true,
					'role'           => true,
					'style'          => true,
					'target'         => true,
					'tabindex'       => true,
					'aria-*'         => true,
					'data-*'         => true,
				),
				'input' => array(
					'accept'      => true,
					'alt'         => true,
					'autocomplete'=> true,
					'autofocus'   => true,
					'checked'     => true,
					'class'       => true,
					'data-*'      => true,
					'disabled'    => true,
					'form'        => true,
					'id'          => true,
					'list'        => true,
					'max'         => true,
					'maxlength'   => true,
					'min'         => true,
					'minlength'   => true,
					'multiple'    => true,
					'name'        => true,
					'pattern'     => true,
					'placeholder' => true,
					'readonly'    => true,
					'required'    => true,
					'role'        => true,
					'size'        => true,
					'src'         => true,
					'step'        => true,
					'style'       => true,
					'tabindex'    => true,
					'title'       => true,
					'type'        => true,
					'value'       => true,
					'aria-*'      => true,
				),
				'select' => array(
					'autocomplete' => true,
					'autofocus'   => true,
					'class'       => true,
					'data-*'      => true,
					'disabled'    => true,
					'form'        => true,
					'id'          => true,
					'multiple'    => true,
					'name'        => true,
					'required'    => true,
					'size'        => true,
					'style'       => true,
					'tabindex'    => true,
					'title'       => true,
					'aria-*'      => true,
				),
				'option' => array(
					'data-*'   => true,
					'disabled' => true,
					'label'    => true,
					'selected' => true,
					'title'    => true,
					'value'    => true,
					'aria-*'   => true,
				),
				'optgroup' => array(
					'data-*'   => true,
					'disabled' => true,
					'label'    => true,
					'aria-*'   => true,
				),
				'textarea' => array(
					'autocomplete' => true,
					'autofocus'   => true,
					'class'       => true,
					'cols'        => true,
					'data-*'      => true,
					'disabled'    => true,
					'form'        => true,
					'id'          => true,
					'maxlength'   => true,
					'minlength'   => true,
					'name'        => true,
					'placeholder' => true,
					'readonly'    => true,
					'required'    => true,
					'rows'        => true,
					'style'       => true,
					'tabindex'    => true,
					'title'       => true,
					'wrap'        => true,
					'aria-*'      => true,
				),
				'button' => array(
					'autofocus' => true,
					'class'     => true,
					'data-*'    => true,
					'disabled'  => true,
					'form'      => true,
					'id'        => true,
					'name'      => true,
					'role'      => true,
					'style'     => true,
					'tabindex'  => true,
					'title'     => true,
					'type'      => true,
					'value'     => true,
					'aria-*'    => true,
				),
				'label' => array(
					'class' => true,
					'for'   => true,
					'form'  => true,
					'id'    => true,
					'style' => true,
					'tabindex' => true,
					'aria-*'   => true,
					'data-*'   => true,
				),
				'fieldset' => array(
					'class'    => true,
					'disabled' => true,
					'form'     => true,
					'id'       => true,
					'name'     => true,
					'style'    => true,
					'aria-*'   => true,
					'data-*'   => true,
				),
				'legend' => array(
					'accesskey' => true,
					'class'     => true,
					'id'        => true,
					'style'     => true,
					'aria-*'    => true,
					'data-*'    => true,
				),
				'datalist' => array(
					'class' => true,
					'id'    => true,
					'aria-*' => true,
					'data-*' => true,
				),
				'output' => array(
					'class' => true,
					'for'   => true,
					'form'  => true,
					'id'    => true,
					'name'  => true,
					'style' => true,
					'aria-*' => true,
					'data-*' => true,
				),
				'progress' => array(
					'class' => true,
					'id'    => true,
					'max'   => true,
					'value' => true,
					'style' => true,
					'aria-*' => true,
					'data-*' => true,
				),
				'meter' => array(
					'class'   => true,
					'high'    => true,
					'id'      => true,
					'low'     => true,
					'max'     => true,
					'min'     => true,
					'optimum' => true,
					'value'   => true,
					'style'   => true,
					'aria-*'  => true,
					'data-*'  => true,
				),
			)
		);
	}
}

if ( ! function_exists( 'dcarr_kses_with_inline_script' ) ) {
	/**
	 * Sanitize HTML while preserving inline script content.
	 * 
	 * This function allows inline JavaScript inside <script></script> tags
	 * while still using wp_kses to sanitize the rest of the HTML content.
	 *
	 * @param string $content HTML content to sanitize
	 * @return string Sanitized HTML with inline scripts preserved
	 */
	function dcarr_kses_with_inline_script( $content ) {
		// Extract inline scripts with a placeholder pattern
		$script_pattern = '/<script\s*([^>]*)>(.*?)<\/script>/is';
		$scripts = array();
		$counter = 0;
		
		$content = preg_replace_callback(
			$script_pattern,
			function( $matches ) use ( &$scripts, &$counter ) {
				$attrs = $matches[1];
				$script_content = $matches[2];
				$placeholder = '___DCARR_SCRIPT_' . $counter . '___';
				$scripts[ $placeholder ] = array(
					'attrs' => $attrs,
					'content' => $script_content,
				);
				$counter++;
				return '<script' . $attrs . '>' . $placeholder . '</script>';
			},
			$content
		);
		
		// Apply standard KSES sanitization
		$sanitized = wp_kses( $content, dcarr_kses_form() );
		
		// Restore script contents from placeholders
		foreach ( $scripts as $placeholder => $script_data ) {
			$sanitized = str_replace( $placeholder, $script_data['content'], $sanitized );
		}
		
		return $sanitized;
	}
}
