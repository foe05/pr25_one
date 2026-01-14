<?php
/**
 * Jagdbezirk Service Class
 * Business logic for Eigenjagdbezirke (hunting districts) operations
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Jagdbezirk Service for business logic
 */
class AHGMH_Jagdbezirk_Service {

    /**
     * @var AHGMH_Jagdbezirk_Repository
     */
    private $repository;

    /**
     * Constructor
     */
    public function __construct() {
        $this->repository = new AHGMH_Jagdbezirk_Repository();
    }

    /**
     * Get all Jagdbezirke
     *
     * @param bool $only_active Only return active Jagdbezirke
     * @return array Array of Jagdbezirke
     */
    public function get_all($only_active = true) {
        return $this->repository->get_all($only_active);
    }

    /**
     * Get a single Jagdbezirk by ID
     *
     * @param int $id Jagdbezirk ID
     * @return array|null Jagdbezirk data or null if not found
     */
    public function get_by_id($id) {
        return $this->repository->get_by_id($id);
    }

    /**
     * Create a new Jagdbezirk
     *
     * @param array $data Jagdbezirk data
     * @return array Result with success status and message
     */
    public function create($data) {
        $name = sanitize_text_field($data['name'] ?? '');

        if (empty($name)) {
            return [
                'success' => false,
                'message' => __('Der Name des Jagdbezirks darf nicht leer sein.', 'abschussplan-hgmh')
            ];
        }

        if (strlen($name) > 255) {
            return [
                'success' => false,
                'message' => __('Der Name des Jagdbezirks ist zu lang (max. 255 Zeichen).', 'abschussplan-hgmh')
            ];
        }

        // Check for duplicate name
        $existing = $this->repository->get_by_name($name);
        if ($existing) {
            return [
                'success' => false,
                'message' => __('Ein Jagdbezirk mit diesem Namen existiert bereits.', 'abschussplan-hgmh')
            ];
        }

        $id = $this->repository->create([
            'name' => $name,
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'is_active' => 1
        ]);

        if ($id === false) {
            return [
                'success' => false,
                'message' => __('Fehler beim Erstellen des Jagdbezirks.', 'abschussplan-hgmh')
            ];
        }

        // Assign Meldegruppen if provided
        if (!empty($data['meldegruppe_ids']) && is_array($data['meldegruppe_ids'])) {
            $this->repository->assign_meldegruppen($id, $data['meldegruppe_ids']);
        }

        // Log activity
        $this->log_activity('create', $id, $name);

        return [
            'success' => true,
            'message' => __('Jagdbezirk erfolgreich erstellt.', 'abschussplan-hgmh'),
            'id' => $id
        ];
    }

    /**
     * Update an existing Jagdbezirk
     *
     * @param int $id Jagdbezirk ID
     * @param array $data Updated data
     * @return array Result with success status and message
     */
    public function update($id, $data) {
        $id = absint($id);

        if ($id <= 0) {
            return [
                'success' => false,
                'message' => __('Ungueltige Jagdbezirk-ID.', 'abschussplan-hgmh')
            ];
        }

        $existing = $this->repository->get_by_id($id);
        if (!$existing) {
            return [
                'success' => false,
                'message' => __('Jagdbezirk nicht gefunden.', 'abschussplan-hgmh')
            ];
        }

        // Validate name if provided
        if (isset($data['name'])) {
            $name = sanitize_text_field($data['name']);

            if (empty($name)) {
                return [
                    'success' => false,
                    'message' => __('Der Name des Jagdbezirks darf nicht leer sein.', 'abschussplan-hgmh')
                ];
            }

            if (strlen($name) > 255) {
                return [
                    'success' => false,
                    'message' => __('Der Name des Jagdbezirks ist zu lang (max. 255 Zeichen).', 'abschussplan-hgmh')
                ];
            }

            // Check for duplicate name (excluding current ID)
            $duplicate = $this->repository->get_by_name($name);
            if ($duplicate && intval($duplicate['id']) !== $id) {
                return [
                    'success' => false,
                    'message' => __('Ein Jagdbezirk mit diesem Namen existiert bereits.', 'abschussplan-hgmh')
                ];
            }
        }

        $result = $this->repository->update($id, $data);

        if ($result === false) {
            return [
                'success' => false,
                'message' => __('Fehler beim Aktualisieren des Jagdbezirks.', 'abschussplan-hgmh')
            ];
        }

        // Update Meldegruppen assignments if provided
        if (isset($data['meldegruppe_ids'])) {
            $meldegruppe_ids = is_array($data['meldegruppe_ids']) ? $data['meldegruppe_ids'] : [];
            $this->repository->assign_meldegruppen($id, $meldegruppe_ids);
        }

        // Log activity
        $this->log_activity('update', $id, $data['name'] ?? $existing['name']);

        return [
            'success' => true,
            'message' => __('Jagdbezirk erfolgreich aktualisiert.', 'abschussplan-hgmh')
        ];
    }

