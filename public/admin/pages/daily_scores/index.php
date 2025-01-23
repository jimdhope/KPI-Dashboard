<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/bootstrap.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/public/admin/includes/header.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/public/admin/pages/daily_scores/functions.php';

// Initialize variables
$db = Database::getInstance();
$pdo = $db->getConnection(); // Ensure this returns a PDO instance
$pageTitle = 'Daily Results';

// Get selections
$selectedDate = $_GET['date'] ?? date('Y-m-d');
$selectedPod = $_GET['pod'] ?? '';
$selectedCompetition = $_GET['competition'] ?? '';

// Fetch pods for dropdown
$pods = getPods($pdo);

// Fetch competitions for dropdown based on selected pod
$competitions = [];
if ($selectedPod) {
    $competitions = getCompetitions($pdo, $selectedPod);
}

// Initialize rule and target variables
$selectedRule1 = '';
$selectedRule2 = '';
$target1 = 0;
$target2 = 0;

// Get saved targets first
$savedTargets = [];
if ($selectedPod) {
    $savedTargets = getSavedTargets($pdo, $selectedPod, $selectedDate);

    // Set default selections from saved targets
    if (!empty($savedTargets)) {
        $selectedRule1 = $_GET['rule1'] ?? $savedTargets[0]['rule_id'] ?? '';
        $selectedRule2 = $_GET['rule2'] ?? ($savedTargets[1]['rule_id'] ?? '');
        $target1 = isset($_GET['target1']) ? intval($_GET['target1']) : $savedTargets[0]['target_value'] ?? 0;
        $target2 = isset($_GET['target2']) ? intval($_GET['target2']) : $savedTargets[1]['target_value'] ?? 0;
    } else {
        $selectedRule1 = $_GET['rule1'] ?? '';
        $selectedRule2 = $_GET['rule2'] ?? '';
        $target1 = isset($_GET['target1']) && $_GET['target1'] !== '' ? intval($_GET['target1']) : null;
        $target2 = isset($_GET['target2']) && $_GET['target2'] !== '' ? intval($_GET['target2']) : null;
    }
}

// Fetch all rules for dropdowns and key based on selected competition
$rules = [];
if ($selectedCompetition) {
    $rules = getRules($pdo, $selectedCompetition);
}

// Get all pod members first
$results = [];
if ($selectedPod) {
    $results = getPodMembers($pdo, $selectedPod, $selectedDate);
}

// Format content for Teams
$teamsFormattedContent = formatTeamsContent($results, $selectedRule1, $selectedRule2);

