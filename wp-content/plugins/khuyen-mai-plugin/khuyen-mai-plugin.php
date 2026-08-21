<?php
/**
 * Plugin Name: Wisp Bookstore Flash Deal Section
 * Description: Flash Sale Carousel + Countdown tùy chỉnh qua Dashboard Admin.
 * Version: 2.0
 * Author: Wisp Bookstore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/* ===============================
   1. ĐĂNG KÝ SCRIPTS & CSS
================================ */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_script( 'jquery' );

	if ( is_cart() || is_checkout() ) {
		wp_enqueue_script( 'jquery-blockui' );
	}

	wp_enqueue_style( 'slick-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css' );
	wp_enqueue_style( 'slick-theme', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css' );
	wp_enqueue_script( 'slick-js', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', array( 'jquery' ), '1.8.1', true );
});

/* ===============================
   2. DASHBOARD QUẢN TRỊ (ADMIN MENU)
================================ */
add_action( 'admin_menu', function () {
	add_menu_page(
		'Wisp Flash Deal',
		'Wisp Flash Deal',
		'manage_options',
		'wisp-flash-deal',
		'wisp_flash_deal_admin_page',
		'dashicons-tickets-alt',
		25
	);
});

// Đăng ký cài đặt (Settings)
add_action( 'admin_init', function () {
	register_setting( 'wisp_flash_deal_group', 'wisp_fd_title' );
	register_setting( 'wisp_flash_deal_group', 'wisp_fd_end_time' );
	register_setting( 'wisp_flash_deal_group', 'wisp_fd_limit' );
	register_setting( 'wisp_flash_deal_group', 'wisp_fd_main_color' );
});

// Giao diện Dashboard Admin
function wisp_flash_deal_admin_page() {
	?>
	<div class="wrap">
		<h1>⚡ Cấu Hình Wisp Flash Deal</h1>
		<form method="post" action="options.php" style="background: #fff; padding: 20px; border-radius: 8px; max-width: 600px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-top: 20px;">
			<?php
			settings_fields( 'wisp_flash_deal_group' );
			do_settings_sections( 'wisp_flash_deal_group' );

			$title      = get_option( 'wisp_fd_title', '⚡ Flash Deals ⚡' );
			$end_time   = get_option( 'wisp_fd_end_time', date( 'Y-m-d\T23:59', strtotime( 'today' ) ) );
			$limit      = get_option( 'wisp_fd_limit', 10 );
			$main_color = get_option( 'wisp_fd_main_color', '#ff8a3d' );
			?>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="wisp_fd_title">Tiêu đề Flash Deal</label></th>
					<td><input type="text" id="wisp_fd_title" name="wisp_fd_title" value="<?php echo esc_attr( $title ); ?>" class="regular-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="wisp_fd_end_time">Thời gian kết thúc</label></th>
					<td>
						<input type="datetime-local" id="wisp_fd_end_time" name="wisp_fd_end_time" value="<?php echo esc_attr( $end_time ); ?>" class="regular-text" required />
						<p class="description">Chọn thời điểm kết thúc đếm ngược Flash Sale.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wisp_fd_limit">Số lượng sản phẩm</label></th>
					<td><input type="number" id="wisp_fd_limit" name="wisp_fd_limit" value="<?php echo esc_attr( $limit ); ?>" class="small-text" min="1" max="50" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="wisp_fd_main_color">Màu chủ đạo</label></th>
					<td><input type="color" id="wisp_fd_main_color" name="wisp_fd_main_color" value="<?php echo esc_attr( $main_color ); ?>" /></td>
				</tr>
			</table>
			<?php submit_button( 'Lưu thay đổi' ); ?>
		</form>
	</div>
	<?php
}

