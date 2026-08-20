<?php
/**
 * Plugin Name: 1. Quản Lý Khách Hàng & Tạo Tài Khoản
 * Description: Đăng ký, quản lý thông tin khách hàng, tự động tạo tài khoản WordPress, tích hợp Bản đồ GPS và Cắt ảnh Avatar (Cropper).
 * Version: 3.4
 * Author: Trần Phi Hùng
 */

if (!defined('ABSPATH')) exit;

// 1. Tạo hoặc nâng cấp cấu trúc bảng CSDL khi Activate
register_activation_hook(__FILE__, 'qlkh_create_table');
function qlkh_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'khach_hang_leads';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) DEFAULT 0 NOT NULL,
        fullname varchar(100) NOT NULL,
        email varchar(100) NOT NULL,
        phone varchar(20) DEFAULT '',
        address text DEFAULT '',
        gender varchar(10) DEFAULT '',
        avatar longtext DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Tự động kiểm tra và bổ sung cột nếu bảng cũ thiếu các trường mới
add_action('admin_init', 'qlkh_check_db_columns');
function qlkh_check_db_columns() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'khach_hang_leads';

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
        $row = $wpdb->get_row("SELECT * FROM $table_name LIMIT 1");
        if ($row) {
            if (!property_exists($row, 'address')) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN address text DEFAULT ''");
            }
            if (!property_exists($row, 'gender')) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN gender varchar(10) DEFAULT ''");
            }
            if (!property_exists($row, 'avatar')) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN avatar longtext DEFAULT ''");
            }
        }
    }
}

// Enqueue Leaflet (Bản đồ) & Croppie (Cắt ảnh)
add_action('wp_enqueue_scripts', 'qlkh_enqueue_assets');
add_action('admin_enqueue_scripts', 'qlkh_enqueue_assets');
function qlkh_enqueue_assets() {
    // Leaflet Maps
    wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4');
    wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true);

    // Croppie (Library cắt ảnh JS)
    wp_enqueue_style('croppie-css', 'https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.5/croppie.min.css', array(), '2.6.5');
    wp_enqueue_script('croppie-js', 'https://cdnjs.cloudflare.com/ajax/libs/croppie/2.6.5/croppie.min.js', array(), '2.6.5', true);
}

// 2. Menu Dashboard Admin
add_action('admin_menu', 'qlkh_add_admin_menu');
function qlkh_add_admin_menu() {
    add_menu_page(
        'Danh Sách Khách Hàng',
        'Khách Hàng',
        'manage_options',
        'ql-khach-hang',
        'qlkh_admin_page',
        'dashicons-groups',
        6
    );

    add_submenu_page(
        'ql-khach-hang',
        'Cài Đặt Layout Form',
        'Cài Đặt Layout',
        'manage_options',
        'ql-khach-hang-settings',
        'qlkh_settings_page'
    );
}

function qlkh_render_shortcode_box() {
    ?>
    <div style="background: #fff; border-left: 4px solid #72aee6; padding: 15px; margin: 20px 0; box-shadow: 0 1px 1px rgba(0,0,0,.04); max-width: 700px;">
        <h3 style="margin-top: 0; margin-bottom: 10px;">📌 Mã Shortcode Tích Hợp</h3>
        <p style="margin-bottom: 5px; color: #666;">• <strong>Form Đăng Ký:</strong> <code>[form_dang_ky_khach_hang]</code></p>
        <p style="margin-bottom: 10px; color: #666;">• <strong>Trang Hồ Sơ Khách Hàng (Tự chỉnh sửa & Cắt Avatar):</strong> <code>[thong_tin_tai_khoan]</code></p>
    </div>
    <?php
}

