<?php
/**
 * Admin View: Page Views Statistics
 *
 * Uses WordPress-native postbox patterns with proper i18n escaping.
 *
 * @var array $summary Summary statistics
 * @var array $page_views List of page views
 * @var int $total_count Total number of records
 * @var int $total_pages Total pages for pagination
 * @var int $page Current page number
 * @var array $filters Active filters
 * @var bool $log_ip_addresses Whether IP logging is enabled
 * @var bool $anonymize_ip Whether IP anonymisation is enabled
 * @var bool $auto_cleanup_enabled Whether auto-cleanup is enabled
 * @var int $auto_cleanup_days Retention period in days
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- Summary Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 20px;">
    <div class="postbox" style="margin: 0; padding: 15px;">
        <div style="font-size: 13px; color: #646970; margin-bottom: 4px;"><?php echo esc_html__('Gesamt Aufrufe', 'abschussplan-hgmh'); ?></div>
        <div style="font-size: 28px; font-weight: 600; color: #2271b1;"><?php echo esc_html(number_format($summary['total_views'], 0, ',', '.')); ?></div>
    </div>
    <div class="postbox" style="margin: 0; padding: 15px;">
        <div style="font-size: 13px; color: #646970; margin-bottom: 4px;"><?php echo esc_html__('Angemeldete Benutzer', 'abschussplan-hgmh'); ?></div>
        <div style="font-size: 28px; font-weight: 600; color: #00a32a;"><?php echo esc_html(number_format($summary['unique_users'], 0, ',', '.')); ?></div>
    </div>
    <div class="postbox" style="margin: 0; padding: 15px;">
        <div style="font-size: 13px; color: #646970; margin-bottom: 4px;"><?php echo esc_html__('Authentifizierte Aufrufe', 'abschussplan-hgmh'); ?></div>
        <div style="font-size: 28px; font-weight: 600; color: #2271b1;"><?php echo esc_html(number_format($summary['authenticated_views'], 0, ',', '.')); ?></div>
    </div>
    <div class="postbox" style="margin: 0; padding: 15px;">
        <div style="font-size: 13px; color: #646970; margin-bottom: 4px;"><?php echo esc_html__('Anonyme Aufrufe', 'abschussplan-hgmh'); ?></div>
        <div style="font-size: 28px; font-weight: 600; color: #d63638;"><?php echo esc_html(number_format($summary['anonymous_views'], 0, ',', '.')); ?></div>
    </div>
</div>

<!-- Filters -->
<div class="postbox" style="margin-bottom: 20px;">
    <div class="postbox-header">
        <h2 class="hndle"><?php echo esc_html__('Filter', 'abschussplan-hgmh'); ?></h2>
    </div>
    <div class="inside">
        <form method="get" action="">
            <input type="hidden" name="page" value="abschussplan-hgmh-page-views">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <label for="shortcode_name" style="display: block; margin-bottom: 4px; font-weight: 600;">
                        <?php echo esc_html__('Shortcode', 'abschussplan-hgmh'); ?>
                    </label>
                    <select name="shortcode_name" id="shortcode_name" class="regular-text" style="width: 100%;">
                        <option value=""><?php echo esc_html__('Alle', 'abschussplan-hgmh'); ?></option>
                        <?php foreach ($summary['views_by_shortcode'] as $shortcode_stat): ?>
                            <option value="<?php echo esc_attr($shortcode_stat['shortcode_name']); ?>" <?php selected(!empty($filters['shortcode_name']) && $filters['shortcode_name'] === $shortcode_stat['shortcode_name']); ?>>
                                <?php echo esc_html($shortcode_stat['shortcode_name']); ?> (<?php echo esc_html($shortcode_stat['count']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="date_from" style="display: block; margin-bottom: 4px; font-weight: 600;">
                        <?php echo esc_html__('Von Datum', 'abschussplan-hgmh'); ?>
                    </label>
                    <input type="date" name="date_from" id="date_from" class="regular-text" value="<?php echo esc_attr($filters['date_from'] ?? ''); ?>">
                </div>
                <div>
                    <label for="date_to" style="display: block; margin-bottom: 4px; font-weight: 600;">
                        <?php echo esc_html__('Bis Datum', 'abschussplan-hgmh'); ?>
                    </label>
                    <input type="date" name="date_to" id="date_to" class="regular-text" value="<?php echo esc_attr($filters['date_to'] ?? ''); ?>">
                </div>
            </div>

            <p class="submit">
                <button type="submit" class="button button-primary"><?php echo esc_html__('Filtern', 'abschussplan-hgmh'); ?></button>
                <a href="<?php echo esc_url(admin_url('admin.php?page=abschussplan-hgmh-page-views')); ?>" class="button"><?php echo esc_html__('Zuruecksetzen', 'abschussplan-hgmh'); ?></a>
            </p>
        </form>
    </div>
</div>

<!-- Charts Section -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); gap: 20px; margin-bottom: 20px;">
    <!-- Views by Shortcode -->
    <div class="postbox" style="margin: 0;">
        <div class="postbox-header">
            <h2 class="hndle"><?php echo esc_html__('Aufrufe nach Shortcode', 'abschussplan-hgmh'); ?></h2>
        </div>
        <div class="inside">
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Shortcode', 'abschussplan-hgmh'); ?></th>
                        <th style="text-align: right;"><?php echo esc_html__('Anzahl', 'abschussplan-hgmh'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($summary['views_by_shortcode'] as $shortcode_stat): ?>
                        <tr>
                            <td><code><?php echo esc_html($shortcode_stat['shortcode_name']); ?></code></td>
                            <td style="text-align: right; font-weight: 600;"><?php echo esc_html(number_format($shortcode_stat['count'], 0, ',', '.')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Users -->
    <div class="postbox" style="margin: 0;">
        <div class="postbox-header">
            <h2 class="hndle"><?php echo esc_html__('Top Benutzer', 'abschussplan-hgmh'); ?></h2>
        </div>
        <div class="inside">
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Benutzer', 'abschussplan-hgmh'); ?></th>
                        <th style="text-align: right;"><?php echo esc_html__('Anzahl', 'abschussplan-hgmh'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($summary['top_users'])): ?>
                        <tr>
                            <td colspan="2" style="text-align: center; color: #646970;"><?php echo esc_html__('Keine angemeldeten Benutzer', 'abschussplan-hgmh'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($summary['top_users'] as $user_stat): ?>
                            <tr>
                                <td><?php echo esc_html($user_stat['user_display_name']); ?></td>
                                <td style="text-align: right; font-weight: 600;"><?php echo esc_html(number_format($user_stat['count'], 0, ',', '.')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Views by Day -->
<div class="postbox" style="margin-bottom: 20px;">
    <div class="postbox-header">
        <h2 class="hndle"><?php echo esc_html__('Aufrufe nach Tag (letzte 30 Tage)', 'abschussplan-hgmh'); ?></h2>
    </div>
    <div class="inside">
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Datum', 'abschussplan-hgmh'); ?></th>
                    <th style="text-align: right;"><?php echo esc_html__('Anzahl', 'abschussplan-hgmh'); ?></th>
                    <th style="width: 50%;"><?php echo esc_html__('Visualisierung', 'abschussplan-hgmh'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $max_views = !empty($summary['views_by_day']) ? max(array_column($summary['views_by_day'], 'count')) : 1;
                foreach ($summary['views_by_day'] as $day_stat):
                    $percentage = ($day_stat['count'] / $max_views) * 100;
                ?>
                    <tr>
                        <td><?php echo esc_html($day_stat['date']); ?></td>
                        <td style="text-align: right; font-weight: 600;"><?php echo esc_html(number_format($day_stat['count'], 0, ',', '.')); ?></td>
                        <td>
                            <div style="background: #f0f0f1; height: 18px; border-radius: 3px; overflow: hidden;">
                                <div style="background: #2271b1; height: 100%; width: <?php echo esc_attr($percentage); ?>%;"></div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Actions -->
<div class="postbox" style="margin-bottom: 20px;">
    <div class="postbox-header">
        <h2 class="hndle"><?php echo esc_html__('Aktionen', 'abschussplan-hgmh'); ?></h2>
    </div>
    <div class="inside">
        <p>
            <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=ahgmh_export_page_views' . (!empty($filters['shortcode_name']) ? '&shortcode_name=' . urlencode($filters['shortcode_name']) : '') . (!empty($filters['date_from']) ? '&date_from=' . urlencode($filters['date_from']) : '') . (!empty($filters['date_to']) ? '&date_to=' . urlencode($filters['date_to']) : ''))); ?>" class="button button-primary">
                <span class="dashicons dashicons-download" style="margin-top: 4px;"></span>
                <?php echo esc_html__('Als CSV exportieren', 'abschussplan-hgmh'); ?>
            </a>
        </p>

        <h4><?php echo esc_html__('Datenverwaltung', 'abschussplan-hgmh'); ?></h4>
        <p>
            <button type="button" class="button" id="cleanup-logs-btn">
                <span class="dashicons dashicons-trash" style="margin-top: 4px;"></span>
                <?php echo esc_html__('Alte Eintraege loeschen (aelter als 90 Tage)', 'abschussplan-hgmh'); ?>
            </button>

            <button type="button" class="button" id="delete-all-logs-btn" style="margin-left: 10px; color: #b32d2e;">
                <span class="dashicons dashicons-warning" style="margin-top: 4px;"></span>
                <?php echo esc_html__('Alle Eintraege loeschen', 'abschussplan-hgmh'); ?>
            </button>
        </p>
    </div>
</div>

<!-- Settings -->
<div class="postbox" style="margin-bottom: 20px;">
    <div class="postbox-header">
        <h2 class="hndle"><?php echo esc_html__('Einstellungen', 'abschussplan-hgmh'); ?></h2>
    </div>
    <div class="inside">
        <form id="page-views-settings-form">
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php echo esc_html__('IP-Adressen speichern', 'abschussplan-hgmh'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="log_ip_addresses" value="1" <?php checked($log_ip_addresses); ?>>
                            <?php echo esc_html__('IP-Adressen von Besuchern protokollieren', 'abschussplan-hgmh'); ?>
                        </label>
                        <p class="description"><?php echo esc_html__('Wenn deaktiviert, werden keine IP-Adressen gespeichert.', 'abschussplan-hgmh'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__('IP-Adressen anonymisieren', 'abschussplan-hgmh'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="anonymize_ip" value="1" <?php checked($anonymize_ip); ?>>
                            <?php echo esc_html__('IP-Adressen anonymisieren (DSGVO-konform)', 'abschussplan-hgmh'); ?>
                        </label>
                        <p class="description"><?php echo esc_html__('Entfernt das letzte Oktett von IPv4-Adressen (z.B. 192.168.1.0)', 'abschussplan-hgmh'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__('Automatische Bereinigung', 'abschussplan-hgmh'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="auto_cleanup_enabled" value="1" <?php checked($auto_cleanup_enabled); ?>>
                            <?php echo esc_html__('Alte Eintraege automatisch loeschen', 'abschussplan-hgmh'); ?>
                        </label>
                        <p class="description"><?php echo esc_html__('Loescht Eintraege automatisch nach einer bestimmten Zeit.', 'abschussplan-hgmh'); ?></p>

                        <label style="margin-top: 10px; display: block;">
                            <?php echo esc_html__('Aufbewahrungsdauer (Tage):', 'abschussplan-hgmh'); ?>
                            <input type="number" name="auto_cleanup_days" value="<?php echo esc_attr($auto_cleanup_days); ?>" min="1" max="365" class="small-text">
                        </label>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary"><?php echo esc_html__('Einstellungen speichern', 'abschussplan-hgmh'); ?></button>
            </p>
        </form>
    </div>
</div>

<!-- Detailed Log Table -->
<div class="postbox" style="margin-bottom: 20px;">
    <div class="postbox-header">
        <h2 class="hndle"><?php echo esc_html__('Detaillierte Aufrufliste', 'abschussplan-hgmh'); ?></h2>
    </div>
    <div class="inside">
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Datum/Zeit', 'abschussplan-hgmh'); ?></th>
                    <th><?php echo esc_html__('Shortcode', 'abschussplan-hgmh'); ?></th>
                    <th><?php echo esc_html__('Benutzer', 'abschussplan-hgmh'); ?></th>
                    <th><?php echo esc_html__('Parameter', 'abschussplan-hgmh'); ?></th>
                    <th><?php echo esc_html__('Seiten-URL', 'abschussplan-hgmh'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($page_views)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px; color: #646970;">
                            <?php echo esc_html__('Keine Eintraege gefunden', 'abschussplan-hgmh'); ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($page_views as $view):
                        $attributes = json_decode($view['shortcode_attributes'], true);
                        $user_display = $view['user_id'] ? esc_html($view['user_display_name']) : esc_html__('Anonym', 'abschussplan-hgmh');
                    ?>
                        <tr>
                            <td><?php echo esc_html($view['created_at']); ?></td>
                            <td><code><?php echo esc_html($view['shortcode_name']); ?></code></td>
                            <td><?php echo $user_display; ?></td>
                            <td>
                                <?php if (!empty($attributes)): ?>
                                    <small style="color: #646970;">
                                        <?php
                                        $params = array();
                                        foreach ($attributes as $key => $value) {
                                            if (!empty($value)) {
                                                $params[] = esc_html($key) . '="' . esc_html($value) . '"';
                                            }
                                        }
                                        echo implode(', ', $params);
                                        ?>
                                    </small>
                                <?php else: ?>
                                    <small style="color: #c3c4c7;">-</small>
                                <?php endif; ?>
                            </td>
                            <td><small><?php echo esc_html($view['page_url']); ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="tablenav" style="margin-top: 15px;">
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php
                        /* translators: %s: number of entries */
                        echo esc_html(sprintf(__('%s Eintraege', 'abschussplan-hgmh'), number_format($total_count, 0, ',', '.')));
                        ?>
                    </span>
                    <?php
                    $base_url = add_query_arg(array_merge(
                        array('page' => 'abschussplan-hgmh-page-views'),
                        $filters
                    ), admin_url('admin.php'));

                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%', $base_url),
                        'format' => '',
                        'current' => $page,
                        'total' => $total_pages,
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                    ));
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Cleanup old logs
    $('#cleanup-logs-btn').on('click', function() {
        if (!confirm('<?php echo esc_js(__('Moechten Sie wirklich alle Eintraege loeschen, die aelter als 90 Tage sind?', 'abschussplan-hgmh')); ?>')) {
            return;
        }

        $.post(ajaxurl, {
            action: 'ahgmh_cleanup_page_views',
            days: 90,
            nonce: '<?php echo esc_js(wp_create_nonce('ahgmh_page_views_nonce')); ?>'
        }, function(response) {
            if (response.success) {
                alert(response.data.message);
                location.reload();
            } else {
                alert(response.data.message);
            }
        });
    });

    // Delete all logs
    $('#delete-all-logs-btn').on('click', function() {
        if (!confirm('<?php echo esc_js(__('ACHTUNG: Moechten Sie wirklich ALLE Eintraege unwiderruflich loeschen?', 'abschussplan-hgmh')); ?>')) {
            return;
        }

        $.post(ajaxurl, {
            action: 'ahgmh_delete_all_page_views',
            nonce: '<?php echo esc_js(wp_create_nonce('ahgmh_page_views_nonce')); ?>'
        }, function(response) {
            if (response.success) {
                alert(response.data.message);
                location.reload();
            } else {
                alert(response.data.message);
            }
        });
    });

    // Save settings
    $('#page-views-settings-form').on('submit', function(e) {
        e.preventDefault();

        var formData = $(this).serializeArray();
        formData.push({name: 'action', value: 'ahgmh_save_page_view_settings'});
        formData.push({name: 'nonce', value: '<?php echo esc_js(wp_create_nonce('ahgmh_page_views_nonce')); ?>'});

        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                alert(response.data.message);
            } else {
                alert(response.data.message);
            }
        });
    });
});
</script>
