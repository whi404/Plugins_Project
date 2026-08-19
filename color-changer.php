<?php
/*
Plugin Name: Global Color Changer
Description: Plugin cho phép thay đổi màu sắc chủ đạo của toàn bộ website qua Dashboard.
Version: 1.0
Author: Nguyễn Thành Đạt
*/

// Ngăn chặn truy cập trực tiếp vào file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Khởi tạo Menu trong Dashboard
add_action('admin_menu', 'gcc_add_admin_menu');
function gcc_add_admin_menu() {
    add_menu_page(
        'Tùy chỉnh màu sắc', 
        'Đổi màu Website', 
        'manage_options', 
        'global-color-changer', 
        'gcc_settings_page_layout', 
        'dashicons-art', 
        80
    );
}

// 2. Đăng ký Cài đặt (Settings API)
add_action('admin_init', 'gcc_register_settings');
function gcc_register_settings() {
    register_setting('gcc_settings_group', 'gcc_primary_color', 'sanitize_hex_color');
    
    add_settings_section('gcc_main_section', 'Cài đặt màu sắc toàn cục', null, 'global-color-changer');
    
    add_settings_field('gcc_primary_color_field', 'Màu chủ đạo (Primary Color)', 'gcc_color_field_html', 'global-color-changer', 'gcc_main_section');
}

// HTML cho ô chọn màu
function gcc_color_field_html() {
    $color = get_option('gcc_primary_color', '#0073aa'); // Màu mặc định
    echo '<input type="color" name="gcc_primary_color" value="' . esc_attr($color) . '" />';
    echo '<p class="description">Chọn màu sắc chủ đạo sẽ áp dụng cho toàn bộ website.</p>';
}

// 3. Xây dựng Giao diện trang quản lý (Dashboard Page)
function gcc_settings_page_layout() {
    // Kiểm tra quyền
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1>Quản lý màu sắc Website</h1>
        <form action="options.php" method="post">
            <?php 
            // Xuất các trường bảo mật và kết nối với group đã đăng ký
            settings_fields('gcc_settings_group');
            do_settings_sections('global-color-changer');
            submit_button('Lưu thay đổi');
            ?>
        </form>
    </div>
    <?php
}

// 4. Đưa màu sắc ra ngoài Website (Frontend)
add_action('wp_head', 'gcc_apply_custom_colors');
function gcc_apply_custom_colors() {
    $primary_color = get_option('gcc_primary_color', '#0073aa');
    ?>
    <style>
        :root {
            --gcc-primary-color: <?php echo esc_attr($primary_color); ?>;
        }
        
        /* Thay thế các selector dưới đây bằng class/id thực tế trên theme của bạn */
        body {
            color: var(--gcc-primary-color);
        }
        h1, h2, h3, h4, h5, h6, a {
            color: var(--gcc-primary-color) !important;
        }
        .button, button, input[type="submit"] {
            background-color: var(--gcc-primary-color) !important;
            border-color: var(--gcc-primary-color) !important;
        }
    </style>
    <?php
}