// 3. Trang Cài Đặt Layout Form
function qlkh_settings_page() {
    if (isset($_POST['qlkh_save_settings'])) {
        update_option('qlkh_form_title', sanitize_text_field($_POST['qlkh_form_title']));
        update_option('qlkh_bg_color', sanitize_hex_color($_POST['qlkh_bg_color']));
        update_option('qlkh_text_color', sanitize_hex_color($_POST['qlkh_text_color']));
        update_option('qlkh_btn_color', sanitize_hex_color($_POST['qlkh_btn_color']));
        update_option('qlkh_border_radius', intval($_POST['qlkh_border_radius']));
        echo '<div class="updated"><p>Đã lưu cài đặt Layout Form thành công!</p></div>';
    }

    $form_title    = get_option('qlkh_form_title', 'Đăng Ký Tài Khoản Khách Hàng');
    $bg_color      = get_option('qlkh_bg_color', '#ffffff');
    $text_color    = get_option('qlkh_text_color', '#333333');
    $btn_color     = get_option('qlkh_btn_color', '#0073aa');
    $border_radius = get_option('qlkh_border_radius', 8);
    ?>
    <div class="wrap">
        <h1>Cài Đặt Layout Form Đăng Ký (Front-End)</h1>
        <?php qlkh_render_shortcode_box(); ?>
        <form method="POST" style="background: #fff; padding: 20px; border: 1px solid #ccc; max-width: 600px; margin-top: 15px;">
            <table class="form-table">
                <tr><th><label>Tiêu đề Form</label></th><td><input type="text" name="qlkh_form_title" value="<?php echo esc_attr($form_title); ?>" class="regular-text" required></td></tr>
                <tr><th><label>Màu nền Form</label></th><td><input type="color" name="qlkh_bg_color" value="<?php echo esc_attr($bg_color); ?>"></td></tr>
                <tr><th><label>Màu chữ</label></th><td><input type="color" name="qlkh_text_color" value="<?php echo esc_attr($text_color); ?>"></td></tr>
                <tr><th><label>Màu nút gửi</label></th><td><input type="color" name="qlkh_btn_color" value="<?php echo esc_attr($btn_color); ?>"></td></tr>
                <tr><th><label>Bo góc (px)</label></th><td><input type="number" name="qlkh_border_radius" value="<?php echo esc_attr($border_radius); ?>" min="0" max="50"></td></tr>
            </table>
            <?php submit_button('Lưu Tùy Chỉnh Layout', 'primary', 'qlkh_save_settings'); ?>
        </form>
    </div>
    <?php
}

