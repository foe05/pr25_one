<?php
/**
 * Admin View: Page Views Statistics
 *
 * @var array $summary Summary statistics
 * @var array $page_views List of page views
 * @var int $total_count Total number of records
 * @var int $total_pages Total pages for pagination
 * @var int $page Current page number
 * @var array $filters Active filters
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Seitenaufrufe - Statistiken', 'abschussplan-hgmh'); ?></h1>

    <!-- Summary Cards -->
    <div class="ahgmh-stats-cards" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
        <div class="ahgmh-stat-card" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; font-weight: normal;"><?php _e('Gesamt Aufrufe', 'abschussplan-hgmh'); ?></h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #2271b1;"><?php echo number_format($summary['total_views'], 0, ',', '.'); ?></p>
        </div>

        <div class="ahgmh-stat-card" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; font-weight: normal;"><?php _e('Angemeldete Benutzer', 'abschussplan-hgmh'); ?></h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #46b450;"><?php echo number_format($summary['unique_users'], 0, ',', '.'); ?></p>
        </div>

        <div class="ahgmh-stat-card" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; font-weight: normal;"><?php _e('Authentifizierte Aufrufe', 'abschussplan-hgmh'); ?></h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #00a0d2;"><?php echo number_format($summary['authenticated_views'], 0, ',', '.'); ?></p>
        </div>

        <div class="ahgmh-stat-card" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px; font-weight: normal;"><?php _e('Anonyme Aufrufe', 'abschussplan-hgmh'); ?></h3>
            <p style="margin: 0; font-size: 32px; font-weight: bold; color: #f56e28;"><?php echo number_format($summary['anonymous_views'], 0, ',', '.'); ?></p>
        </div>
    </div>

    <!-- Filters -->
    <div class="ahgmh-filters" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin: 20px 0;">
        <h2><?php _e('Filter', 'abschussplan-hgmh'); ?></h2>
        <form method="get" action="">
            <input type="hidden" name="page" value="abschussplan-hgmh-page-views">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <label for="shortcode_name"><?php _e('Shortcode', 'abschussplan-hgmh'); ?></label>
                    <select name="shortcode_name" id="shortcode_name" class="regular-text">
                        <option value=""><?php _e('Alle', 'abschussplan-hgmh'); ?></option>
                        <?php foreach ($summary['views_by_shortcode'] as $shortcode_stat): ?>
                            <option value="<?php echo esc_attr($shortcode_stat['shortcode_name']); ?>" <?php selected(!empty($filters['shortcode_name']) && $filters['shortcode_name'] === $shortcode_stat['shortcode_name']); ?>>
                                <?php echo esc_html($shortcode_stat['shortcode_name']); ?> (<?php echo $shortcode_stat['count']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="date_from"><?php _e('Von Datum', 'abschussplan-hgmh'); ?></label>
                    <input type="date" name="date_from" id="date_from" class="regular-text" value="<?php echo esc_attr($filters['date_from'] ?? ''); ?>">
                </div>

                <div>
                    <label for="date_to"><?php _e('Bis Datum', 'abschussplan-hgmh'); ?></label>
                    <input type="date" name="date_to" id="date_to" class="regular-text" value="<?php echo esc_attr($filters['date_to'] ?? ''); ?>">
                </div>
            </div>

            <p class="submit">
                <button type="submit" class="button button-primary"><?php _e('Filtern', 'abschussplan-hgmh'); ?></button>
                <a href="<?php echo admin_url('admin.php?page=abschussplan-hgmh-page-views'); ?>" class="button"><?php _e('Zurücksetzen', 'abschussplan-hgmh'); ?></a>
            </p>
        </form>
    </div>

    <!-- Charts Section -->
    <div class="ahgmh-charts" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin: 20px 0;">
        <!-- Views by Shortcode -->
        <div class="ahgmh-chart-card" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px;">
            <h3><?php _e('Aufrufe nach Shortcode', 'abschussplan-hgmh'); ?></h3>
            <table class="widefat" style="margin-top: 15px;">
                <thead>
                    <tr>
                        <th><?php _e('Shortcode', 'abschussplan-hgmh'); ?></th>
                        <th style="text-align: right;"><?php _e('Anzahl', 'abschussplan-hgmh'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($summary['views_by_shortcode'] as $shortcode_stat): ?>
                        <tr>
                            <td><?php echo esc_html($shortcode_stat['shortcode_name']); ?></td>
                            <td style="text-align: right;"><?php echo number_format($shortcode_stat['count'], 0, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Top Users -->
        <div class="ahgmh-chart-card" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px;">
            <h3><?php _e('Top Benutzer', 'abschussplan-hgmh'); ?></h3>
            <table class="widefat" style="margin-top: 15px;">
                <thead>
                    <tr>
                        <th><?php _e('Benutzer', 'abschussplan-hgmh'); ?></th>
                        <th style="text-align: right;"><?php _e('Anzahl', 'abschussplan-hgmh'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($summary['top_users'])): ?>
                        <tr>
                            <td colspan="2" style="text-align: center; color: #666;"><?php _e('Keine angemeldeten Benutzer', 'abschussplan-hgmh'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($summary['top_users'] as $user_stat): ?>
                            <tr>
                                <td><?php echo esc_html($user_stat['user_display_name']); ?></td>
                                <td style="text-align: right;"><?php echo number_format($user_stat['count'], 0, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Views by Day -->
    <div class="ahgmh-daily-views" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin: 20px 0;">
        <h3><?php _e('Aufrufe nach Tag (letzte 30 Tage)', 'abschussplan-hgmh'); ?></h3>
        <table class="widefat" style="margin-top: 15px;">
            <thead>
                <tr>
                    <th><?php _e('Datum', 'abschussplan-hgmh'); ?></th>
                    <th style="text-align: right;"><?php _e('Anzahl', 'abschussplan-hgmh'); ?></th>
                    <th style="width: 50%;"><?php _e('Visualisierung', 'abschussplan-hgmh'); ?></th>
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
                        <td style="text-align: right;"><?php echo number_format($day_stat['count'], 0, ',', '.'); ?></td>
                        <td>
                            <div style="background: #f0f0f0; height: 20px; border-radius: 4px; overflow: hidden;">
                                <div style="background: #2271b1; height: 100%; width: <?php echo $percentage; ?>%; transition: width 0.3s;"></div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Action Buttons -->
    <div class="ahgmh-actions" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin: 20px 0;">
        <h3><?php _e('Aktionen', 'abschussplan-hgmh'); ?></h3>

        <p>
            <a href="<?php echo admin_url('admin-ajax.php?action=ahgmh_export_page_views' . (!empty($filters['shortcode_name']) ? '&shortcode_name=' . urlencode($filters['shortcode_name']) : '') . (!empty($filters['date_from']) ? '&date_from=' . urlencode($filters['date_from']) : '') . (!empty($filters['date_to']) ? '&date_to=' . urlencode($filters['date_to']) : '')); ?>" class="button button-primary">
                <span class="dashicons dashicons-download" style="margin-top: 3px;"></span> <?php _e('Als CSV exportieren', 'abschussplan-hgmh'); ?>
            </a>
        </p>

        <h4><?php _e('Datenverwaltung', 'abschussplan-hgmh'); ?></h4>
        <p>
            <button type="button" class="button" id="cleanup-logs-btn">
                <span class="dashicons dashicons-trash" style="margin-top: 3px;"></span> <?php _e('Alte Einträge löschen (älter als 90 Tage)', 'abschussplan-hgmh'); ?>
            </button>

            <button type="button" class="button" id="delete-all-logs-btn" style="margin-left: 10px; color: #b32d2e;">
                <span class="dashicons dashicons-warning" style="margin-top: 3px;"></span> <?php _e('Alle Einträge löschen', 'abschussplan-hgmh'); ?>
            </button>
        </p>
    </div>

    <!-- Settings -->
    <div class="ahgmh-settings" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin: 20px 0;">
        <h3><?php _e('Einstellungen', 'abschussplan-hgmh'); ?></h3>

        <form id="page-views-settings-form">
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('IP-Adressen speichern', 'abschussplan-hgmh'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="log_ip_addresses" value="1" <?php checked($log_ip_addresses); ?>>
                            <?php _e('IP-Adressen von Besuchern protokollieren', 'abschussplan-hgmh'); ?>
                        </label>
                        <p class="description"><?php _e('Wenn deaktiviert, werden keine IP-Adressen gespeichert.', 'abschussplan-hgmh'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('IP-Adressen anonymisieren', 'abschussplan-hgmh'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="anonymize_ip" value="1" <?php checked($anonymize_ip); ?>>
                            <?php _e('IP-Adressen anonymisieren (DSGVO-konform)', 'abschussplan-hgmh'); ?>
                        </label>
                        <p class="description"><?php _e('Entfernt das letzte Oktett von IPv4-Adressen (z.B. 192.168.1.0)', 'abschussplan-hgmh'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Automatische Bereinigung', 'abschussplan-hgmh'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="auto_cleanup_enabled" value="1" <?php checked($auto_cleanup_enabled); ?>>
                            <?php _e('Alte Einträge automatisch löschen', 'abschussplan-hgmh'); ?>
                        </label>
                        <p class="description"><?php _e('Löscht Einträge automatisch nach einer bestimmten Zeit.', 'abschussplan-hgmh'); ?></p>

                        <label style="margin-top: 10px; display: block;">
                            <?php _e('Aufbewahrungsdauer (Tage):', 'abschussplan-hgmh'); ?>
                            <input type="number" name="auto_cleanup_days" value="<?php echo esc_attr($auto_cleanup_days); ?>" min="1" max="365" style="width: 100px;">
                        </label>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary"><?php _e('Einstellungen speichern', 'abschussplan-hgmh'); ?></button>
            </p>
        </form>
    </div>

    <!-- Detailed Log Table -->
    <div class="ahgmh-log-table" style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin: 20px 0;">
        <h3><?php _e('Detaillierte Aufrufliste', 'abschussplan-hgmh'); ?></h3>

        <table class="widefat striped" style="margin-top: 15px;">
            <thead>
                <tr>
                    <th><?php _e('Datum/Zeit', 'abschussplan-hgmh'); ?></th>
                    <th><?php _e('Shortcode', 'abschussplan-hgmh'); ?></th>
                    <th><?php _e('Benutzer', 'abschussplan-hgmh'); ?></th>
                    <th><?php _e('Parameter', 'abschussplan-hgmh'); ?></th>
                    <th><?php _e('Seiten-URL', 'abschussplan-hgmh'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($page_views)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px; color: #666;">
                            <?php _e('Keine Einträge gefunden', 'abschussplan-hgmh'); ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($page_views as $view):
                        $attributes = json_decode($view['shortcode_attributes'], true);
                        $user_display = $view['user_id'] ? esc_html($view['user_display_name']) : __('Anonym', 'abschussplan-hgmh');
                    ?>
                        <tr>
                            <td><?php echo esc_html($view['created_at']); ?></td>
                            <td><code><?php echo esc_html($view['shortcode_name']); ?></code></td>
                            <td><?php echo $user_display; ?></td>
                            <td>
                                <?php if (!empty($attributes)): ?>
                                    <small style="color: #666;">
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
                                    <small style="color: #999;">-</small>
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
            <div class="tablenav" style="margin-top: 20px;">
                <div class="tablenav-pages">
                    <span class="displaying-num"><?php printf(__('%s Einträge', 'abschussplan-hgmh'), number_format($total_count, 0, ',', '.')); ?></span>
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
                        'next_text' => '&raquo;'
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
        if (!confirm('<?php _e('Möchten Sie wirklich alle Einträge löschen, die älter als 90 Tage sind?', 'abschussplan-hgmh'); ?>')) {
            return;
        }

        $.post(ajaxurl, {
            action: 'ahgmh_cleanup_page_views',
            days: 90,
            nonce: '<?php echo wp_create_nonce('ahgmh_page_views_nonce'); ?>'
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
        if (!confirm('<?php _e('ACHTUNG: Möchten Sie wirklich ALLE Einträge unwiderruflich löschen?', 'abschussplan-hgmh'); ?>')) {
            return;
        }

        $.post(ajaxurl, {
            action: 'ahgmh_delete_all_page_views',
            nonce: '<?php echo wp_create_nonce('ahgmh_page_views_nonce'); ?>'
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
        formData.push({name: 'nonce', value: '<?php echo wp_create_nonce('ahgmh_page_views_nonce'); ?>'});

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
