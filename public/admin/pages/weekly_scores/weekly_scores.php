<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/bootstrap.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/public/admin/pages/weekly_scores/functions.php';

// Define page title before header include
$pageTitle = 'Weekly Scores';

require_once $_SERVER['DOCUMENT_ROOT'] . '/public/admin/includes/header.php';

try {
    // Initialize variables
    $db = Database::getInstance();
    $dbConnection = $db->getConnection();

    // Get selections
    $selectedPod = $_GET['pod'] ?? '';
    $selectedCompetition = $_GET['competition'] ?? '';

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
    error_log("Weekly Scores Error: " . $e->getMessage());
    die("An error occurred loading the weekly scores");
}
?>

<div class="container py-5">
    <h1 class="mb-4">Weekly Scorecard</h1>

    <form method="GET" class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Pod</label>
                    <select name="pod" class="form-select" onchange="this.form.submit()">
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
                    <select name="competition" class="form-select" onchange="this.form.submit()">
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
            </div>
        </div>
    </form>

    <?php if ($selectedPod && $selectedCompetition && isset($startDate) && isset($endDate)): ?>
    <div class="row">
        <!-- Team Points Column -->
        <div class="col-md-6">
            <div class="card weekly-scorecard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Team Points</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($teamData as $index => $team): ?>
                        <div class="team-table position-<?php echo $index + 1; ?> d-flex">
                            <div class="team-info flex-grow-1">
                                <h2 class="team-name mb-0"><?php echo htmlspecialchars($team['team_name']); ?></h2>
                                <h4 class="team-members mb-0"><?php echo htmlspecialchars($team['members']); ?></h4>
                            </div>
                            <div class="points-cell d-flex align-items-center justify-content-center">
                                <?php echo number_format($team['total_points']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Leaderboard Column -->
        <div class="col-md-6">
            <div class="card weekly-scorecard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Leaderboard</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($leaderboardData as $index => $person): ?>
                        <div class="leaderboard-table position-<?php echo $index + 1; ?> d-flex">
                            <div class="person-name flex-grow-1">
                                <?php echo htmlspecialchars($person['first_name'] . ' ' . $person['last_name']); ?>
                            </div>
                            <div class="points-cell d-flex align-items-center justify-content-center">
                                <?php echo number_format($person['total_points']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Certificates Column -->
        <div class="col-md-6">
            <div class="card weekly-scorecard">
            <div class="card-header">
                <h5 class="card-title mb-0">Certificates</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="/public/admin/pages/certs/functions.php">
                <input type="hidden" name="pod_id" value="<?php echo htmlspecialchars($selectedPod); ?>">
                <input type="hidden" name="competition_id" value="<?php echo htmlspecialchars($selectedCompetition); ?>">
                <div class="mb-3">
                    <label for="team_manager" class="form-label">Team Manager</label>
                    <input type="text" class="form-control" id="team_manager" name="team_manager" required>
                </div>
                <div class="mb-3">
                    <label for="certificate_type" class="form-label">Certificate Type</label>
                    <select class="form-select" id="certificate_type" name="certificate_type" required>
                    <option value="1st">1st Place</option>
                    <option value="2nd">2nd Place</option>
                    <option value="3rd">3rd Place</option>
                    <option value="team">Winning Team</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Generate Certificate</button>
                </form>
            </div>
            </div>
        </div>

    </div>
    <?php endif; ?>
</div>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/public/admin/includes/footer.php'; ?>