// 4. Shortcode [form_dang_ky_khach_hang]
add_shortcode('form_dang_ky_khach_hang', 'qlkh_render_form');
function qlkh_render_form() {
    ob_start();
    global $wpdb;
    $table_name = $wpdb->prefix . 'khach_hang_leads';

    $form_title    = get_option('qlkh_form_title', 'Đăng Ký Tài Khoản Khách Hàng');
    $bg_color      = get_option('qlkh_bg_color', '#ffffff');
    $text_color    = get_option('qlkh_text_color', '#333333');
    $btn_color     = get_option('qlkh_btn_color', '#0073aa');
    $border_radius = get_option('qlkh_border_radius', 8);

    if (isset($_POST['qlkh_submit'])) {
        $fullname   = isset($_POST['fullname']) ? sanitize_text_field($_POST['fullname']) : '';
        $raw_email  = isset($_POST['email']) ? trim($_POST['email']) : '';
        $phone      = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        $password   = isset($_POST['password']) ? $_POST['password'] : '';

        if (!is_email($raw_email)) {
            echo '<div style="color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 12px; border-radius: 4px; margin-bottom: 15px;">❌ <strong>Lỗi:</strong> Email không hợp lệ!</div>';
        } elseif (!empty($fullname) && !empty($password)) {
            $email = sanitize_email($raw_email);

            if (email_exists($email)) {
                echo '<div style="color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 12px; border-radius: 4px; margin-bottom: 15px;">❌ <strong>Lỗi:</strong> Email này đã được sử dụng!</div>';
            } else {
                $username = !empty($phone) ? sanitize_user($phone) : '';
                if (empty($username) || username_exists($username)) {
                    $parts = explode('@', $email);
                    $username = sanitize_user($parts[0]);
                    if (username_exists($username)) {
                        $username = $username . '_' . rand(100, 999);
                    }
                }

                $user_id = wp_create_user($username, $password, $email);

                if (!is_wp_error($user_id)) {
                    wp_update_user(array(
                        'ID'           => $user_id,
                        'display_name' => $fullname,
                        'first_name'   => $fullname,
                        'role'         => 'subscriber'
                    ));

                    $wpdb->insert(
                        $table_name,
                        array(
                            'user_id'    => $user_id,
                            'fullname'   => $fullname, 
                            'email'      => $email, 
                            'phone'      => $phone,
                            'created_at' => current_time('mysql')
                        ),
                        array('%d', '%s', '%s', '%s', '%s')
                    );

                    echo '<div style="color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 12px; border-radius: 4px; margin-bottom: 15px;">✅ <strong>Thành công:</strong> Đã tạo tài khoản thành công!</div>';
                } else {
                    echo '<div style="color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 12px; border-radius: 4px; margin-bottom: 15px;">❌ ' . esc_html($user_id->get_error_message()) . '</div>';
                }
            }
        }
    }
    ?>
    <form method="POST" autocomplete="off" style="max-width: 450px; padding: 25px; border: 1px solid #ddd; background: <?php echo esc_attr($bg_color); ?>; border-radius: <?php echo esc_attr($border_radius); ?>px; box-shadow: 0 4px 10px rgba(0,0,0,0.08); margin: 0 auto;">
        <h3 style="margin-top:0; margin-bottom: 20px; font-size: 20px; color: <?php echo esc_attr($text_color); ?>; text-align: center; font-weight: bold;"><?php echo esc_html($form_title); ?></h3>
        <p style="margin-bottom: 12px;">
            <label style="font-weight: bold; display: block; margin-bottom: 5px; color: <?php echo esc_attr($text_color); ?>;">Họ và Tên (*)</label>
            <input type="text" name="fullname" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
        </p>
        <p style="margin-bottom: 12px;">
            <label style="font-weight: bold; display: block; margin-bottom: 5px; color: <?php echo esc_attr($text_color); ?>;">Email (*)</label>
            <input type="email" name="email" placeholder="nguyenvana@gmail.com" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
        </p>
        <p style="margin-bottom: 12px;">
            <label style="font-weight: bold; display: block; margin-bottom: 5px; color: <?php echo esc_attr($text_color); ?>;">Số Điện Thoại</label>
            <input type="text" name="phone" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
        </p>
        <p style="margin-bottom: 20px;">
            <label style="font-weight: bold; display: block; margin-bottom: 5px; color: <?php echo esc_attr($text_color); ?>;">Mật Khẩu Mới (*)</label>
            <input type="password" name="password" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
        </p>
        <p style="margin: 0;">
            <button type="submit" name="qlkh_submit" style="background: <?php echo esc_attr($btn_color); ?>; color: #fff; border: none; padding: 12px 20px; border-radius: 4px; cursor: pointer; font-weight: bold; width: 100%; font-size: 16px;">Tạo Tài Khoản</button>
        </p>
    </form>
    <?php
    return ob_get_clean();
}

