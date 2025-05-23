<?php
/**
 * Single Product tabs
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/tabs/tabs.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @var array $args This is an array of data to display this template.
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filter tabs and allow third parties to add their own.
 *
 * Each tab is an array containing title, callback and priority.
 *
 * @see woocommerce_default_product_tabs()
 */
$product_tabs = apply_filters( 'woocommerce_product_tabs', array() );

woodmart_enqueue_inline_style( 'woo-single-prod-el-tabs-opt-layout-side-hidden' );
woodmart_enqueue_js_script( 'single-product-tabs-side-hidden' );
?>
<?php if ( ! empty( $product_tabs ) ) : ?>
	<?php foreach ( $product_tabs as $key => $product_tab ) : ?>
		<?php
		$title_wrapper_classes  = ' tab-title-' . $key;
		$title_wrapper_classes .= $args['builder_title_classes'];
		$tab_title              = apply_filters( 'woocommerce_product_' . $key . '_tab_title', $product_tab['title'], $key );	
		$content_classes        = ' woocommerce-Tabs-panel--' . $key;

		if ( ! empty( $args['builder_content_classes'] ) ) {
			$content_classes .= $args['builder_content_classes'];
		}

		// Builder classes.
		if ( 'woocommerce_product_additional_information_tab' === $product_tab['callback'] ) {
			$content_classes .= ' wd-single-attrs';
			$content_classes .= $args['builder_additional_info_classes'];
		}

		if ( 'comments_template' === $product_tab['callback'] ) {
			$content_classes .= ' wd-single-reviews';
			$content_classes .= $args['builder_reviews_classes'];
		}

		$tab_title = apply_filters( 'woocommerce_product_' . $key . '_tab_title', $product_tab['title'], $key );
		$popup_id  = 'popup-' . $key;
		?>

		<div class="wd-hidden-tab-title <?php echo esc_attr( $title_wrapper_classes ); ?>" data-tab-key="<?php echo esc_attr( $key ); ?>" data-mfp-src="#<?php echo esc_attr( $popup_id ); ?>">
			<span>
				<?php echo wp_kses_post( $tab_title ); ?>
			</span>
		</div>

		<div id="<?php echo esc_attr( $popup_id ); ?>" class="wd-side-hidden wd-scroll<?php echo esc_attr( $content_classes ); ?>">
			<div class="wd-heading">
				<span class="title">
					<?php echo wp_kses_post( $tab_title ); ?>
				</span>
				<div class="close-side-hidden wd-action-btn wd-style-text wd-cross-icon">
					<a href="#" rel="nofollow">Close</a>
				</div>
			</div>
			<div class="woocommerce-Tabs-panel panel entry-content wc-tab wd-scroll-content" id="tab-<?php echo esc_attr( $key ); ?>" role="tabpanel" aria-labelledby="tab-title-<?php echo esc_attr( $key ); ?>">
				<?php if ( isset( $product_tab['callback'] ) ) : ?>
					<?php call_user_func( $product_tab['callback'], $key, $product_tab ); ?>
				<?php endif; ?>
			</div>
		</div>
	<?php endforeach; ?>
<?php endif; ?>
