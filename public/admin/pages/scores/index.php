<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/bootstrap.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/public/admin/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/public/admin/pages/scores/functions.php';

// Initialize variables
$db = Database::getInstance();
$pageTitle = 'Rules';

// Debug POST data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log('POST Data: ' . print_r($_POST, true));
}

// Get selections
$selectedDate = $_GET['date'] ?? date('Y-m-d');
$selectedPod = $_GET['pod'] ?? 'all';
$selectedCompetition = $_GET['competition'] ?? '';

// Get existing scores for the selected date and pod
$existingScores = getExistingScores($db, $selectedPod, $selectedDate);

// Fetch pods for dropdown
$pods = getPods($db);

// Fetch competitions for dropdown
$competitions = getCompetitions($db);

// Initialize arrays
$users = [];
$rules = [];
$teamInfo = [];

if ($selectedPod) {
    $users = getUsers($db, $selectedPod);

    // Get team and competition info for this pod
    $teamInfo = getTeamInfo($db, $selectedPod);
    
    // Get competition rules if we found a team
    if ($selectedCompetition) {
        $rules = getCompetitionRules($db, $selectedCompetition);
    }
}

// Form submission handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scores = $_POST['scores'] ?? [];
    $podId = intval($_POST['pod_id']);
    $date = $_POST['date'];
    $competitionId = $_POST['competition_id'];

    try {
        saveScores($db, $scores, $podId, $date, $competitionId);
        header("Location: index.php?pod=$podId&date=$date&competition=$competitionId&message=Scores saved successfully");
        exit();
    } catch (Exception $e) {
        error_log("Score save error: " . $e->getMessage());
        header("Location: index.php?pod=$podId&date=$date&competition=$competitionId&error=" . urlencode($e->getMessage()));
        exit();
    }
}
?>

<!-- Selection Form -->
<div class="container py-5">
    <h1 class="mb-4">Score Management</h1>

    <form method="GET">
        <div class="row mb-4">
            <div class="col-md-4">
                <label class="form-label">Date</label>
                <input type="date" name="date" class="form-control" 
                       value="<?php echo htmlspecialchars($selectedDate); ?>"
                       onchange="this.form.submit()">
            </div>
            <div class="col-md-4">
                <label class="form-label">Pod</label>
                <div class="d-flex">
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
            </div>
            <div class="col-md-4">
                <label class="form-label">Competition</label>
                <div class="d-flex">
                    <select name="competition" class="form-select" onchange="this.form.submit()">
                        <option value="">Select Competition</option>
                        <?php foreach ($competitions as $competition): ?>
                            <option value="<?php echo $competition['id']; ?>" 
                                    <?php echo $selectedCompetition == $competition['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($competition['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </form>

    <!-- Scores Table -->
    <?php if ($selectedPod && !empty($users) && !empty($rules)): ?>
        <div id="scoreUpdateStatus" class="alert" style="display:none;"></div>
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <?php foreach ($rules as $rule): ?>
                        <th><?php echo htmlspecialchars($rule['name']); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                        <?php foreach ($rules as $rule): ?>
                            <td>
                                <input type="number" 
                                    class="form-control score-input" 
                                    style="width: 70px;"
                                    value="<?php echo $existingScores[$user['id']][$rule['id']] ?? ''; ?>"
                                    data-user-id="<?php echo $user['id']; ?>"
                                    data-rule-id="<?php echo $rule['id']; ?>"
                                    data-pod-id="<?php echo $selectedPod; ?>"
                                    data-competition-id="<?php echo $selectedCompetition; ?>"> <!-- Use the selected competition ID -->
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <div id="scoresTableContainer">
        <!-- Scores table will be loaded here -->
    </div>
</div>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/public/admin/includes/footer.php'; ?>

<link rel="stylesheet" href="/public/admin/pages/scores/style.css">
<script src="/public/admin/pages/scores/scripts.js"></script>