// 5. Shortcode [thong_tin_tai_khoan] Cho Khách Hàng Tự Chỉnh Sửa Hồ Sơ, Cắt Avatar & Ghim GPS
add_shortcode('thong_tin_tai_khoan', 'qlkh_render_profile_form');
function qlkh_render_profile_form() {
    if (!is_user_logged_in()) {
        return '<div style="padding: 15px; background: #fff3cd; color: #856404; border-radius: 4px; text-align: center;">Bạn cần <a href="' . wp_login_url() . '">Đăng nhập</a> để xem và chỉnh sửa thông tin tài khoản.</div>';
    }

    ob_start();
    global $wpdb;
    $current_user = wp_get_current_user();
    $user_id      = $current_user->ID;
    $table_name   = $wpdb->prefix . 'khach_hang_leads';

    $customer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id));

    // Xử lý Form Cập Nhật Thông Tin Cá Nhân
    if (isset($_POST['qlkh_update_profile'])) {
        $fullname   = isset($_POST['fullname']) ? sanitize_text_field($_POST['fullname']) : '';
        $email      = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $phone      = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        $address    = isset($_POST['address']) ? sanitize_textarea_field($_POST['address']) : '';
        $gender     = isset($_POST['gender']) ? sanitize_text_field($_POST['gender']) : 'Khác';
        $avatar_base64 = isset($_POST['avatar_cropped_data']) ? $_POST['avatar_cropped_data'] : '';

        if (!empty($fullname) && !empty($email) && is_email($email)) {
            $avatar_url = (isset($customer->avatar)) ? $customer->avatar : '';
            if (!empty($avatar_base64)) {
                $avatar_url = $avatar_base64; // Lưu chuỗi Base64 của ảnh đã cắt
            }

            wp_update_user(array('ID' => $user_id, 'display_name' => $fullname, 'user_email' => $email));

            if ($customer) {
                $wpdb->update(
                    $table_name,
                    array('fullname' => $fullname, 'email' => $email, 'phone' => $phone, 'address' => $address, 'gender' => $gender, 'avatar' => $avatar_url),
                    array('user_id' => $user_id),
                    array('%s', '%s', '%s', '%s', '%s', '%s'),
                    array('%d')
                );
            } else {
                $wpdb->insert(
                    $table_name,
                    array('user_id' => $user_id, 'fullname' => $fullname, 'email' => $email, 'phone' => $phone, 'address' => $address, 'gender' => $gender, 'avatar' => $avatar_url, 'created_at' => current_time('mysql')),
                    array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
                );
            }

            echo '<div style="color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 12px; border-radius: 4px; margin-bottom: 15px;">✅ Đã cập nhật hồ sơ cá nhân thành công!</div>';
            $customer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id));
        } else {
            echo '<div style="color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 12px; border-radius: 4px; margin-bottom: 15px;">❌ <strong>Lỗi:</strong> Vui lòng điền đầy đủ Họ và Tên và Email hợp lệ!</div>';
        }
    }

    // Xử lý Đổi Mật Khẩu
    if (isset($_POST['qlkh_change_password'])) {
        $old_pass     = $_POST['old_password'];
        $new_pass     = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];

        if (!wp_check_password($old_pass, $current_user->user_pass, $user_id)) {
            echo '<div style="color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 12px; border-radius: 4px; margin-bottom: 15px;">❌ <strong>Lỗi:</strong> Mật khẩu cũ không chính xác!</div>';
        } elseif (strlen($new_pass) < 6) {
            echo '<div style="color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 12px; border-radius: 4px; margin-bottom: 15px;">⚠️ Mật khẩu mới phải có ít nhất 6 ký tự.</div>';
        } elseif ($new_pass !== $confirm_pass) {
            echo '<div style="color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 12px; border-radius: 4px; margin-bottom: 15px;">❌ <strong>Lỗi:</strong> Mật khẩu mới không khớp nhau!</div>';
        } else {
            wp_set_password($new_pass, $user_id);
            echo '<div style="color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 12px; border-radius: 4px; margin-bottom: 15px;">✅ Đã đổi mật khẩu thành công!</div>';
        }
    }

    $fullname_val = isset($customer->fullname) ? $customer->fullname : $current_user->display_name;
    $email_val    = isset($customer->email) ? $customer->email : $current_user->user_email;
    $phone_val    = isset($customer->phone) ? $customer->phone : '';
    $address_val  = isset($customer->address) ? $customer->address : '';
    $gender_val   = isset($customer->gender) ? $customer->gender : 'Khác';
    $avatar_val   = (isset($customer->avatar) && !empty($customer->avatar)) ? $customer->avatar : get_avatar_url($user_id);
    ?>

    <div style="max-width: 600px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); font-family: sans-serif;">
        <h2 style="text-align: center; margin-top: 0; color: #333;">Thông Tin Tài Khoản</h2>
        
        <!-- Form Thông Tin Cá Nhân -->
        <form method="POST" enctype="multipart/form-data" style="margin-bottom: 30px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <!-- Avatar Preview -->
                <img id="avatar_preview" src="<?php echo esc_url($avatar_val); ?>" style="width: 110px; height: 110px; border-radius: 50%; object-fit: cover; border: 3px solid #0073aa; box-shadow: 0 2px 8px rgba(0,0,0,0.15);"><br>
                <label style="margin-top: 10px; display: inline-block; cursor: pointer; color: #0073aa; font-weight: bold; font-size: 14px;">
                    📷 Thay đổi Avatar
                    <input type="file" id="upload_avatar_file" accept="image/*" style="display: none;">
                </label>
                <!-- Thẻ chứa dữ liệu ảnh đã cắt -->
                <input type="hidden" name="avatar_cropped_data" id="avatar_cropped_data">
            </div>

            <p>
                <label style="font-weight: bold; display: block; margin-bottom: 5px;">Họ và Tên (*)</label>
                <input type="text" name="fullname" value="<?php echo esc_attr($fullname_val); ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </p>
            <p>
                <label style="font-weight: bold; display: block; margin-bottom: 5px;">Email (*)</label>
                <input type="email" name="email" value="<?php echo esc_attr($email_val); ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </p>
            <p>
                <label style="font-weight: bold; display: block; margin-bottom: 5px;">Số Điện Thoại</label>
                <input type="text" name="phone" value="<?php echo esc_attr($phone_val); ?>" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </p>
            <p>
                <label style="font-weight: bold; display: block; margin-bottom: 5px;">Giới Tính</label>
                <select name="gender" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                    <option value="Nam" <?php selected($gender_val, 'Nam'); ?>>Nam</option>
                    <option value="Nữ" <?php selected($gender_val, 'Nữ'); ?>>Nữ</option>
                    <option value="Khác" <?php selected($gender_val, 'Khác'); ?>>Khác</option>
                </select>
            </p>
            
            <!-- Địa Chỉ Tích Hợp Bản Đồ Auto-Ghim GPS -->
            <p style="margin-bottom: 10px;">
                <label style="font-weight: bold; display: block; margin-bottom: 5px;">Địa Chỉ</label>
                <textarea id="customer_address" name="address" rows="2" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;"><?php echo esc_textarea($address_val); ?></textarea>
            </p>
            
            <div style="margin-bottom: 15px;">
                <button type="button" id="btn_locate_me" style="background: #28a745; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-size: 13px; margin-bottom: 8px;">
                    📍 Định vị vị trí hiện tại của tôi
                </button>
                <small style="display: block; color: #666; margin-bottom: 5px;">* Bạn có thể bấm nút trên hoặc nhấp trực tiếp vào bản đồ để ghim vị trí chính xác.</small>
                <div id="map" style="height: 250px; width: 100%; border-radius: 6px; border: 1px solid #ccc;"></div>
            </div>

            <button type="submit" name="qlkh_update_profile" style="background: #0073aa; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; width: 100%; font-size: 16px;">Lưu Thay Đổi Thông Tin</button>
        </form>

        <hr style="border: 0; border-top: 1px solid #eee; margin: 30px 0;">

        <!-- Form Đổi Mật Khẩu -->
        <h3 style="color: #333; margin-top: 0;">Đổi Mật Khẩu</h3>
        <form method="POST">
            <p>
                <label style="font-weight: bold; display: block; margin-bottom: 5px;">Mật Khẩu Hiện Tại (*)</label>
                <input type="password" name="old_password" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </p>
            <p>
                <label style="font-weight: bold; display: block; margin-bottom: 5px;">Mật Khẩu Mới (*)</label>
                <input type="password" name="new_password" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </p>
            <p>
                <label style="font-weight: bold; display: block; margin-bottom: 5px;">Xác Nhận Mật Khẩu Mới (*)</label>
                <input type="password" name="confirm_password" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </p>
            <button type="submit" name="qlkh_change_password" style="background: #23282d; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; width: 100%;">Cập Nhật Mật Khẩu</button>
        </form>
    </div>

    <!-- POPUP MODAL CẮT ẢNH AVATAR -->
    <div id="croppie_modal" style="display: none; position: fixed; z-index: 99999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.7); justify-content: center; align-items: center;">
        <div style="background: #fff; padding: 20px; border-radius: 8px; width: 90%; max-width: 450px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.3);">
            <h3 style="margin-top: 0; margin-bottom: 15px; color: #333;">Cắt & Căn Chỉnh Avatar</h3>
            <div id="croppie_demo" style="width: 100%; margin: 0 auto;"></div>
            <div style="margin-top: 15px; display: flex; gap: 10px; justify-content: center;">
                <button type="button" id="btn_crop_image" style="background: #0073aa; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold;">Áp dụng ảnh này</button>
                <button type="button" id="btn_close_modal" style="background: #dc3545; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer;">Hủy</button>
            </div>
        </div>
    </div>

    <!-- Script Tự Động Định Vị Bản Đồ & Xử Lý Cắt Ảnh Avatar -->
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // --- 1. XỬ LÝ CẮT VÀ PREVIEW AVATAR ---
        var croppieInstance = null;
        var uploadInput = document.getElementById('upload_avatar_file');
        var modal = document.getElementById('croppie_modal');
        var croppieContainer = document.getElementById('croppie_demo');

        uploadInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    modal.style.display = 'flex';

                    if (croppieInstance) {
                        croppieInstance.destroy();
                    }

                    croppieInstance = new Croppie(croppieContainer, {
                        viewport: { width: 180, height: 180, type: 'circle' },
                        boundary: { width: 280, height: 280 },
                        showZoomer: true,
                        enableOrientation: true
                    });

                    croppieInstance.bind({
                        url: e.target.result
                    });
                }
                reader.readAsDataURL(this.files[0]);
            }
        });

        document.getElementById('btn_crop_image').addEventListener('click', function() {
            if (croppieInstance) {
                croppieInstance.result({
                    type: 'base64',
                    size: { width: 300, height: 300 },
                    format: 'jpeg',
                    circle: false
                }).then(function(base64) {
                    document.getElementById('avatar_preview').src = base64;
                    document.getElementById('avatar_cropped_data').value = base64;
                    modal.style.display = 'none';
                });
            }
        });

        document.getElementById('btn_close_modal').addEventListener('click', function() {
            modal.style.display = 'none';
            uploadInput.value = '';
        });

        // --- 2. XỬ LÝ BẢN ĐỒ GPS LEAFLET ---
        if (typeof L !== 'undefined') {
            var defaultLat = 10.7769;
            var defaultLng = 106.7009;

            var map = L.map('map').setView([defaultLat, defaultLng], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap'
            }).addTo(map);

            var marker = L.marker([defaultLat, defaultLng], {draggable: true}).addTo(map);

            function updateAddressFromLatLng(lat, lng) {
                fetch('https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + lat + '&lon=' + lng)
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.display_name) {
                            document.getElementById('customer_address').value = data.display_name;
                        }
                    })
                    .catch(err => console.error('Lỗi lấy địa chỉ:', err));
            }

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    var userLat = position.coords.latitude;
                    var userLng = position.coords.longitude;

                    map.setView([userLat, userLng], 16);
                    marker.setLatLng([userLat, userLng]);

                    if (!document.getElementById('customer_address').value.trim()) {
                        updateAddressFromLatLng(userLat, userLng);
                    }
                }, function(error) {
                    console.log("Chưa bật định vị.");
                });
            }

            document.getElementById('btn_locate_me').addEventListener('click', function() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        var userLat = position.coords.latitude;
                        var userLng = position.coords.longitude;

                        map.setView([userLat, userLng], 16);
                        marker.setLatLng([userLat, userLng]);
                        updateAddressFromLatLng(userLat, userLng);
                    }, function(error) {
                        alert('Vui lòng bật tính năng định vị (GPS) trên thiết bị!');
                    });
                }
            });

            map.on('click', function(e) {
                var lat = e.latlng.lat;
                var lng = e.latlng.lng;
                marker.setLatLng([lat, lng]);
                updateAddressFromLatLng(lat, lng);
            });

            marker.on('dragend', function(e) {
                var position = marker.getLatLng();
                updateAddressFromLatLng(position.lat, position.lng);
            });
        }
    });
    </script>
    <?php
    return ob_get_clean();
}

