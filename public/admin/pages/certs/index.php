<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/bootstrap.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/public/admin/certs/functions.php';

$pageTitle = 'Certificates';
require_once $_SERVER['DOCUMENT_ROOT'] . '/public/admin/includes/header.php';

// Fetch competitions and pods for the dropdowns
$db = Database::getInstance()->getConnection();
$competitions = $db->query("SELECT id, name, start_date, end_date FROM competitions ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$pods = $db->query("SELECT id, name FROM pods ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Check for error messages
$error = isset($_GET['error']) ? $_GET['error'] : '';
?>

<div class="container py-5">
    <h1 class="mb-4">Certificates</h1>

    <?php if ($error): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h2 class="mb-0">Create</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="/public/admin/certs/functions.php" id="certificateForm">
                <div class="mb-3">
                    <label for="pod" class="form-label">Select Pod</label>
                    <select name="pod_id" id="pod" class="form-select" required>
                        <option value="">Select Pod</option>
                        <?php foreach ($pods as $pod): ?>
                            <option value="<?php echo $pod['id']; ?>"
                                    <?php echo (isset($_GET['pod']) && $_GET['pod'] == $pod['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pod['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="competition" class="form-label">Select Competition</label>
                    <select name="competition_id" id="competition" class="form-select" required>
                        <option value="">Select Competition</option>
                        <?php foreach ($competitions as $competition): ?>
                            <option value="<?php echo $competition['id']; ?>"
                                    <?php echo (isset($_GET['competition']) && $_GET['competition'] == $competition['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($competition['name']) . " (" . 
                                         htmlspecialchars($competition['start_date']) . " - " . 
                                         htmlspecialchars($competition['end_date']) . ")"; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="team_manager" class="form-label">Team Manager</label>
                    <input type="text" name="team_manager" id="team_manager" class="form-control" 
                           value="<?php echo htmlspecialchars($_GET['team_manager'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="certificate_type" class="form-label">Certificate Type</label>
                    <select name="certificate_type" id="certificate_type" class="form-select" required>
                        <option value="1st">1st Place Individual</option>
                        <option value="2nd">2nd Place Individual</option>
                        <option value="3rd">3rd Place Individual</option>
                        <option value="team">Winning Team</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Generate Certificate</button>
            </form>
        </div>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/public/admin/includes/footer.php'; ?>