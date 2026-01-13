<?php
/**
 * Migration Manager for Database Schema Versioning
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class for handling database migrations with version control
 */
class AHGMH_Migration_Manager {

    /**
     * Migrations directory path
     */
    private $migrations_dir;

    /**
     * Current database schema version
     */
    private $current_version;

    /**
     * Constructor
     */
    public function __construct() {
        $this->migrations_dir = plugin_dir_path(__FILE__) . '../migrations/';
        $this->current_version = get_option('hgmh_db_schema_version', 0);
    }

    /**
     * Get current database schema version
     *
     * @return int Current version number
     */
    public function get_current_version() {
        return $this->current_version;
    }

    /**
     * Get all available migrations
     *
     * @return array Array of migration information
     */
    public function get_available_migrations() {
        $migrations = [];

        if (!is_dir($this->migrations_dir)) {
            return $migrations;
        }

        $files = glob($this->migrations_dir . '*.php');

        foreach ($files as $file) {
            $filename = basename($file, '.php');
            if (preg_match('/^(\d+)_(.+)$/', $filename, $matches)) {
                $version = intval($matches[1]);
                $name = str_replace('_', ' ', $matches[2]);

                $migrations[$version] = [
                    'version' => $version,
                    'name' => $name,
                    'file' => $file,
                    'applied' => ($version <= $this->current_version)
                ];
            }
        }

        ksort($migrations);
        return $migrations;
    }

    /**
     * Run migrations up to target version
     *
     * @param int|null $target_version Target version to migrate to, or null for latest
     * @return array Result array with success status, final version, and log
     */
    public function migrate_to($target_version = null) {
        $migrations = $this->get_available_migrations();

        if ($target_version === null) {
            $target_version = max(array_keys($migrations));
        }

        $log = [];

        foreach ($migrations as $version => $migration) {
            if ($migration['applied'] || $version > $target_version) {
                continue;
            }

            try {
                require_once $migration['file'];

                $class_name = 'AHGMH_Migration_' . str_pad($version, 3, '0', STR_PAD_LEFT);

                if (!class_exists($class_name)) {
                    throw new Exception("Migration class {$class_name} not found");
                }

                $instance = new $class_name();

                if (!method_exists($instance, 'up')) {
                    throw new Exception("Migration {$class_name} has no up() method");
                }

                $result = $instance->up();

                if ($result === false) {
                    throw new Exception("Migration {$class_name}::up() returned false");
                }

                update_option('hgmh_db_schema_version', $version);
                $this->current_version = $version;

                $log[] = "✅ Migration {$version}: {$migration['name']} - SUCCESS";

            } catch (Exception $e) {
                $log[] = "❌ Migration {$version}: {$migration['name']} - FAILED: " . $e->getMessage();
                break;
            }
        }

        return [
            'success' => true,
            'final_version' => $this->current_version,
            'log' => $log
        ];
    }

    /**
     * Rollback to target version
     *
     * @param int $target_version Target version to rollback to
     * @return array Result array with success status, final version, and log
     */
    public function rollback_to($target_version) {
        $migrations = $this->get_available_migrations();
        krsort($migrations);

        $log = [];

        foreach ($migrations as $version => $migration) {
            if (!$migration['applied'] || $version <= $target_version) {
                continue;
            }

            try {
                require_once $migration['file'];

                $class_name = 'AHGMH_Migration_' . str_pad($version, 3, '0', STR_PAD_LEFT);
                $instance = new $class_name();

                if (!method_exists($instance, 'down')) {
                    throw new Exception("Migration {$class_name} has no down() method");
                }

                $result = $instance->down();

                if ($result === false) {
                    throw new Exception("Migration {$class_name}::down() returned false");
                }

                update_option('hgmh_db_schema_version', $version - 1);
                $this->current_version = $version - 1;

                $log[] = "✅ Rollback {$version}: {$migration['name']} - SUCCESS";

            } catch (Exception $e) {
                $log[] = "❌ Rollback {$version}: {$migration['name']} - FAILED: " . $e->getMessage();
                break;
            }
        }

        return [
            'success' => true,
            'final_version' => $this->current_version,
            'log' => $log
        ];
    }
}