    /**
     * Delete a Jagdbezirk
     *
     * @param int $id Jagdbezirk ID
     * @param bool $force Force delete even if has submissions
     * @return array Result with success status and message
     */
    public function delete($id, $force = false) {
        $id = absint($id);

        if ($id <= 0) {
            return [
                'success' => false,
                'message' => __('Ungueltige Jagdbezirk-ID.', 'abschussplan-hgmh')
            ];
        }

        $existing = $this->repository->get_by_id($id);
        if (!$existing) {
            return [
                'success' => false,
                'message' => __('Jagdbezirk nicht gefunden.', 'abschussplan-hgmh')
            ];
        }

        // Check for associated submissions
        if (!$force && $this->repository->has_submissions($id)) {
            return [
                'success' => false,
                'message' => __('Dieser Jagdbezirk kann nicht geloescht werden, da bereits Meldungen existieren. Bitte deaktivieren Sie den Jagdbezirk stattdessen.', 'abschussplan-hgmh')
            ];
        }

        // Soft delete (deactivate)
        $result = $this->repository->delete($id, false);

        if ($result === false) {
            return [
                'success' => false,
                'message' => __('Fehler beim Loeschen des Jagdbezirks.', 'abschussplan-hgmh')
            ];
        }

        // Log activity
        $this->log_activity('delete', $id, $existing['name']);

        return [
            'success' => true,
            'message' => __('Jagdbezirk erfolgreich geloescht.', 'abschussplan-hgmh')
        ];
    }

    /**
     * Restore a deleted Jagdbezirk
     *
     * @param int $id Jagdbezirk ID
     * @return array Result with success status and message
     */
    public function restore($id) {
        $id = absint($id);

        $result = $this->repository->restore($id);

        if ($result === false) {
            return [
                'success' => false,
                'message' => __('Fehler beim Wiederherstellen des Jagdbezirks.', 'abschussplan-hgmh')
            ];
        }

        return [
            'success' => true,
            'message' => __('Jagdbezirk erfolgreich wiederhergestellt.', 'abschussplan-hgmh')
        ];
    }

    /**
     * Assign Meldegruppen to a Jagdbezirk
     *
     * @param int $jagdbezirk_id Jagdbezirk ID
     * @param array $meldegruppe_ids Array of Meldegruppe IDs
     * @return array Result with success status and message
     */
    public function assign_meldegruppen($jagdbezirk_id, $meldegruppe_ids) {
        $jagdbezirk_id = absint($jagdbezirk_id);

        if ($jagdbezirk_id <= 0) {
            return [
                'success' => false,
                'message' => __('Ungueltige Jagdbezirk-ID.', 'abschussplan-hgmh')
            ];
        }

        $existing = $this->repository->get_by_id($jagdbezirk_id);
        if (!$existing) {
            return [
                'success' => false,
                'message' => __('Jagdbezirk nicht gefunden.', 'abschussplan-hgmh')
            ];
        }

        $meldegruppe_ids = is_array($meldegruppe_ids) ? array_map('absint', $meldegruppe_ids) : [];

        $result = $this->repository->assign_meldegruppen($jagdbezirk_id, $meldegruppe_ids);

        if ($result === false) {
            return [
                'success' => false,
                'message' => __('Fehler beim Zuweisen der Meldegruppen.', 'abschussplan-hgmh')
            ];
        }

        // Log activity
        $this->log_activity('assign_meldegruppen', $jagdbezirk_id, $existing['name']);

        return [
            'success' => true,
            'message' => __('Meldegruppen erfolgreich zugewiesen.', 'abschussplan-hgmh')
        ];
    }

    /**
     * Get all available Meldegruppen
     *
     * @return array Array of Meldegruppen
     */
    public function get_all_meldegruppen() {
        return $this->repository->get_all_meldegruppen(true);
    }

    /**
     * Get Jagdbezirke by Meldegruppe
     *
     * @param int $meldegruppe_id Meldegruppe ID
     * @return array Array of Jagdbezirke
     */
    public function get_by_meldegruppe($meldegruppe_id) {
        return $this->repository->get_by_meldegruppe($meldegruppe_id);
    }

    /**
     * Get statistics for Jagdbezirke
     *
     * @return array Statistics data
     */
    public function get_statistics() {
        $all = $this->repository->get_all(false);
        $active = array_filter($all, function($item) {
            return intval($item['is_active']) === 1;
        });
        $inactive = array_filter($all, function($item) {
            return intval($item['is_active']) === 0;
        });

        $with_meldegruppen = array_filter($all, function($item) {
            return !empty($item['meldegruppe_ids']);
        });

        return [
            'total' => count($all),
            'active' => count($active),
            'inactive' => count($inactive),
            'with_meldegruppen' => count($with_meldegruppen),
            'without_meldegruppen' => count($all) - count($with_meldegruppen)
        ];
    }

    /**
     * Log activity for audit trail
     *
     * @param string $action Action performed
     * @param int $jagdbezirk_id Jagdbezirk ID
     * @param string $jagdbezirk_name Jagdbezirk name
     */
    private function log_activity($action, $jagdbezirk_id, $jagdbezirk_name) {
        if (class_exists('AHGMH_Activity_Logger')) {
            $logger = new AHGMH_Activity_Logger();
            $logger->log(
                'jagdbezirk_' . $action,
                'jagdbezirk',
                $jagdbezirk_id,
                [
                    'name' => $jagdbezirk_name,
                    'action' => $action
                ]
            );
        }
    }
}
