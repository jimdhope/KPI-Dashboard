<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../../includes/bootstrap.php';
require_once __DIR__ . '/functions.php';

$pageTitle = 'Generate Certificates';
require_once __DIR__ . '/../../includes/header.php';

// Handle AJAX requests before any HTML output
if(isset($_GET['action'])) {
    header('Content-Type: application/json');
    ob_clean(); // Clear any previous output
    try {
        $db = Database::getInstance();
        switch($_GET['action']) {
            case 'get_pod_members':
                $stmt = $db->getConnection()->prepare("
                    SELECT DISTINCT u.id, u.first_name 
                    FROM users u 
                    JOIN pod_assignments pa ON u.id = pa.staff_id 
                    WHERE pa.pod_id = ?
                    ORDER BY u.first_name
                ");
                $stmt->execute([$_GET['pod_id']]);
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
                exit;
            
            case 'get_teams':
                $stmt = $db->getConnection()->prepare("
                    SELECT id, name 
                    FROM teams 
                    WHERE competition_id = ?
                    ORDER BY name
                ");
                $stmt->execute([$_GET['competition_id']]);
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
                exit;
        }
    } catch(Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

try {
    $db = Database::getInstance();
    $dbConnection = $db->getConnection();

    // Get pods
    $stmt = $dbConnection->prepare("SELECT * FROM pods ORDER BY name");
    $stmt->execute();
    $pods = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get competitions
    $stmt = $dbConnection->prepare("SELECT * FROM competitions ORDER BY start_date DESC");
    $stmt->execute();
    $competitions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
        if (!empty($_POST['pod']) && !empty($_POST['competition']) && !empty($_POST['team_manager'])) {
            generateCertificates($_POST);
        }
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

<div class="container py-5">
    <h1 class="mb-4">Generate Certificates</h1>

    <form method="POST" class="card mb-4" id="certificateForm">
        <input type="hidden" name="generate" value="1">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="pod" class="form-label">Pod</label>
                    <select id="pod" name="pod" class="form-select" required>
                        <option value="">Select Pod</option>
                        <?php foreach ($pods as $pod): ?>
                            <option value="<?php echo htmlspecialchars($pod['id']); ?>">
                                <?php echo htmlspecialchars($pod['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="competition" class="form-label">Competition</label>
                    <select id="competition" name="competition" class="form-select" required>
                        <option value="">Select Competition</option>
                        <?php foreach ($competitions as $competition): ?>
                            <option value="<?php echo htmlspecialchars($competition['id']); ?>" 
                                    data-end-date="<?php echo htmlspecialchars($competition['end_date']); ?>">
                                <?php echo htmlspecialchars($competition['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-12">
                    <label for="winning_team" class="form-label">Winning Team</label>
                    <select id="winning_team" name="winning_team" class="form-select" required>
                        <option value="">Select Team</option>
                    </select>
                </div>

                <div class="col-md-12">
                    <label for="team_members" class="form-label">Team Members</label>
                    <select id="team_members" name="team_members[]" class="form-select" multiple required>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="first_place" class="form-label">First Place</label>
                    <select id="first_place" name="first_place" class="form-select" required>
                        <option value="">Select Winner</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="second_place" class="form-label">Second Place</label>
                    <select id="second_place" name="second_place" class="form-select" required>
                        <option value="">Select Winner</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="third_place" class="form-label">Third Place</label>
                    <select id="third_place" name="third_place" class="form-select" required>
                        <option value="">Select Winner</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="team_manager" class="form-label">Team Manager</label>
                    <input type="text" id="team_manager" name="team_manager" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label for="competition_date" class="form-label">Competition Date</label>
                    <input type="date" id="competition_date" name="competition_date" class="form-control" readonly>
                </div>
            </div>
        </div>
        <div class="card-footer text-end">
            <button type="submit" class="btn btn-primary">Generate All Certificates</button>
        </div>
    </form>
</div>

<script src="/public/admin/pages/certs/scripts.js"></script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>