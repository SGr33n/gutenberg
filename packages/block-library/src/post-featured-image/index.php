<?php
/**
 * Server-side rendering of the `core/post-featured-image` block.
 *
 * @package WordPress
 */

/**
 * Renders the `core/post-featured-image` block on the server.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 * @return string Returns the featured image for the current post.
 */
function render_block_core_post_featured_image( $attributes, $content, $block ) {
	if ( ! isset( $block->context['postId'] ) ) {
		return '';
	}
	$post_ID = $block->context['postId'];

	$is_link        = isset( $attributes['isLink'] ) && $attributes['isLink'];
	$size_slug      = isset( $attributes['sizeSlug'] ) ? $attributes['sizeSlug'] : 'post-thumbnail';
	$post_title     = trim( strip_tags( get_the_title( $post_ID ) ) );
	$attr           = get_block_core_post_featured_image_border_attributes( $attributes );
	$overlay_markup = get_block_core_post_featured_image_overlay_element_markup( $attributes );

	if ( $is_link ) {
		$attr['alt'] = $post_title;
	}

	$featured_image = get_the_post_thumbnail( $post_ID, $size_slug, $attr );
	if ( ! $featured_image ) {
		return '';
	}
	$wrapper_attributes = get_block_wrapper_attributes();
	if ( $is_link ) {
		$link_target    = $attributes['linkTarget'];
		$rel            = ! empty( $attributes['rel'] ) ? 'rel="' . esc_attr( $attributes['rel'] ) . '"' : '';
		$featured_image = sprintf(
			'<a href="%1$s" target="%2$s" %3$s>%4$s%5$s</a>',
			get_the_permalink( $post_ID ),
			esc_attr( $link_target ),
			$rel,
			$featured_image,
			$overlay_markup
		);
	} else {
		$featured_image = $featured_image . $overlay_markup;
	}

	$has_width  = ! empty( $attributes['width'] );
	$has_height = ! empty( $attributes['height'] );
	if ( ! $has_height && ! $has_width ) {
		return "<figure {$wrapper_attributes}>{$featured_image}</figure>";
	}

	if ( $has_width ) {
		$wrapper_attributes = get_block_wrapper_attributes( array( 'style' => "width:{$attributes['width']};" ) );
	}

	if ( $has_height ) {
		$image_styles = "height:{$attributes['height']};";
		if ( ! empty( $attributes['scale'] ) ) {
			$image_styles .= "object-fit:{$attributes['scale']};";
		}
		$featured_image = str_replace( 'src=', 'style="' . esc_attr( $image_styles ) . '" src=', $featured_image );
	}

	return "<figure {$wrapper_attributes}>{$featured_image}</figure>";
}

/**
 * Generate markup for the HTML element that will be used for the overlay.
 *
 * @param array $attributes Block attributes.
 *
 * @return string HTML markup in string format.
 */
function get_block_core_post_featured_image_overlay_element_markup( $attributes ) {
	$has_dim_background  = isset( $attributes['dimRatio'] ) && $attributes['dimRatio'];
	$has_gradient        = isset( $attributes['gradient'] ) && $attributes['gradient'];
	$has_custom_gradient = isset( $attributes['customGradient'] ) && $attributes['customGradient'];
	$has_solid_overlay   = isset( $attributes['overlayColor'] ) && $attributes['overlayColor'];
	$has_custom_overlay  = isset( $attributes['customOverlayColor'] ) && $attributes['customOverlayColor'];
	$class_names         = array(
		'wp-block-post-featured-image__overlay',
	);
	$styles_properties   = array();

	if ( ! $has_dim_background ) {
		return '';
	}

	// Generate required classes for the element.
	if ( $has_dim_background ) {
		$class_names[] = 'has-background-dim';
		$class_names[] = "has-background-dim-{$attributes['dimRatio']}";
	}

	if ( $has_solid_overlay ) {
		$class_names[] = "has-{$attributes['overlayColor']}-background-color";
	}

	if ( $has_gradient || $has_custom_gradient ) {
		$class_names[] = 'has-background-gradient';
	}

	if ( $has_gradient ) {
		$class_names[] = "has-{$attributes['gradient']}-gradient-background";
	}

	// Generate required CSS properties and their values.
	if ( ! empty( $attributes['style']['border']['radius'] ) ) {
		$styles_properties['border-radius'] = $attributes['style']['border']['radius'];
	}

	if ( ! empty( $attributes['style']['border']['width'] ) ) {
		$styles_properties['border-width'] = $attributes['style']['border']['width'];
	}

	if ( $has_custom_gradient ) {
		$styles_properties['background-image'] = $attributes['customGradient'];
	}

	if ( $has_custom_overlay ) {
		$styles_properties['background-color'] = $attributes['customOverlayColor'];
	}

	$styles = '';

	foreach ( $styles_properties as $style_attribute => $style_attribute_value ) {
		$styles .= "{$style_attribute}: $style_attribute_value; ";
	}

	return sprintf(
		'<span class="%s" style="%s" aria-hidden="true"></span>',
		esc_attr( implode( ' ', $class_names ) ),
		esc_attr( trim( $styles ) )
	);

}

/**
 * Generates class names and styles to apply the border support styles for
 * the Post Featured Image block.
 *
 * @param array $attributes The block attributes.
 * @return array The border-related classnames and styles for the block.
 */
function get_block_core_post_featured_image_border_attributes( $attributes ) {
	$border_styles = array();
	$sides         = array( 'top', 'right', 'bottom', 'left' );

	// Border radius.
	if ( isset( $attributes['style']['border']['radius'] ) ) {
		$border_styles['radius'] = $attributes['style']['border']['radius'];
	}

	// Border style.
	if ( isset( $attributes['style']['border']['style'] ) ) {
		$border_styles['style'] = $attributes['style']['border']['style'];
	}

	// Border width.
	if ( isset( $attributes['style']['border']['width'] ) ) {
		$border_styles['width'] = $attributes['style']['border']['width'];
	}

	// Border color.
	$preset_color           = array_key_exists( 'borderColor', $attributes ) ? "var:preset|color|{$attributes['borderColor']}" : null;
	$custom_color           = _wp_array_get( $attributes, array( 'style', 'border', 'color' ), null );
	$border_styles['color'] = $preset_color ? $preset_color : $custom_color;

	// Individual border styles e.g. top, left etc.
	foreach ( $sides as $side ) {
		$border                 = _wp_array_get( $attributes, array( 'style', 'border', $side ), null );
		$border_styles[ $side ] = array(
			'color' => isset( $border['color'] ) ? $border['color'] : null,
			'style' => isset( $border['style'] ) ? $border['style'] : null,
			'width' => isset( $border['width'] ) ? $border['width'] : null,
		);
	}

	$styles     = gutenberg_style_engine_get_styles( array( 'border' => $border_styles ) );
	$attributes = array();
	if ( ! empty( $styles['classnames'] ) ) {
		$attributes['class'] = $styles['classnames'];
	}
	if ( ! empty( $styles['css'] ) ) {
		$attributes['style'] = $styles['css'];
	}
	return $attributes;
}

/**
 * Registers the `core/post-featured-image` block on the server.
 */
function register_block_core_post_featured_image() {
	register_block_type_from_metadata(
		__DIR__ . '/post-featured-image',
		array(
			'render_callback' => 'render_block_core_post_featured_image',
		)
	);
}
add_action( 'init', 'register_block_core_post_featured_image' );
