<?php
class ConfigManager {
    private static $instance = null;
    private $settings = [];
    private $db;

    private function __construct() {
        $this->db = Database::getInstance();
        $this->loadSettings();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function loadSettings() {
        try {
            $stmt = $this->db->query("SELECT setting_name, setting_value FROM settings");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as $row) {
                $this->settings[$row['setting_name']] = $row['setting_value'];
            }
            return true;
        } catch (Exception $e) {
            error_log("Settings Load Error: " . $e->getMessage());
            return false;
        }
    }

    public function getSetting($key, $default = null) {
        return $this->settings[$key] ?? $default;
    }

    public function getSettings() {
        return $this->settings;
    }
}