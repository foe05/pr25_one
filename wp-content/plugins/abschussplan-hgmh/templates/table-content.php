<?php
/**
 * Table content template for AJAX refresh
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get current page from URL or use the one from shortcode attributes
$current_page = isset($_GET['abschuss_page']) ? max(1, intval($_GET['abschuss_page'])) : $page;

// Get limit from URL or use the one from shortcode attributes
$current_limit = isset($_GET['abschuss_limit']) ? max(1, intval($_GET['abschuss_limit'])) : $limit;
?>

<?php if (empty($submissions)) : ?>
    <div class="abschuss-empty">
        <p><?php echo esc_html__('Keine Abschussmeldungen vorhanden.', 'custom-form-display'); ?></p>
    </div>
<?php else : ?>
    <div class="table-responsive">
        <table class="table table-striped abschuss-table">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col"><?php echo esc_html__('Abschussdatum', 'custom-form-display'); ?></th>
                    <th scope="col"><?php echo esc_html__('Abschuss', 'custom-form-display'); ?></th>
                    <th scope="col"><?php echo esc_html__('WUS', 'custom-form-display'); ?></th>
                    <th scope="col"><?php echo esc_html__('Bemerkung', 'custom-form-display'); ?></th>
                    <th scope="col"><?php echo esc_html__('Erstellt von', 'abschussplan-hgmh'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($submissions as $submission) : ?>
                    <tr>
                        <td data-label="<?php echo esc_attr__('#', 'custom-form-display'); ?>"><?php echo esc_html($submission['id']); ?></td>
                        <td data-label="<?php echo esc_attr__('Abschussdatum', 'custom-form-display'); ?>">
                            <?php 
                            // Format the date in German format if possible
                            if (!empty($submission['field1'])) {
                                echo esc_html(date_i18n(get_option('date_format'), strtotime($submission['field1'])));
                            }
                            ?>
                        </td>
                        <td data-label="<?php echo esc_attr__('Abschuss', 'custom-form-display'); ?>"><?php echo esc_html($submission['field2']); ?></td>
                        <td data-label="<?php echo esc_attr__('WUS', 'custom-form-display'); ?>"><?php echo esc_html($submission['field3']); ?></td>
                        <td data-label="<?php echo esc_attr__('Bemerkung', 'custom-form-display'); ?>"><?php echo esc_html($submission['field4']); ?></td>
                        <td data-label="<?php echo esc_attr__('Erstellt von', 'abschussplan-hgmh'); ?>">
                            <?php 
                            if (isset($submission['user_id']) && $submission['user_id'] > 0) {
                                $user = get_user_by('id', $submission['user_id']);
                                if ($user) {
                                    $first_name = get_user_meta($user->ID, 'first_name', true);
                                    $last_name = get_user_meta($user->ID, 'last_name', true);
                                    
                                    // If both first and last name are available, use them
                                    if (!empty($first_name) && !empty($last_name)) {
                                        echo esc_html(trim($first_name . ' ' . $last_name));
                                    } 
                                    // If only one name is available, use it
                                    elseif (!empty($first_name)) {
                                        echo esc_html($first_name);
                                    }
                                    elseif (!empty($last_name)) {
                                        echo esc_html($last_name);
                                    }
                                    // Fall back to display name
                                    else {
                                        echo esc_html($user->display_name);
                                    }
                                } else {
                                    echo esc_html__('Unbekannter Benutzer', 'abschussplan-hgmh');
                                }
                            } else {
                                // Fallback for old entries with manual names or when user_id is 0
                                if (isset($submission['prename']) || isset($submission['name'])) {
                                    echo esc_html(trim(($submission['prename'] ?? '') . ' ' . ($submission['name'] ?? '')));
                                } else {
                                    echo esc_html__('Kein Name verfügbar', 'abschussplan-hgmh');
                                }
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($total_pages > 1) : ?>
        <nav aria-label="<?php echo esc_attr__('Abschussmeldungen Navigation', 'custom-form-display'); ?>">
            <ul class="pagination">
                <?php if ($current_page > 1) : ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo esc_url(add_query_arg(array('abschuss_page' => $current_page - 1, 'abschuss_limit' => $current_limit))); ?>">
                            <?php echo esc_html__('&laquo; Zurück', 'custom-form-display'); ?>
                        </a>
                    </li>
                <?php else : ?>
                    <li class="page-item disabled">
                        <span class="page-link"><?php echo esc_html__('&laquo; Zurück', 'custom-form-display'); ?></span>
                    </li>
                <?php endif; ?>
                
                <?php 
                // Calculate start and end page numbers for pagination
                $start = max(1, $current_page - 2);
                $end = min($total_pages, $current_page + 2);
                
                // Show first page if we're not close to the beginning
                if ($start > 1) : ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo esc_url(add_query_arg(array('abschuss_page' => 1, 'abschuss_limit' => $current_limit))); ?>">1</a>
                    </li>
                    <?php if ($start > 2) : ?>
                        <li class="page-item disabled">
                            <span class="page-link">&hellip;</span>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php for ($i = $start; $i <= $end; $i++) : ?>
                    <?php if ($i == $current_page) : ?>
                        <li class="page-item active">
                            <span class="page-link"><?php echo esc_html($i); ?></span>
                        </li>
                    <?php else : ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo esc_url(add_query_arg(array('abschuss_page' => $i, 'abschuss_limit' => $current_limit))); ?>">
                                <?php echo esc_html($i); ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php 
                // Show last page if we're not close to the end
                if ($end < $total_pages) : 
                ?>
                    <?php if ($end < $total_pages - 1) : ?>
                        <li class="page-item disabled">
                            <span class="page-link">&hellip;</span>
                        </li>
                    <?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo esc_url(add_query_arg(array('abschuss_page' => $total_pages, 'abschuss_limit' => $current_limit))); ?>">
                            <?php echo esc_html($total_pages); ?>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php if ($current_page < $total_pages) : ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo esc_url(add_query_arg(array('abschuss_page' => $current_page + 1, 'abschuss_limit' => $current_limit))); ?>">
                            <?php echo esc_html__('Weiter &raquo;', 'custom-form-display'); ?>
                        </a>
                    </li>
                <?php else : ?>
                    <li class="page-item disabled">
                        <span class="page-link"><?php echo esc_html__('Weiter &raquo;', 'custom-form-display'); ?></span>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>