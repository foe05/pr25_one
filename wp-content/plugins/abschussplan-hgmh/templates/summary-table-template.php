<?php
/**
 * Summary Table template for displaying Abschussmeldungen (public access with reduced columns)
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get current page from URL or use the one from shortcode attributes
$current_page = isset($_GET['abschuss_page']) ? max(1, intval($_GET['abschuss_page'])) : (isset($page) ? $page : 1);

// Get limit from URL or use the one from shortcode attributes
$current_limit = isset($_GET['abschuss_limit']) ? max(1, intval($_GET['abschuss_limit'])) : (isset($limit) ? $limit : 10);
?>

<div class="abschuss-table-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><?php echo esc_html__('Abschussmeldungen', 'abschussplan-hgmh'); ?></h2>
    </div>

    <?php if (empty($submissions)) : ?>
        <div class="abschuss-empty" role="status">
            <div class="abschuss-empty-icon">
                <i class="bi bi-inbox" aria-hidden="true"></i>
            </div>
            <h3 class="abschuss-empty-heading">
                <?php echo esc_html__('Keine Abschussmeldungen vorhanden', 'abschussplan-hgmh'); ?>
            </h3>
            <p class="abschuss-empty-description">
                <?php echo esc_html__('Es wurden noch keine Meldungen für diese Wildart erfasst. Melden Sie Ihren ersten Abschuss, um die Übersicht zu starten.', 'abschussplan-hgmh'); ?>
            </p>
            <?php if (is_user_logged_in()) : ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=abschussplan-hgmh')); ?>" class="abschuss-empty-cta">
                    <i class="bi bi-plus-circle" aria-hidden="true"></i>
                    <?php echo esc_html__('Erste Meldung erfassen', 'abschussplan-hgmh'); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php else : ?>
        <div class="table-responsive">
            <table class="table table-striped abschuss-table">
                <caption class="visually-hidden"><?php echo esc_html__('Zusammenfassung der Abschussmeldungen', 'abschussplan-hgmh'); ?></caption>
                <thead>
                    <tr>
                        <th scope="col"><?php echo esc_html__('Abschussdatum', 'abschussplan-hgmh'); ?></th>
                        <th scope="col"><?php echo esc_html__('Meldegruppe', 'abschussplan-hgmh'); ?></th>
                        <th scope="col"><?php echo esc_html__('Jagdbezirk', 'abschussplan-hgmh'); ?></th>
                        <th scope="col"><?php echo esc_html__('Abschuss', 'abschussplan-hgmh'); ?></th>
                        <th scope="col"><?php echo esc_html__('Bemerkung', 'abschussplan-hgmh'); ?></th>
                        <th scope="col"><?php echo esc_html__('WUS', 'abschussplan-hgmh'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $submission) : ?>
                        <tr>
                            <td data-label="<?php echo esc_attr__('Abschussdatum', 'abschussplan-hgmh'); ?>">
                                <?php
                                // Format the date in German format dd.mm.yy
                                if (!empty($submission['field1'])) {
                                    $date = DateTime::createFromFormat('Y-m-d', $submission['field1']);
                                    if ($date) {
                                        echo esc_html($date->format('d.m.y'));
                                    } else {
                                        echo esc_html($submission['field1']);
                                    }
                                }
                                ?>
                            </td>
                            <td data-label="<?php echo esc_attr__('Meldegruppe', 'abschussplan-hgmh'); ?>">
                                <?php echo esc_html($submission['meldegruppe'] ?? ''); ?>
                            </td>
                            <td data-label="<?php echo esc_attr__('Jagdbezirk', 'abschussplan-hgmh'); ?>">
                                <?php echo esc_html($submission['field5'] ?? ''); ?>
                            </td>
                            <td data-label="<?php echo esc_attr__('Abschuss', 'abschussplan-hgmh'); ?>"><?php echo esc_html($submission['field2']); ?></td>
                            <td data-label="<?php echo esc_attr__('Bemerkung', 'abschussplan-hgmh'); ?>"><?php echo esc_html($submission['field4'] ?? ''); ?></td>
                            <td data-label="<?php echo esc_attr__('WUS', 'abschussplan-hgmh'); ?>"><?php echo esc_html($submission['field3']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1) : ?>
            <nav aria-label="<?php echo esc_attr__('Abschussmeldungen Navigation', 'abschussplan-hgmh'); ?>">
                <ul class="pagination">
                    <?php if ($current_page > 1) : ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo esc_url(add_query_arg(array('abschuss_page' => $current_page - 1, 'abschuss_limit' => $current_limit))); ?>" aria-label="<?php echo esc_attr__('Vorherige Seite', 'abschussplan-hgmh'); ?>">
                                &laquo; <?php echo esc_html__('Zurück', 'abschussplan-hgmh'); ?>
                            </a>
                        </li>
                    <?php else : ?>
                        <li class="page-item disabled">
                            <span class="page-link" aria-disabled="true">&laquo; <?php echo esc_html__('Zurück', 'abschussplan-hgmh'); ?></span>
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
                                <span class="page-link" aria-hidden="true">&hellip;</span>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $start; $i <= $end; $i++) : ?>
                        <?php if ($i == $current_page) : ?>
                            <li class="page-item active" aria-current="page">
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
                                <span class="page-link" aria-hidden="true">&hellip;</span>
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
                            <a class="page-link" href="<?php echo esc_url(add_query_arg(array('abschuss_page' => $current_page + 1, 'abschuss_limit' => $current_limit))); ?>" aria-label="<?php echo esc_attr__('Nächste Seite', 'abschussplan-hgmh'); ?>">
                                <?php echo esc_html__('Weiter', 'abschussplan-hgmh'); ?> &raquo;
                            </a>
                        </li>
                    <?php else : ?>
                        <li class="page-item disabled">
                            <span class="page-link" aria-disabled="true"><?php echo esc_html__('Weiter', 'abschussplan-hgmh'); ?> &raquo;</span>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>
