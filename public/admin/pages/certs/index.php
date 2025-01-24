<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/bootstrap.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/public/admin/pages/weekly_scores/functions.php';

// Define page title before header include
$pageTitle = 'Generate Certificates';

require_once $_SERVER['DOCUMENT_ROOT'] . '/public/admin/includes/header.php';

try {
    // Initialize variables
    $db = Database::getInstance();
    $dbConnection = $db->getConnection();

    // Get selections
    $selectedPod = $_GET['pod'] ?? '';
    $selectedCompetition = $_GET['competition'] ?? '';
    $teamManager = $_GET['team_manager'] ?? '';
    $certificateType = $_GET['certificate_type'] ?? '';

    // Get competitions for dropdown
    $stmt = $dbConnection->prepare("SELECT * FROM competitions ORDER BY start_date DESC");
    $stmt->execute();
    $competitions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($selectedCompetition) {
        // Fetch competition date range
        $stmt2 = $dbConnection->prepare("SELECT start_date, end_date FROM competitions WHERE id = ?");
        $stmt2->execute([$selectedCompetition]);
        $competitionData = $stmt2->fetch(PDO::FETCH_ASSOC);

        $startDate = new DateTime($competitionData['start_date']);
        $endDate = new DateTime($competitionData['end_date']);
    }

    // Get pods for dropdown
    $stmt = $dbConnection->prepare("SELECT * FROM pods ORDER BY name");
    $stmt->execute();
    $pods = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($selectedPod && $selectedCompetition && isset($startDate) && isset($endDate)) {
        $teamData = getTeamPoints(
            $selectedPod, 
            $startDate->format('Y-m-d'), 
            $endDate->format('Y-m-d')
        );

        $leaderboardData = getLeaderboardData(
            $selectedPod,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );
    }
} catch (Exception $e) {
    error_log("Certificate Generation Error: " . $e->getMessage());
    die("An error occurred loading the certificate generation page");
}
?>

<link rel="stylesheet" href="/public/admin/pages/weekly_scores/style.css">

<div class="container py-5">
    <h1 class="mb-4">Generate Certificates</h1>

    <form method="GET" class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Pod</label>
                    <select name="pod" class="form-select" required>
                        <option value="">Select Pod</option>
                        <?php foreach ($pods as $pod): ?>
                            <option value="<?php echo $pod['id']; ?>" 
                                    <?php echo $selectedPod == $pod['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pod['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Competition</label>
                    <select name="competition" class="form-select" required>
                        <option value="">Select Competition</option>
                        <?php foreach ($competitions as $competition): ?>
                            <option value="<?php echo $competition['id']; ?>" 
                                    <?php echo $selectedCompetition == $competition['id'] ? 'selected' : ''; ?>>
                                <?php 
                                    echo htmlspecialchars(
                                        $competition['name'] 
                                        . " (" . $competition['start_date'] 
                                        . " - " . $competition['end_date'] . ")"
                                    ); 
                                ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Team Manager's Name</label>
                    <input type="text" name="team_manager" class="form-control" value="<?php echo htmlspecialchars($teamManager); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Certificate Type</label>
                    <select name="certificate_type" class="form-select" required>
                        <option value="">Select Certificate Type</option>
                        <option value="team" <?php echo $certificateType == 'team' ? 'selected' : ''; ?>>Team</option>
                        <option value="individual" <?php echo $certificateType == 'individual' ? 'selected' : ''; ?>>Individual</option>
                    </select>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Generate Certificates</button>
            </div>
        </div>
    </form>

    <?php if ($selectedPod && $selectedCompetition && isset($startDate) && isset($endDate) && $teamManager && $certificateType): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Certificates</h5>
                        <div id="certificates">
                            <!-- Certificates will be generated here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="/public/admin/pages/certs/scripts.js"></script>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/public/admin/includes/footer.php'; ?>