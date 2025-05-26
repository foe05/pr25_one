<?php
/**
 * Table Display Class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class for displaying form submissions in a table
 */
class AHGMH_Table_Display {
    /**
     * Constructor
     */
    public function __construct() {
        // Register shortcode for displaying submissions table
        add_shortcode('ahgmh_submissions', array($this, 'render_table'));
    }

    /**
     * Render the submissions table using shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output of the table
     */
    public function render_table($atts) {
        // Parse shortcode attributes
        $atts = shortcode_atts(
            array(
                'limit' => 10, // Default number of entries to show
                'page' => 1,   // Default page
            ),
            $atts,
            'ahgmh_submissions'
        );

        // Convert to integers and check URL parameters
        $limit = isset($_GET['ahgmh_limit']) ? max(1, intval($_GET['ahgmh_limit'])) : intval($atts['limit']);
        $page = isset($_GET['ahgmh_page']) ? max(1, intval($_GET['ahgmh_page'])) : max(1, intval($atts['page']));
        $offset = ($page - 1) * $limit;

        // Get database instance
        $database = abschussplan_hgmh()->database;

        // Get submissions
        $submissions = $database->get_submissions($limit, $offset);
        
        // Get total count
        $total_submissions = $database->count_submissions();
        
        // Calculate pagination
        $total_pages = ceil($total_submissions / $limit);

        // Start output buffer
        ob_start();
        
        // Include template
        include AHGMH_PLUGIN_DIR . 'templates/table-template.php';
        
        // Return the output
        return ob_get_clean();
    }

    /**
     * Generate pagination HTML
     *
     * @param int $current_page Current page number
     * @param int $total_pages Total number of pages
     * @param int $limit Items per page
     * @return string Pagination HTML
     */
    public function pagination_html($current_page, $total_pages, $limit) {
        if ($total_pages <= 1) {
            return '';
        }

        $output = '<nav aria-label="Submissions navigation">';
        $output .= '<ul class="pagination justify-content-center">';
        
        // Previous page link
        $prev_disabled = ($current_page <= 1) ? 'disabled' : '';
        $output .= '<li class="page-item ' . $prev_disabled . '">';
        if ($current_page > 1) {
            $output .= '<a class="page-link" href="' . add_query_arg(array('ahgmh_page' => $current_page - 1, 'ahgmh_limit' => $limit)) . '">&laquo; ' . __('Previous', 'abschussplan-hgmh') . '</a>';
        } else {
            $output .= '<span class="page-link">&laquo; ' . __('Previous', 'abschussplan-hgmh') . '</span>';
        }
        $output .= '</li>';
        
        // Page links
        $range = 2; // Number of pages to show on each side of current page
        
        // Start range
        $start = max(1, $current_page - $range);
        
        // End range
        $end = min($total_pages, $current_page + $range);
        
        // Show first page if not in range
        if ($start > 1) {
            $output .= '<li class="page-item"><a class="page-link" href="' . add_query_arg(array('ahgmh_page' => 1, 'ahgmh_limit' => $limit)) . '">1</a></li>';
            if ($start > 2) {
                $output .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }
        
        // Page numbers
        for ($i = $start; $i <= $end; $i++) {
            $active = ($i == $current_page) ? 'active' : '';
            $output .= '<li class="page-item ' . $active . '">';
            if ($i == $current_page) {
                $output .= '<span class="page-link">' . $i . '</span>';
            } else {
                $output .= '<a class="page-link" href="' . add_query_arg(array('ahgmh_page' => $i, 'ahgmh_limit' => $limit)) . '">' . $i . '</a>';
            }
            $output .= '</li>';
        }
        
        // Show last page if not in range
        if ($end < $total_pages) {
            if ($end < $total_pages - 1) {
                $output .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            $output .= '<li class="page-item"><a class="page-link" href="' . add_query_arg(array('ahgmh_page' => $total_pages, 'ahgmh_limit' => $limit)) . '">' . $total_pages . '</a></li>';
        }
        
        // Next page link
        $next_disabled = ($current_page >= $total_pages) ? 'disabled' : '';
        $output .= '<li class="page-item ' . $next_disabled . '">';
        if ($current_page < $total_pages) {
            $output .= '<a class="page-link" href="' . add_query_arg(array('ahgmh_page' => $current_page + 1, 'ahgmh_limit' => $limit)) . '">' . __('Next', 'abschussplan-hgmh') . ' &raquo;</a>';
        } else {
            $output .= '<span class="page-link">' . __('Next', 'abschussplan-hgmh') . ' &raquo;</span>';
        }
        $output .= '</li>';
        
        $output .= '</ul>';
        $output .= '</nav>';
        
        return $output;
    }
}
