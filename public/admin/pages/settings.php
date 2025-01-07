<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/bootstrap.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/public/admin/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/classes/ConfigManager.php';

// Initialize variables
$db = Database::getInstance();
$pageTitle = 'Settings';

// Ensure this points to the correct bootstrap file
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/bootstrap.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $configManager = ConfigManager::getInstance();
        
        // Collect all settings including background color
        $newSettings = [
            'APP_NAME' => $_POST['site_name'] ?? '',
            'APP_TIMEZONE' => $_POST['timezone'] ?? '',
            'APP_THEME_COLOR1' => $_POST['theme_color1'] ?? '',
            'APP_THEME_COLOR2' => $_POST['theme_color2'] ?? '',
            'APP_THEME_COLOR3' => $_POST['theme_color3'] ?? '',
            'APP_THEME_COLOR4' => $_POST['theme_color4'] ?? '',
            'APP_THEME_COLOR5' => $_POST['theme_color5'] ?? '',
            'APP_THEME_COLOR6' => $_POST['theme_color6'] ?? '',
            'APP_BACKGROUND_COLOR' => $_POST['background_color'] ?? '',
            'TEXT_H1_COLOR' => $_POST['text_h1_color'] ?? '',
            'TEXT_H2_COLOR' => $_POST['text_h2_color'] ?? '',
            'TEXT_H3_COLOR' => $_POST['text_h3_color'] ?? '',
            'TEXT_H4_COLOR' => $_POST['text_h4_color'] ?? '',
            'TEXT_H5_COLOR' => $_POST['text_h5_color'] ?? '',
            'TEXT_H6_COLOR' => $_POST['text_h6_color'] ?? '',
            'TEXT_P_COLOR' => $_POST['text_p_color'] ?? ''
        ];
        
        $configManager->updateConfig($newSettings);
        $_SESSION['success'] = "Settings updated successfully";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } catch (Exception $e) {
        error_log("Settings update error: " . $e->getMessage());
        $_SESSION['error'] = "Settings update failed: " . $e->getMessage();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Fetch current settings
try {
    $configManager = ConfigManager::getInstance();
    $settings = $configManager->getSettings();
    
    if (!is_array($settings)) {
        throw new Exception("Failed to load settings");
    }
} catch (Exception $e) {
    error_log("Settings fetch error: " . $e->getMessage());
    $_SESSION['error'] = "Failed to load settings";
    $settings = [];
}

// Display messages from session
$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']);

$pageTitle = $settings['APP_NAME'] ?? 'Site Settings';
$headerButtons = '';
?>

<div class="container py-5" style="background-color: #121212; color: white;">
    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($success)): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <!-- Introduction -->
    <h1>Welcome.</h1>
    <h2>Please configure the competition tracker below</h2>

    <!-- Settings Form -->
    <div class="card mb-4" style="background-color: #332648; color: white;">
        <div class="card-header">
            <h2 class="h5 mb-0">Site Settings</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <div class="mb-3">
                    <label for="site_name" class="form-label">Site Name</label>
                    <input type="text" name="site_name" id="site_name" class="form-control" 
                           value="<?php echo htmlspecialchars($settings['APP_NAME'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="timezone" class="form-label">Timezone</label>
                    <select name="timezone" id="timezone" class="form-control" required>
                        <?php
                        // List of timezones
                        $timezones = DateTimeZone::listIdentifiers();
                        foreach ($timezones as $timezone) {
                            $selected = (isset($settings['APP_TIMEZONE']) && $settings['APP_TIMEZONE'] === $timezone) ? 'selected' : '';
                            echo "<option value=\"$timezone\" $selected>$timezone</option>";
                        }
                        ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary" style="background-color: #00B945; border-color: #00B945;">Save Settings</button>
            </form>
        </div>
    </div>
</div>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/public/admin/includes/footer.php'; ?>