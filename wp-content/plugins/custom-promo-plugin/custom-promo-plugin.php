<?php
/*
Plugin Name: Flash Sale WooCommerce PRO (Fix Lỗi Định Dạng Thời Gian)
Description: Quản lý và hiển thị Flash Sale chuẩn xác theo thời gian cấu hình ở Dashboard, dùng shortcode [flash_sale_pro].
Version: 3.7
Author: Nhu Y
*/

if ( ! defined('ABSPATH') ) exit;

// ==========================================
// 1. TẠO MENU PHÍA DASHBOARD
// ==========================================
add_action('admin_menu', 'fs_add_admin_menu');
function fs_add_admin_menu() {
    add_menu_page(
        'Quản Lý Flash Sale',
        'Flash Sale',
        'manage_options',
        'flash-sale-settings',
        'fs_settings_page_html',
        'dashicons-superhero',
        30
    );
}

add_action('admin_init', 'fs_register_settings');
function fs_register_settings() {
    register_setting('fs_options_group', 'fs_title');
    register_setting('fs_options_group', 'fs_start_time');
    register_setting('fs_options_group', 'fs_end_time');
}

function fs_settings_page_html() {
    ?>
    <div class="wrap">
        <h1>Cấu Hình Flash Sale</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('fs_options_group');
            do_settings_sections('fs_options_group');

            $fs_title      = get_option('fs_title', '🔥 FLASH SALE');
            $fs_start_time = get_option('fs_start_time', current_time('Y-m-d\TH:i'));
            $fs_end_time   = get_option('fs_end_time', current_time('Y-m-d\TH:i'));
            ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="fs_title">Tiêu đề Flash Sale</label></th>
                    <td>
                        <input type="text" id="fs_title" name="fs_title" value="<?php echo esc_attr($fs_title); ?>" class="regular-text" />
                        <p class="description">Ví dụ: 🔥 FLASH SALE GIỜ VÀNG</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="fs_start_time">Thời gian bắt đầu</label></th>
                    <td>
                        <input type="datetime-local" id="fs_start_time" name="fs_start_time" value="<?php echo esc_attr($fs_start_time); ?>" style="max-width: 100%;" />
                        <p class="description">Chọn thời điểm bắt đầu hiển thị chương trình.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="fs_end_time">Thời gian kết thúc</label></th>
                    <td>
                        <input type="datetime-local" id="fs_end_time" name="fs_end_time" value="<?php echo esc_attr($fs_end_time); ?>" style="max-width: 100%;" />
                        <p class="description">Chọn thời điểm kết thúc chương trình.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Lưu Cấu Hình'); ?>
        </form>

        <div style="background: #fff; padding: 15px; border-left: 4px solid #f4511e; margin-top: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.1);">
            <h3 style="margin-top: 0; color: #d84315;">📌 Hướng dẫn sử dụng Shortcode:</h3>
            <p>Dùng Shortcode này chèn vào trang chủ hoặc bài viết của bạn:</p>
            <p><code style="background: #f1f1f1; padding: 5px 10px; font-size: 14px; font-weight: bold; color: #333;">[flash_sale_pro]</code></p>
        </div>
    </div>
    <?php
}

// ==========================================
// 2. SHORTCODE [flash_sale_pro]
// ==========================================
add_shortcode('flash_sale_pro', 'fs_new_shortcode_handler');