/* ===============================
   3. CSS & JS CUSTOM (TRONG HEAD)
================================ */
add_action( 'wp_head', function () {
	$main_color = get_option( 'wisp_fd_main_color', '#ff8a3d' );
	$end_time   = get_option( 'wisp_fd_end_time', '' );
	?>
<style>
	:root { --hasaki-orange: <?php echo esc_html( $main_color ); ?>; }
	.hasaki-container{ background:var(--hasaki-orange); padding:30px 20px; border-radius:10px; max-width:1200px; margin:20px auto; font-family:Arial,sans-serif; }
	.deal-header{ text-align: center; color:#fff; margin-bottom:25px; }
	.deal-title-wrapper{ display: flex; flex-direction: column; align-items: center; gap: 10px; }
	.deal-title{ font-size:32px; font-weight:bold; margin:0; text-transform: uppercase; letter-spacing: 1px; text-shadow: 2px 2px 4px rgba(0,0,0,0.2); }
	.countdown-timer{ display:flex; gap:8px; justify-content: center; align-items: center; color: #fff; font-weight: bold; }
	.countdown-label{ font-size: 16px; text-transform: none; opacity: 0.9; }
	.time-box{ background:#333; padding:5px 8px; border-radius:4px; min-width:30px; text-align:center; color: #fff; font-size: 16px; }
	.view-all-wrapper { text-align: center; margin-top: 25px; }
	.view-all{ color: var(--hasaki-orange); background: #fff; padding: 10px 30px; border-radius: 25px; text-decoration: none; font-size: 16px; font-weight: bold; display: inline-block; transition: .3s; }
	.product-carousel .product-card { margin: 0 8px; }
	.product-grid{ display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:12px; }
	.product-card{ background:#fff; border-radius:8px; padding:10px; text-align:center; position:relative; transition:.3s; height: 100%; }
	.product-card img { max-width: 100%; height: auto; margin-bottom: 10px; }
	.discount-badge{ position:absolute; top:8px; right:8px; background:#ff4d4d; color:#fff; font-size:12px; padding:2px 6px; border-radius:3px; font-weight:bold; z-index: 2; }
	.product-name{ font-size:14px; color:#333; text-align:left; height:40px; overflow:hidden; margin: 10px 0; }
	.price-now{ color:var(--hasaki-orange); font-size:18px; font-weight:bold; margin:0; }
	.price-old{ font-size:13px; text-decoration:line-through; color:#999; margin:0; }
	.slick-prev, .slick-next { z-index: 10; width: 30px; height: 30px; }
	.slick-prev { left: -10px; }
	.slick-next { right: -10px; }
	.slick-prev:before, .slick-next:before { font-size: 30px; color: #fff; }
	.flash-cat-list{ display:flex; gap:10px; flex-wrap:wrap; margin-bottom:20px; }
	.flash-cat-list a{ background:#fff; color:var(--hasaki-orange); padding:6px 14px; border-radius:20px; border:1px solid var(--hasaki-orange); text-decoration:none; font-size:14px; transition:.3s; }
	.flash-cat-list a.active, .flash-cat-list a:hover{ background:var(--hasaki-orange); color:#fff; }
	.deal-header-inline { display: flex; align-items: center; gap: 25px; margin-bottom: 20px; }
	@media (max-width: 600px) { .deal-header-inline { flex-direction: column; align-items: center; gap: 10px; } }
</style>

<script>
jQuery(document).ready(function($){
	if ($('.product-carousel').length > 0) {
		$('.product-carousel').slick({
			dots: false, infinite: true, speed: 300, slidesToShow: 5, slidesToScroll: 1,
			prevArrow: '<button type="button" class="slick-prev">〈</button>',
			nextArrow: '<button type="button" class="slick-next">〉</button>',
			responsive: [{ breakpoint: 1024, settings: { slidesToShow: 3 } }, { breakpoint: 600, settings: { slidesToShow: 2 } }]
		});
	}

	function updateTimer() {
		const endTimeStr = "<?php echo esc_js( $end_time ); ?>";
		let targetDate;

		if (endTimeStr) {
			targetDate = new Date(endTimeStr);
		} else {
			const now = new Date();
			targetDate = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59, 59);
		}

		const diff = targetDate - new Date();
		if (diff <= 0) { 
			$('.time-box.h, .time-box.m, .time-box.s').text('00'); 
			return; 
		}

		const hh = Math.floor(diff / (1000 * 60 * 60)).toString().padStart(2, '0');
		const mm = Math.floor((diff / (1000 * 60)) % 60).toString().padStart(2, '0');
		const ss = Math.floor((diff / 1000) % 60).toString().padStart(2, '0');

		$('.time-box.h').text(hh); 
		$('.time-box.m').text(mm); 
		$('.time-box.s').text(ss);
	}

	setInterval(updateTimer, 1000);
	updateTimer();
});
</script>
	<?php
});

/* ===============================
   4. SHORTCODE FLASH SALE (TRANG CHỦ)
================================ */
add_shortcode( 'hasaki_flash_sale', function () {
	$title = get_option( 'wisp_fd_title', '⚡ Flash Deals ⚡' );
	$limit = get_option( 'wisp_fd_limit', 10 );

	$args = array(
		'post_type'      => 'product',
		'posts_per_page' => $limit,
		'meta_query'     => array(
			array(
				'key'     => '_sale_price',
				'value'   => 0,
				'compare' => '>',
				'type'    => 'NUMERIC',
			),
		),
	);

	$q = new WP_Query( $args );
	ob_start();
	?>
	<div class="hasaki-container">
		<div class="deal-header">
			<div class="deal-title-wrapper">
				<h2 class="deal-title"><?php echo esc_html( $title ); ?></h2>
				<div class="countdown-timer">
					<span class="countdown-label">Kết thúc sau</span>
					<span class="time-box h">00</span> : <span class="time-box m">00</span> : <span class="time-box s">00</span>
				</div>
			</div>
		</div>
		<div class="product-carousel">
			<?php
			while ( $q->have_posts() ) :
				$q->the_post();
				global $product;
				$r = $product->get_regular_price();
				$s = $product->get_sale_price();
				$p = $r ? round( ( ( $r - $s ) / $r ) * 100 ) : 0;
				?>
			<div>
				<div class="product-card">
					<?php if ( $p > 0 ) : ?><div class="discount-badge">-<?php echo $p; ?>%</div><?php endif; ?>
					<a href="<?php the_permalink(); ?>"><?php echo $product->get_image(); ?></a>
					<p class="price-now"><?php echo number_format( $s, 0, ',', '.' ); ?> ₫</p>
					<p class="price-old"><?php echo number_format( $r, 0, ',', '.' ); ?> ₫</p>
					<h3 class="product-name"><?php the_title(); ?></h3>
				</div>
			</div>
			<?php endwhile; wp_reset_postdata(); ?>
		</div>
		<div class="view-all-wrapper">
			<a href="<?php echo site_url( '/khuyen-mai' ); ?>" class="view-all">Xem tất cả khuyến mãi</a>
		</div>
	</div>
	<?php
	return ob_get_clean();
});

/* ===============================
   5. SHORTCODE TRANG KHUYẾN MÃI (ALL)
================================ */
add_shortcode( 'hasaki_flash_sale_all', function () {
	$paged       = max( 1, get_query_var( 'paged' ) );
	$current_cat = isset( $_GET['cat'] ) ? sanitize_text_field( $_GET['cat'] ) : '';
	$tax_query   = $current_cat ? array( array( 'taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => $current_cat ) ) : array();

	$args = array(
		'post_type'      => 'product',
		'posts_per_page' => 12,
		'paged'          => $paged,
		'tax_query'      => $tax_query,
		'meta_query'     => array(
			array(
				'key'     => '_sale_price',
				'value'   => 0,
				'compare' => '>',
				'type'    => 'NUMERIC',
			),
		),
	);

	$q    = new WP_Query( $args );
	$cats = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true ) );
	ob_start();
	?>
	<div class="hasaki-container">
		<div class="deal-header-inline">
			<h2 class="deal-title" style="color:white;">🔥 KHUYẾN MÃI</h2>
			<div class="countdown-timer">
				<span class="countdown-label">Kết thúc sau</span>
				<span class="time-box h">00</span> : <span class="time-box m">00</span> : <span class="time-box s">00</span>
			</div>
		</div>
		<div class="flash-cat-list">
			<a href="<?php echo site_url( '/khuyen-mai' ); ?>" class="<?php echo ! $current_cat ? 'active' : ''; ?>">Tất cả</a>
			<?php foreach ( $cats as $c ) : ?>
			<a href="<?php echo site_url( '/khuyen-mai/?cat=' . $c->slug ); ?>" class="<?php echo $current_cat == $c->slug ? 'active' : ''; ?>"><?php echo esc_html( $c->name ); ?></a>
			<?php endforeach; ?>
		</div>
		<div class="product-grid">
			<?php
			while ( $q->have_posts() ) :
				$q->the_post();
				global $product;
				$r = $product->get_regular_price();
				$s = $product->get_sale_price();
				$p = $r ? round( ( ( $r - $s ) / $r ) * 100 ) : 0;
				?>
			<div class="product-card">
				<?php if ( $p > 0 ) : ?><div class="discount-badge">-<?php echo $p; ?>%</div><?php endif; ?>
				<a href="<?php the_permalink(); ?>"><?php echo $product->get_image(); ?></a>
				<p class="price-now"><?php echo number_format( $s, 0, ',', '.' ); ?> ₫</p>
				<p class="price-old"><?php echo number_format( $r, 0, ',', '.' ); ?> ₫</p>
				<h3 class="product-name"><?php the_title(); ?></h3>
			</div>
			<?php endwhile; wp_reset_postdata(); ?>
		</div>
		<div style="text-align:center;margin-top:20px;"><?php echo paginate_links( array( 'total' => $q->max_num_pages ) ); ?></div>
	</div>
	<?php
	return ob_get_clean();
});

/* ===============================
   6. SHORTCODE ĐẾM NGƯỢC RỜI (CÓ THỂ CHÈN MỌI NƠI)
================================ */
add_shortcode( 'wisp_flash_countdown', function () {
	ob_start();
	?>
	<div class="countdown-timer" style="background: var(--hasaki-orange); padding: 10px; border-radius: 6px; display: inline-flex;">
		<span class="countdown-label" style="margin-right: 8px;">Kết thúc sau</span>
		<span class="time-box h">00</span> : <span class="time-box m">00</span> : <span class="time-box s">00</span>
	</div>
	<?php
	return ob_get_clean();
});