// 6. Trang Dashboard Admin
function qlkh_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'khach_hang_leads';

    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $customer = $wpdb->get_row($wpdb->prepare("SELECT user_id FROM $table_name WHERE id = %d", $id));
        if ($customer && $customer->user_id > 0) {
            require_once(ABSPATH . 'wp-admin/includes/user.php');
            wp_delete_user($customer->user_id);
        }
        $wpdb->delete($table_name, array('id' => $id), array('%d'));
        echo '<div class="notice notice-success is-dismissible"><p>Đã xóa khách hàng thành công!</p></div>';
    }

    if (isset($_POST['qlkh_admin_edit_submit'])) {
        $edit_id   = intval($_POST['edit_id']);
        $fullname  = sanitize_text_field($_POST['fullname']);
        $email     = sanitize_email($_POST['email']);
        $phone     = sanitize_text_field($_POST['phone']);
        $address   = sanitize_textarea_field($_POST['address']);
        $gender    = sanitize_text_field($_POST['gender']);
        $user_id   = intval($_POST['user_id']);

        if ($user_id > 0) {
            wp_update_user(array('ID' => $user_id, 'display_name' => $fullname, 'user_email' => $email));
            if (!empty($_POST['new_password'])) {
                wp_set_password($_POST['new_password'], $user_id);
            }
        }

        $wpdb->update(
            $table_name,
            array('fullname' => $fullname, 'email' => $email, 'phone' => $phone, 'address' => $address, 'gender' => $gender),
            array('id' => $edit_id),
            array('%s', '%s', '%s', '%s', '%s'),
            array('%d')
        );

        echo '<div class="notice notice-success is-dismissible"><p>Đã cập nhật thông tin khách hàng thành công!</p></div>';
    }

    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        $edit_id = intval($_GET['id']);
        $edit_customer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $edit_id));
        if ($edit_customer):
            $gender_val  = isset($edit_customer->gender) ? $edit_customer->gender : 'Khác';
            $address_val = isset($edit_customer->address) ? $edit_customer->address : '';
        ?>
        <div class="wrap">
            <h1>Chỉnh Sửa Thông Tin Khách Hàng (Admin)</h1>
            <form method="POST" style="background: #fff; padding: 20px; border: 1px solid #ccc; max-width: 600px; margin-top: 15px;">
                <input type="hidden" name="edit_id" value="<?php echo esc_attr($edit_customer->id); ?>">
                <input type="hidden" name="user_id" value="<?php echo esc_attr($edit_customer->user_id); ?>">
                <table class="form-table">
                    <tr><th><label>Họ và Tên (*)</label></th><td><input type="text" name="fullname" value="<?php echo esc_attr($edit_customer->fullname); ?>" class="regular-text" required></td></tr>
                    <tr><th><label>Email (*)</label></th><td><input type="email" name="email" value="<?php echo esc_attr($edit_customer->email); ?>" class="regular-text" required></td></tr>
                    <tr><th><label>Số Điện Thoại</label></th><td><input type="text" name="phone" value="<?php echo esc_attr($edit_customer->phone); ?>" class="regular-text"></td></tr>
                    <tr>
                        <th><label>Giới Tính</label></th>
                        <td>
                            <select name="gender">
                                <option value="Nam" <?php selected($gender_val, 'Nam'); ?>>Nam</option>
                                <option value="Nữ" <?php selected($gender_val, 'Nữ'); ?>>Nữ</option>
                                <option value="Khác" <?php selected($gender_val, 'Khác'); ?>>Khác</option>
                            </select>
                        </td>
                    </tr>
                    <tr><th><label>Địa Chỉ</label></th><td><textarea name="address" class="large-text" rows="3"><?php echo esc_textarea($address_val); ?></textarea></td></tr>
                    <tr><th><label>Đặt Mật Khẩu Mới (Bỏ trống nếu không đổi)</label></th><td><input type="password" name="new_password" class="regular-text"></td></tr>
                </table>
                <p class="submit">
                    <input type="submit" name="qlkh_admin_edit_submit" class="button button-primary" value="Lưu Thay Đổi">
                    <a href="?page=ql-khach-hang" class="button">Hủy</a>
                </p>
            </form>
        </div>
        <?php
        return;
        endif;
    }

    $customers = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Danh Sách Khách Hàng Đăng Ký</h1>
        <a href="?page=ql-khach-hang-settings" class="page-title-action">Cài Đặt Layout Form</a>
        <hr class="wp-header-end">
        
        <?php qlkh_render_shortcode_box(); ?>

        <table class="wp-list-table widefat fixed striped" style="margin-top: 15px;">
            <thead>
                <tr>
                    <th width="5%">ID</th>
                    <th width="8%">Avatar</th>
                    <th>Họ và Tên</th>
                    <th>Email</th>
                    <th>SĐT</th>
                    <th>Giới Tính</th>
                    <th>Địa Chỉ</th>
                    <th>Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($customers)): ?>
                    <?php foreach ($customers as $row): 
                        $avatar_row  = (isset($row->avatar) && !empty($row->avatar)) ? $row->avatar : '';
                        $phone_row   = isset($row->phone) && !empty($row->phone) ? $row->phone : 'Chưa nhập';
                        $gender_row  = isset($row->gender) && !empty($row->gender) ? $row->gender : 'Chưa nhập';
                        $address_row = isset($row->address) && !empty($row->address) ? $row->address : 'Chưa nhập';
                    ?>
                        <tr>
                            <td><?php echo esc_html($row->id); ?></td>
                            <td>
                                <?php if (!empty($avatar_row)): ?>
                                    <img src="<?php echo esc_url($avatar_row); ?>" style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover;">
                                <?php else: ?>
                                    <span class="dashicons dashicons-admin-users" style="font-size: 30px; color: #aaa;"></span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo esc_html($row->fullname); ?></strong></td>
                            <td><?php echo esc_html($row->email); ?></td>
                            <td><?php echo esc_html($phone_row); ?></td>
                            <td><?php echo esc_html($gender_row); ?></td>
                            <td><?php echo esc_html($address_row); ?></td>
                            <td>
                                <a href="?page=ql-khach-hang&action=edit&id=<?php echo esc_attr($row->id); ?>" class="button button-small button-primary">Sửa</a>
                                <a href="?page=ql-khach-hang&action=delete&id=<?php echo esc_attr($row->id); ?>" 
                                   onclick="return confirm('Bạn có chắc chắn muốn xóa khách hàng này?')" 
                                   class="button button-small button-link-delete" style="color: #d63638;">Xóa</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align: center; padding: 20px; color: #666;">Chưa có dữ liệu khách hàng.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}