function fs_new_shortcode_handler(){
    ob_start();

    $saved_start_time = get_option('fs_start_time', '');
    $saved_end_time   = get_option('fs_end_time', '');

    if ( empty($saved_start_time) || empty($saved_end_time) ) {
        return '<p style="text-align:center; color:red;">Vui lòng cấu hình đầy đủ thời gian Flash Sale trong Dashboard!</p>';
    }

    // Xử lý chuẩn hóa định dạng chuỗi thời gian để hàm strtotime đọc chính xác tuyệt đối trên mọi máy chủ
    $formatted_start = str_replace('T', ' ', $saved_start_time);
    $formatted_end   = str_replace('T', ' ', $saved_end_time);

    $start_timestamp   = strtotime($formatted_start);
    $end_timestamp     = strtotime($formatted_end);
    $current_timestamp = current_time('timestamp');

    // Kiểm tra trạng thái thời gian thực tế
    if ( $current_timestamp < $start_timestamp ) {
        return '<p style="text-align:center; font-weight:bold; color:#d84315; padding: 20px;">Chương trình Flash Sale chưa bắt đầu!</p>';
    }

    if ( $current_timestamp > $end_timestamp ) {
        return '<p style="text-align:center; font-weight:bold; color:#999; padding: 20px;">Chương trình Flash Sale đã kết thúc!</p>';
    }

    $fs_title = get_option('fs_title', '🔥 FLASH SALE');

    $args = [
        'post_type'      => 'product',
        'posts_per_page' => 4,
        'orderby'        => 'rand',
        'post__in'       => wc_get_product_ids_on_sale(),
    ];

    $loop = new WP_Query($args);

    if ($loop->have_posts()) :
?>

<section class="flash-sale-wrap">
    <div class="flash-head">

        <h2><?php echo esc_html($fs_title); ?></h2>

        <div class="flash-right">
            <div id="flash-countdown-pro">Đang tải...</div>

            <a class="view-all-sale" href="<?php echo home_url('/khuyen-mai?sale=1'); ?>">
                Xem tất cả →
            </a>
        </div>

    </div>

    <div class="flash-products">

        <?php while ($loop->have_posts()) : $loop->the_post();
            global $product;

            if (!$product) continue;

            $regular = $product->get_regular_price();
            $sale    = $product->get_sale_price();

            if (!$sale || $regular <= 0) continue;

            $percent = round(100 - ($sale / $regular * 100));
        ?>

        <div class="flash-item">

            <a href="<?php the_permalink(); ?>">
                <?php
                if ($product->get_image_id()) {
                    echo $product->get_image();
                } else {
                    echo '<img src="https://via.placeholder.com/300x300?text=No+Image">';
                }
                ?>
            </a>

            <span class="discount">-<?php echo $percent; ?>%</span>

            <h3><?php the_title(); ?></h3>

            <div class="price">
                <span class="sale"><?php echo wc_price($sale); ?></span>
                <span class="old"><?php echo wc_price($regular); ?></span>
            </div>

            <a class="buy-now" href="<?php the_permalink(); ?>">Mua ngay</a>

        </div>

        <?php endwhile; wp_reset_postdata(); ?>

    </div>
</section>

<script>
(function() {
    const endTime = new Date("<?php echo $formatted_end; ?>").getTime();
    const timer = document.getElementById("flash-countdown-pro");

    if (timer) {
        const x = setInterval(() => {
            const now = new Date().getTime();
            const t = endTime - now;

            if (t <= 0) {
                clearInterval(x);
                timer.innerHTML = "Đã kết thúc";
                return;
            }

            const d = Math.floor(t / (1000 * 60 * 60 * 24));
            const h = Math.floor((t / (1000 * 60 * 60)) % 24);
            const m = Math.floor((t / (1000 * 60)) % 60);
            const s = Math.floor((t / 1000) % 60);

            if (d > 0) {
                timer.innerHTML = d + "d " + h + "h : " + m + "m : " + s + "s";
            } else {
                timer.innerHTML = h + "h : " + m + "m : " + s + "s";
            }
        }, 1000);
    }
})();
</script>

<style>
.flash-sale-wrap{
    max-width:1250px;
    margin:40px auto;
    padding:22px;
    background:linear-gradient(135deg,#fff7f0,#ffe0cc);
    border-radius:14px;
}
.flash-head{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:22px;
}
.flash-head h2{
    margin:0;
    color:#d84315;
    font-size:21px;
}
.flash-right{
    display:flex;
    align-items:center;
    gap:10px;
}
#flash-countdown-pro{
    background:#f4511e;
    color:#fff;
    padding:7px 12px;
    border-radius:7px;
    font-weight:bold;
    font-size:13px;
}
.view-all-sale{
    background:#ff8a65;
    color:#fff;
    padding:7px 12px;
    border-radius:7px;
    text-decoration:none;
    font-size:13px;
    transition:0.3s;
}
.view-all-sale:hover{
    background:#f4511e;
}
.flash-products{
    display:grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap:18px;
}
.flash-item{
    background:#fff;
    padding:13px;
    border-radius:12px;
    text-align:center;
    position:relative;
    box-shadow:0 4px 10px rgba(0,0,0,.06);
    display:flex;
    flex-direction:column;
    justify-content:space-between;
    height:100%;
    transition:0.3s;
}
.flash-item:hover{
    transform:translateY(-5px);
    box-shadow:0 8px 20px rgba(0,0,0,0.1);
}
.flash-item img{
    width:100%;
    height:200px;
    object-fit:cover;
    border-radius:10px;
}
.discount{
    position:absolute;
    top:10px;
    left:10px;
    background:#f4511e;
    color:#fff;
    padding:4px 9px;
    border-radius:6px;
    font-size:13px;
}
.flash-item h3{
    font-size:14.5px;
    line-height:1.4;
    height:42px;
    overflow:hidden;
    margin:10px 0;
}
.price{
    display:flex;
    justify-content:center;
    align-items:center;
    gap:6px;
    min-height:24px;
}
.price .sale{
    color:#d84315;
    font-weight:bold;
    font-size:15px;
}
.price .old{
    text-decoration:line-through;
    color:#999;
    font-size:13px;
}
.buy-now{
    margin-top:auto;
    background:#ff8a65;
    color:#fff;
    padding:9px;
    border-radius:9px;
    border:2px solid #ffd9c2;
    text-decoration:none;
    font-weight:500;
    font-size:13.5px;
    transition:0.3s;
}
.buy-now:hover{
    background:#f4511e;
    border-color:#ffcc80;
}
@media (max-width:768px){
    .flash-products{
        grid-template-columns: repeat(2, 1fr);
    }
    .flash-item img{
        height:170px;
    }
}
</style>

<?php
    endif;

    return ob_get_clean();
}

// ==========================================
// 3. LỌC TRANG SHOP ?sale=1
// ==========================================
add_action('pre_get_posts', function($query){
    if ( is_admin() || ! $query->is_main_query() ) return;

    if ( is_shop() && isset($_GET['sale']) && $_GET['sale'] == 1 ) {
        $sale_ids = wc_get_product_ids_on_sale();

        if ( empty($sale_ids) ) {
            $sale_ids = [0];
        }

        $query->set('post__in', $sale_ids);
        $query->set('orderby', 'post__in');
        $query->set('meta_query', []);
    }
});