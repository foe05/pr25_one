<?php
/**
 * Table template for displaying Abschussmeldungen
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

<div class="abschuss-table-container">
    <h2 class="mb-4"><?php echo esc_html__('Abschussmeldungen', 'custom-form-display'); ?></h2>

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
                        <th scope="col"><?php echo esc_html__('Erstellt am', 'custom-form-display'); ?></th>
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
                            <td data-label="<?php echo esc_attr__('WUS', 'custom-form-display'); ?>">
                                <?php echo !empty($submission['field3']) ? esc_html($submission['field3']) : '-'; ?>
                            </td>
                            <td data-label="<?php echo esc_attr__('Bemerkung', 'custom-form-display'); ?>">
                                <?php echo !empty($submission['field4']) ? esc_html($submission['field4']) : '-'; ?>
                            </td>
                            <td data-label="<?php echo esc_attr__('Erstellt am', 'custom-form-display'); ?>">
                                <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($submission['created_at']))); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_count > $current_limit) : ?>
            <div class="page-count">
                <?php
                echo sprintf(
                    esc_html__('Zeige %1$d bis %2$d von %3$d Einträgen', 'custom-form-display'),
                    ($current_page - 1) * $current_limit + 1,
                    min($current_page * $current_limit, $total_count),
                    $total_count
                );
                ?>
            </div>
            
            <!-- Pagination -->
            <nav aria-label="<?php echo esc_attr__('Abschussmeldungen Navigation', 'custom-form-display'); ?>">
                <ul class="pagination justify-content-center">
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
                    // Show page numbers
                    $range = 2; // Display 2 pages on either side of current page
                    $start = max(1, $current_page - $range);
                    $end = min($total_pages, $current_page + $range);
                    
                    // Show link to first page if not in range
                    if ($start > 1) :
                    ?>
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
                        <li class="page-item <?php echo $i === $current_page ? 'active' : ''; ?>">
                            <?php if ($i === $current_page) : ?>
                                <span class="page-link"><?php echo esc_html($i); ?></span>
                            <?php else : ?>
                                <a class="page-link" href="<?php echo esc_url(add_query_arg(array('abschuss_page' => $i, 'abschuss_limit' => $current_limit))); ?>">
                                    <?php echo esc_html($i); ?>
                                </a>
                            <?php endif; ?>
                        </li>
                    <?php endfor; ?>
                    
                    <?php
                    // Show link to last page if not in range
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
</div>
