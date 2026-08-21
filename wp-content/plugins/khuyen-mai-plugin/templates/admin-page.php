<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap kmm-wrap">

    <div class="kmm-header">
        <h1><span class="dashicons dashicons-tag"></span> Quản Lý Khuyến Mãi</h1>
        <p class="kmm-subtitle">Tạo chương trình khuyến mãi theo thời gian và phần trăm giảm giá.</p>
    </div>

    <?php if (isset($_GET['kmm_msg'])) :
        $msg_map = [
            'saved'   => 'Đã lưu chương trình khuyến mãi thành công!',
            'deleted' => 'Đã xóa chương trình khuyến mãi.',
            'updated' => 'Đã cập nhật trạng thái.',
        ];
        $msg = $msg_map[$_GET['kmm_msg']] ?? '';
        if ($msg) : ?>
            <div class="kmm-notice"><span class="dashicons dashicons-yes-alt"></span> <?php echo esc_html($msg); ?></div>
        <?php endif;
    endif; ?>

    <div class="kmm-grid">

        <!-- FORM THÊM / SỬA -->
        <div class="kmm-card kmm-form-card">
            <h2><?php echo $edit_promo ? 'Chỉnh sửa khuyến mãi' : 'Thêm khuyến mãi mới'; ?></h2>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="kmm-form">
                <input type="hidden" name="action" value="kmm_save_promotion">
                <input type="hidden" name="kmm_id" value="<?php echo esc_attr($edit_promo['id'] ?? ''); ?>">
                <?php wp_nonce_field('kmm_save_promotion_action', 'kmm_nonce'); ?>

                <div class="kmm-field">
                    <label for="kmm_name">Tên chương trình</label>
                    <input type="text" id="kmm_name" name="kmm_name" placeholder="VD: Sale mùa hè, Flash Sale 12.12..."
                        value="<?php echo esc_attr($edit_promo['name'] ?? ''); ?>" required>
                </div>

                <div class="kmm-field">
                    <label for="kmm_percent">Phần trăm giảm giá (%)</label>
                    <div class="kmm-percent-input">
                        <input type="number" id="kmm_percent" name="kmm_percent" min="0" max="100" step="0.1"
                            placeholder="0 - 100" value="<?php echo esc_attr($edit_promo['percent'] ?? ''); ?>" required>
                        <span>%</span>
                    </div>
                </div>

                <div class="kmm-field-row">
                    <div class="kmm-field">
                        <label for="kmm_start">Thời gian bắt đầu</label>
                        <input type="datetime-local" id="kmm_start" name="kmm_start"
                            value="<?php echo esc_attr(!empty($edit_promo['start']) ? date('Y-m-d\TH:i', strtotime($edit_promo['start'])) : ''); ?>" required>
                    </div>
                    <div class="kmm-field">
                        <label for="kmm_end">Thời gian kết thúc</label>
                        <input type="datetime-local" id="kmm_end" name="kmm_end"
                            value="<?php echo esc_attr(!empty($edit_promo['end']) ? date('Y-m-d\TH:i', strtotime($edit_promo['end'])) : ''); ?>" required>
                    </div>
                </div>

                <div class="kmm-field">
                    <label for="kmm_note">Ghi chú (tùy chọn)</label>
                    <textarea id="kmm_note" name="kmm_note" rows="3" placeholder="Điều kiện áp dụng, sản phẩm áp dụng..."><?php echo esc_textarea($edit_promo['note'] ?? ''); ?></textarea>
                </div>

                <div class="kmm-form-actions">
                    <button type="submit" class="button button-primary kmm-btn-primary">
                        <span class="dashicons dashicons-yes"></span>
                        <?php echo $edit_promo ? 'Cập nhật' : 'Thêm khuyến mãi'; ?>
                    </button>
                    <?php if ($edit_promo): ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=kmm-promotions')); ?>" class="button kmm-btn-cancel">Hủy</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- DANH SÁCH KHUYẾN MÃI -->
        <div class="kmm-card kmm-list-card">
            <h2>Danh sách chương trình (<?php echo count($promos); ?>)</h2>

            <?php if (empty($promos)) : ?>
                <div class="kmm-empty">
                    <span class="dashicons dashicons-info"></span>
                    <p>Chưa có chương trình khuyến mãi nào. Hãy tạo mới ở form bên trái.</p>
                </div>
            <?php else : ?>
                <div class="kmm-table-wrap">
                    <table class="kmm-table">
                        <thead>
                            <tr>
                                <th>Tên chương trình</th>
                                <th>Giảm giá</th>
                                <th>Bắt đầu</th>
                                <th>Kết thúc</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($promos as $p):
                            $status = $this->get_status($p);
                        ?>
                            <tr>
                                <td data-label="Tên">
                                    <strong><?php echo esc_html($p['name']); ?></strong>
                                    <?php if (!empty($p['note'])): ?>
                                        <div class="kmm-note-text"><?php echo esc_html($p['note']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Giảm giá"><span class="kmm-percent-badge"><?php echo esc_html($p['percent']); ?>%</span></td>
                                <td data-label="Bắt đầu"><?php echo esc_html(date('d/m/Y H:i', strtotime($p['start']))); ?></td>
                                <td data-label="Kết thúc"><?php echo esc_html(date('d/m/Y H:i', strtotime($p['end']))); ?></td>
                                <td data-label="Trạng thái"><span class="kmm-badge <?php echo esc_attr($status['class']); ?>"><?php echo esc_html($status['label']); ?></span></td>
                                <td data-label="Thao tác" class="kmm-actions">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=kmm-promotions&edit=' . $p['id'])); ?>" class="kmm-icon-btn" title="Sửa">
                                        <span class="dashicons dashicons-edit"></span>
                                    </a>
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=kmm_toggle_promotion&id=' . $p['id']), 'kmm_toggle_promotion_action', 'kmm_nonce')); ?>" class="kmm-icon-btn" title="<?php echo $p['active'] ? 'Tắt' : 'Bật'; ?>">
                                        <span class="dashicons <?php echo $p['active'] ? 'dashicons-hidden' : 'dashicons-visibility'; ?>"></span>
                                    </a>
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=kmm_delete_promotion&id=' . $p['id']), 'kmm_delete_promotion_action', 'kmm_nonce')); ?>" class="kmm-icon-btn kmm-icon-danger kmm-delete-link" title="Xóa">
                                        <span class="dashicons dashicons-trash"></span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>