// Initialize displayResults and ruleTotals
$displayResults = [];
$ruleTotals = [];
$ruleCounts = []; // New array to count rule achievements
if ($selectedPod) {
    list($displayResults, $ruleTotals, $ruleCounts) = calculateResults($pdo, $selectedPod, $selectedDate);
}
?>
<link rel="stylesheet" href="/public/admin/pages/daily_scores/style.css">
<div class="container py-5">
    <h1 class="mb-4" style="color: #eeeeee;">Daily Scorecard</h1>

    <!-- Date, Pod, and Competition Selectors -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="selectionForm" method="GET" class="row g-3">
                <div class="col-md-4">
                    <!-- Date Selector -->
                    <label for="date" class="form-label">Date</label>
                    <input type="date" id="date" name="date" class="form-control" value="<?php echo htmlspecialchars($selectedDate); ?>">
                </div>
                <div class="col-md-4">
                    <!-- Pod Selector -->
                    <label for="pod" class="form-label">Pod</label>
                    <select id="pod" name="pod" class="form-select">
                        <option value="">Select Pod</option>
                        <?php foreach ($pods as $pod): ?>
                            <option value="<?php echo $pod['id']; ?>" <?php echo $selectedPod == $pod['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pod['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <!-- Competition Selector -->
                    <label for="competition" class="form-label">Competition</label>
                    <select id="competition" name="competition" class="form-select">
                        <option value="">Select Competition</option>
                        <?php foreach ($competitions as $competition): ?>
                            <option value="<?php echo $competition['id']; ?>" <?php echo $selectedCompetition == $competition['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($competition['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <?php if ($selectedPod && $selectedCompetition): ?>
        <!-- Target Settings Card -->
        <div class="card mb-4">
            <div class="card-body">
                <form id="targetForm" method="POST" action="functions.php">
                    <input type="hidden" name="action" value="update_targets">
                    <input type="hidden" name="pod_id" value="<?php echo htmlspecialchars($selectedPod); ?>">
                    <input type="hidden" name="date" value="<?php echo htmlspecialchars($selectedDate); ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="rule1" class="form-label">Rule 1</label>
                            <select id="rule1" name="rule1" class="form-select">
                                <option value="">Select Rule</option>
                                <?php foreach ($rules as $rule): ?>
                                    <option value="<?php echo $rule['id']; ?>" <?php echo $selectedRule1 == $rule['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($rule['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="target1" class="form-label">Target 1</label>
                            <input type="number" id="target1" name="target1" class="form-control" value="<?php echo htmlspecialchars($target1); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="rule2" class="form-label">Rule 2</label>
                            <select id="rule2" name="rule2" class="form-select">
                                <option value="">Select Rule</option>
                                <?php foreach ($rules as $rule): ?>
                                    <option value="<?php echo $rule['id']; ?>" <?php echo $selectedRule2 == $rule['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($rule['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="target2" class="form-label">Target 2</label>
                            <input type="number" id="target2" name="target2" class="form-control" value="<?php echo htmlspecialchars($target2); ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3">Save Targets</button>
                </form>
            </div>
        </div>

        <!-- Results Card -->
        <div class="card">
            <div class="card-body">
                <!-- Emoji Key -->
                <div class="mb-3">
                    <?php foreach ($rules as $rule): ?>
                        <span><?php echo htmlspecialchars($rule['emoji']) . ' ' . htmlspecialchars($rule['name']); ?></span>
                    <?php endforeach; ?>
                </div>

                <table class="table table-sm mb-0">
                    <tbody>
                        <?php foreach ($displayResults as $name => $data): ?>
                            <tr>
                                <td class="text-nowrap"><?php echo htmlspecialchars($name); ?></td>
                                <td class="text-nowrap"><?php echo $data['emojis']; ?></td>
                                <td class="text-nowrap text-end"><?php echo $data['total']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Target Totals -->
                <?php if ($selectedRule1 || $selectedRule2): ?>
                    <div class="mt-3 pt-3">
                        <div class="target-totals">
                            <?php 
                            $targetDisplay = [];
                            if ($selectedRule1) {
                                $rule1Name = $rules[array_search($selectedRule1, array_column($rules, 'id'))]['name'];
                                $rule1Emoji = $rules[array_search($selectedRule1, array_column($rules, 'id'))]['emoji'];
                                $targetDisplay[] = sprintf("%s %s: %d/%d",
                                    htmlspecialchars($rule1Name),
                                    htmlspecialchars($rule1Emoji),
                                    ($ruleCounts[$selectedRule1] ?? 0), // Use ruleCounts for achievements
                                    $target1
                                );
                            }
                            if ($selectedRule2) {
                                $rule2Name = $rules[array_search($selectedRule2, array_column($rules, 'id'))]['name'];
                                $rule2Emoji = $rules[array_search($selectedRule2, array_column($rules, 'id'))]['emoji'];
                                $targetDisplay[] = sprintf("%s %s: %d/%d",
                                    htmlspecialchars($rule2Name),
                                    htmlspecialchars($rule2Emoji),
                                    ($ruleCounts[$selectedRule2] ?? 0), // Use ruleCounts for achievements
                                    $target2
                                );
                            }
                            echo implode(' | ', $targetDisplay);
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/public/admin/includes/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const competitionSelect = document.getElementById('competition');
    if (competitionSelect) {
        competitionSelect.addEventListener('change', function() {
            console.log('Competition changed');
            document.getElementById('selectionForm').submit();
        });
    } else {
        console.error('Competition select element not found');
    }
});
</script>
<script src="/public/admin/pages/daily_scores/scripts.js"></script>