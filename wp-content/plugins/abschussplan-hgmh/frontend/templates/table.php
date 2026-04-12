<?php
/**
 * Table template for displaying Abschussmeldungen with moderation controls
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get current page from URL or use the one from shortcode attributes
$current_page = isset($_GET['abschuss_page']) ? max(1, intval($_GET['abschuss_page'])) : (isset($page) ? $page : 1);

// Get limit from URL or use the one from shortcode attributes
$current_limit = isset($_GET['abschuss_limit']) ? max(1, intval($_GET['abschuss_limit'])) : (isset($limit) ? $limit : 10);

// Check if current user is an Obmann (moderator) or at least logged in (can edit own entries)
$is_moderator = current_user_can('moderate_submissions') || current_user_can('manage_options');
$can_edit = is_user_logged_in();
?>

<div class="abschuss-table-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><?php echo esc_html__('Abschussmeldungen', 'abschussplan-hgmh'); ?></h2>
        <?php if (isset($show_export_button) && $show_export_button && current_user_can('manage_options')) : ?>
        <div class="export-controls">
            <button type="button" class="btn btn-success export-btn" data-species="<?php echo esc_attr($species); ?>" aria-label="<?php echo esc_attr__('Daten als CSV exportieren', 'abschussplan-hgmh'); ?>">
                <i class="bi bi-download" aria-hidden="true"></i> <?php echo esc_html__('CSV Export', 'abschussplan-hgmh'); ?>
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Live region for moderation action feedback -->
    <div aria-live="polite" class="visually-hidden" id="moderation-status-announcer"></div>

    <?php if (empty($submissions)) : ?>
        <div class="abschuss-empty" role="status">
            <p><?php echo esc_html__('Keine Abschussmeldungen vorhanden.', 'abschussplan-hgmh'); ?></p>
        </div>
    <?php else : ?>
        <div class="table-responsive">
            <table class="table table-striped abschuss-table">
                <caption class="visually-hidden"><?php echo esc_html__('Abschussmeldungen mit Moderationsoptionen', 'abschussplan-hgmh'); ?></caption>
                <thead>
                    <tr>
                        <th scope="col"><?php echo esc_html__('Abschussdatum', 'abschussplan-hgmh'); ?></th>
                        <th scope="col"><?php echo esc_html__('Wildart', 'abschussplan-hgmh'); ?></th>
                        <th scope="col"><?php echo esc_html__('Abschuss', 'abschussplan-hgmh'); ?></th>
                        <th scope="col"><?php echo esc_html__('WUS', 'abschussplan-hgmh'); ?></th>
                        <th scope="col"><?php echo esc_html__('Meldegruppe', 'abschussplan-hgmh'); ?></th>
                        <th scope="col"><?php echo esc_html__('Jagdbezirk', 'abschussplan-hgmh'); ?></th>
                        <th scope="col"><?php echo esc_html__('Bemerkung', 'abschussplan-hgmh'); ?></th>
                        <th scope="col"><?php echo esc_html__('Interne Notiz', 'abschussplan-hgmh'); ?></th>
                        <th scope="col"><?php echo esc_html__('Erstellt von', 'abschussplan-hgmh'); ?></th>
                        <th scope="col"><?php echo esc_html__('Erstellt am', 'abschussplan-hgmh'); ?></th>
                        <?php if ($is_moderator || $can_edit) : ?>
                        <th scope="col"><?php echo esc_html__('Moderation', 'abschussplan-hgmh'); ?></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $frontend_status_labels = array(
                        'pending_email'    => 'E-Mail ausstehend',
                        'pending'          => 'Ausstehend',
                        'email_verified'   => 'E-Mail bestätigt',
                        'pending_approval' => 'Warten auf Freigabe',
                        'approved'         => 'Genehmigt',
                        'rejected'         => 'Abgelehnt',
                    );
                    ?>
                    <?php foreach ($submissions as $submission) : ?>
                        <?php
                        $sub_id       = isset($submission['id']) ? intval($submission['id']) : 0;
                        $raw_harvest  = $submission['harvest_date'] ?? '';
                        // Store ISO date (YYYY-MM-DD) for the inline-edit date input
                        $iso_date     = $raw_harvest ? substr($raw_harvest, 0, 10) : '';
                        ?>
                        <tr data-id="<?php echo esc_attr($sub_id); ?>" data-harvest-date="<?php echo esc_attr($iso_date); ?>"
                            data-nonce="<?php echo esc_attr(wp_create_nonce('ahgmh_table_moderation_nonce')); ?>"><?php // nonce per row for delete ?>
                            <td data-label="<?php echo esc_attr__('Abschussdatum', 'abschussplan-hgmh'); ?>">
                                <?php
                                $harvest_date = $submission['harvest_date'] ?? '';
                                if (!empty($harvest_date)) {
                                    $date = DateTime::createFromFormat('Y-m-d', substr($harvest_date, 0, 10));
                                    if ($date) {
                                        echo esc_html($date->format('d.m.y'));
                                    } else {
                                        echo esc_html($harvest_date);
                                    }
                                }
                                ?>
                            </td>
                            <td data-label="<?php echo esc_attr__('Wildart', 'abschussplan-hgmh'); ?>"><?php echo esc_html($submission['wildart_name'] ?? ''); ?></td>
                            <td data-label="<?php echo esc_attr__('Abschuss', 'abschussplan-hgmh'); ?>"><?php echo esc_html($submission['category'] ?? ''); ?></td>
                            <td data-label="<?php echo esc_attr__('WUS', 'abschussplan-hgmh'); ?>"><?php echo esc_html($submission['wus_number'] ?? ''); ?></td>
                            <td data-label="<?php echo esc_attr__('Meldegruppe', 'abschussplan-hgmh'); ?>"><?php echo esc_html($submission['meldegruppe_name'] ?? ''); ?></td>
                            <td data-label="<?php echo esc_attr__('Jagdbezirk', 'abschussplan-hgmh'); ?>"><?php echo esc_html($submission['eigenjagdbezirk_name'] ?? ''); ?></td>
                            <td data-label="<?php echo esc_attr__('Bemerkung', 'abschussplan-hgmh'); ?>"><?php echo esc_html($submission['notes'] ?? ''); ?></td>
                            <td data-label="<?php echo esc_attr__('Interne Notiz', 'abschussplan-hgmh'); ?>"><?php echo esc_html($submission['internal_note'] ?? ''); ?></td>
                            <td data-label="<?php echo esc_attr__('Erstellt von', 'abschussplan-hgmh'); ?>">
                                <?php
                                if (isset($submission['submitted_by_user_id']) && $submission['submitted_by_user_id'] > 0) {
                                    $user = get_user_by('id', $submission['submitted_by_user_id']);
                                    if ($user) {
                                        $first_name = get_user_meta($user->ID, 'first_name', true);
                                        $last_name = get_user_meta($user->ID, 'last_name', true);
                                        if (!empty($first_name) && !empty($last_name)) {
                                            echo esc_html(trim($first_name . ' ' . $last_name));
                                        } elseif (!empty($first_name)) {
                                            echo esc_html($first_name);
                                        } elseif (!empty($last_name)) {
                                            echo esc_html($last_name);
                                        } else {
                                            echo esc_html($user->display_name);
                                        }
                                    } else {
                                        echo esc_html__('Unbekannter Benutzer', 'abschussplan-hgmh');
                                    }
                                } else {
                                    echo esc_html__('Kein Name verfügbar', 'abschussplan-hgmh');
                                }
                                ?>
                            </td>
                            <td data-label="<?php echo esc_attr__('Erstellt am', 'abschussplan-hgmh'); ?>">
                                <?php
                                $submitted_at = $submission['submitted_at'] ?? '';
                                if (!empty($submitted_at)) {
                                    $datetime = new DateTime($submitted_at, new DateTimeZone('UTC'));
                                    $datetime->setTimezone(new DateTimeZone('Europe/Berlin'));
                                    echo esc_html($datetime->format('d.m.y H:i'));
                                }
                                ?>
                            </td>
                            <?php if ($is_moderator || $can_edit) : ?>
                            <td data-label="<?php echo esc_attr__('Moderation', 'abschussplan-hgmh'); ?>">
                                <?php
                                $submission_status = $submission['status'] ?? '';
                                $is_pending = in_array($submission_status, array('pending_email', 'pending', 'email_verified', 'pending_approval'), true);
                                $status_label = isset($frontend_status_labels[$submission_status])
                                    ? $frontend_status_labels[$submission_status]
                                    : ucfirst($submission_status);
                                ?>
                                <div class="d-flex align-items-center gap-1 flex-wrap">
                                    <?php if ($is_moderator && $is_pending) : ?>
                                        <button type="button"
                                                class="btn btn-success btn-sm btn-approve"
                                                data-submission-id="<?php echo esc_attr($sub_id); ?>"
                                                title="<?php echo esc_attr__('Freigeben', 'abschussplan-hgmh'); ?>"
                                                aria-label="<?php echo esc_attr(sprintf(__('Meldung %d freigeben', 'abschussplan-hgmh'), $sub_id)); ?>">
                                            <i class="bi bi-check-lg" aria-hidden="true"></i>
                                        </button>
                                        <button type="button"
                                                class="btn btn-danger btn-sm btn-reject"
                                                data-submission-id="<?php echo esc_attr($sub_id); ?>"
                                                title="<?php echo esc_attr__('Ablehnen', 'abschussplan-hgmh'); ?>"
                                                aria-label="<?php echo esc_attr(sprintf(__('Meldung %d ablehnen', 'abschussplan-hgmh'), $sub_id)); ?>">
                                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                                        </button>
                                    <?php elseif (!$is_pending) : ?>
                                        <span class="badge bg-secondary"><?php echo esc_html($status_label); ?></span>
                                    <?php endif; ?>
                                    <?php if ($can_edit) : ?>
                                        <button type="button"
                                                class="btn btn-outline-secondary btn-sm btn-edit"
                                                data-submission-id="<?php echo esc_attr($sub_id); ?>"
                                                title="<?php echo esc_attr__('Bearbeiten', 'abschussplan-hgmh'); ?>"
                                                aria-label="<?php echo esc_attr(sprintf(__('Meldung %d bearbeiten', 'abschussplan-hgmh'), $sub_id)); ?>">
                                            <i class="bi bi-pencil" aria-hidden="true"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <?php endif; ?>
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
                    $start = max(1, $current_page - 2);
                    $end = min($total_pages, $current_page + 2);

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

                    <?php if ($end < $total_pages) : ?>
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

<!-- Reject Submission Modal -->
<div class="modal fade" id="rejectSubmissionModal" tabindex="-1" aria-labelledby="rejectSubmissionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectSubmissionModalLabel"><?php echo esc_html__('Abschussmeldung ablehnen', 'abschussplan-hgmh'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo esc_attr__('Schließen', 'abschussplan-hgmh'); ?>"></button>
            </div>
            <div class="modal-body">
                <div id="reject-form-response" class="alert" role="alert" aria-live="polite" style="display: none;"></div>

                <form id="reject-submission-form" data-submission-id="" novalidate>
                    <?php wp_nonce_field('ahgmh_reject_submission_nonce', 'ahgmh_reject_nonce'); ?>

                    <div class="mb-3">
                        <label for="reject-comment" class="form-label"><?php echo esc_html__('Ablehnungsgrund (Pflichtfeld)', 'abschussplan-hgmh'); ?> <span class="text-danger" aria-hidden="true">*</span></label>
                        <textarea class="form-control" id="reject-comment" name="comment" rows="4" required aria-required="true" placeholder="<?php echo esc_attr__('Bitte geben Sie einen Grund für die Ablehnung an...', 'abschussplan-hgmh'); ?>"></textarea>
                        <div class="form-error" role="alert"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo esc_html__('Abbrechen', 'abschussplan-hgmh'); ?></button>
                <button type="button" class="btn btn-danger" id="confirm-reject-btn"><?php echo esc_html__('Ablehnen bestätigen', 'abschussplan-hgmh'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';
    document.querySelectorAll('.export-btn[data-species]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var species = this.getAttribute('data-species');
            var exportUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>?action=export_abschuss_csv';
            if (species) {
                exportUrl += '&species=' + encodeURIComponent(species);
            }
            window.location.href = exportUrl;
        });
    });
})();
</script>
