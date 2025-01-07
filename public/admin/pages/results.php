<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/bootstrap.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/public/admin/includes/header.php';

// Initialize variables
$db = Database::getInstance();
$pdo = $db->getConnection(); // Ensure this returns a PDO instance
$pageTitle = 'Daily Results';

// Get selections
$selectedDate = $_GET['date'] ?? date('Y-m-d');
$selectedPod = $_GET['pod'] ?? '';

// Get saved targets first
$savedTargets = [];
if ($selectedPod) {
    $stmt = $pdo->prepare("
        SELECT pt.rule_id, pt.target_value, cr.name as rule_name
        FROM pod_targets pt
        JOIN competition_rules cr ON pt.rule_id = cr.id
        WHERE pt.pod_id = ? AND pt.date = ?
    ");
    $stmt->execute([$selectedPod, $selectedDate]);
    $savedTargets = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

// Fetch pods for dropdown
$pods = $pdo->query("SELECT * FROM pods ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all rules for dropdowns and key
$rules = $pdo->query("SELECT * FROM competition_rules ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get all pod members first
$results = [];
if ($selectedPod) {
    $stmt = $pdo->prepare("
        SELECT u.id, u.first_name, u.last_name, ds.score, ds.rule_id
        FROM users u
        LEFT JOIN daily_scores ds ON u.id = ds.user_id AND ds.date = ? AND ds.pod_id = ?
        WHERE u.id IN (SELECT staff_id FROM pod_assignments WHERE pod_id = ?)
        ORDER BY u.first_name ASC
    ");
    $stmt->execute([$selectedDate, $selectedPod, $selectedPod]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Format content for Teams
$teamsFormattedContent = '';
if ($selectedPod && !empty($results)) {
    foreach ($results as $result) {
        $score1 = isset($result['score']) && $result['rule_id'] == $selectedRule1 ? $result['score'] : '';
        $score2 = isset($result['score']) && $result['rule_id'] == $selectedRule2 ? $result['score'] : '';
        $teamsFormattedContent .= "<tr>
            <td>{$result['first_name']} {$result['last_name']}</td>
            <td><input type='number' name='scores[{$result['id']}][{$selectedRule1}]' value='{$score1}'></td>
            <td><input type='number' name='scores[{$result['id']}][{$selectedRule2}]' value='{$score2}'></td>
        </tr>";
    }
}

// Initialize displayResults and ruleTotals
$displayResults = [];
$ruleTotals = [];
if ($selectedPod) {
    $stmt = $pdo->prepare("
        SELECT u.first_name, u.last_name, ds.rule_id, ds.score, cr.emoji, cr.points
        FROM users u
        LEFT JOIN daily_scores ds ON u.id = ds.user_id AND ds.date = ? AND ds.pod_id = ?
        LEFT JOIN competition_rules cr ON ds.rule_id = cr.id
        WHERE u.id IN (SELECT staff_id FROM pod_assignments WHERE pod_id = ?)
        ORDER BY u.first_name ASC
    ");
    $stmt->execute([$selectedDate, $selectedPod, $selectedPod]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as $result) {
        $name = "{$result['first_name']} {$result['last_name']}";
        if (!isset($displayResults[$name])) {
            $displayResults[$name] = ['emojis' => '', 'total' => 0];
        }
        if (isset($result['emoji'])) {
            $displayResults[$name]['emojis'] .= str_repeat($result['emoji'], $result['score']);
        }
        if (isset($result['score']) && isset($result['points'])) {
            // Multiply score (times achieved) by points value for the rule
            $displayResults[$name]['total'] += ($result['score'] * $result['points']);
        }
    
        // Calculate rule totals
        if (isset($result['rule_id']) && isset($result['score'])) {
            if (!isset($ruleTotals[$result['rule_id']])) {
                $ruleTotals[$result['rule_id']] = 0;
            }
            $ruleTotals[$result['rule_id']] += ($result['score'] * ($result['points'] ?? 1));
        }
    }
}

// Add copy button and pre-formatted content
?>

<div class="container py-5">
    <h1 class="mb-4" style="color: #eeeeee;">Daily Scorecard</h1>

    <!-- Date and Pod Selectors - Moved outside selectedPod condition -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="selectionForm" method="GET" class="row g-3">
                <div class="col-md-6">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" id="date" name="date" class="form-control" value="<?php echo htmlspecialchars($selectedDate); ?>">
                </div>
                <div class="col-md-6">
                    <label for="pod" class="form-label">Pod</label>
                    <select id="pod" name="pod" class="form-select">
                        <option value="">Select Pod</option>
                        <?php foreach ($pods as $pod): ?>
                            <option value="<?php echo $pod['id']; ?>" <?php echo ($selectedPod == $pod['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pod['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <?php if ($selectedPod): ?>
        <!-- Target Settings Card -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Target Settings</h5>
                <form id="targetForm" method="POST" action="functions.php">
                    <input type="hidden" name="action" value="update_targets">
                    <input type="hidden" name="pod_id" value="<?php echo $selectedPod; ?>">
                    <input type="hidden" name="date" value="<?php echo $selectedDate; ?>">
                    <div class="row g-3">
                        <!-- Rule 1 Settings -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Rule 1</h6>
                                    <div class="d-flex flex-column gap-2">
                                        <select name="rule1" class="form-select">
                                            <option value="">Select Rule</option>
                                            <?php foreach ($rules as $rule): ?>
                                                <option value="<?php echo $rule['id']; ?>" <?php echo ($selectedRule1 == $rule['id']) ? 'selected' : ''; ?>>
                                                    <?php echo $rule['emoji'] . ' ' . htmlspecialchars($rule['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="number" name="target1" class="form-control" placeholder="Target" value="<?php echo $target1 !== null ? $target1 : ''; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Rule 2 Settings -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2">Rule 2</h6>
                                    <div class="d-flex flex-column gap-2">
                                        <select name="rule2" class="form-select">
                                            <option value="">Select Rule</option>
                                            <?php foreach ($rules as $rule): ?>
                                                <option value="<?php echo $rule['id']; ?>" <?php echo ($selectedRule2 == $rule['id']) ? 'selected' : ''; ?>>
                                                    <?php echo $rule['emoji'] . ' ' . htmlspecialchars($rule['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="number" name="target2" class="form-control" placeholder="Target" value="<?php echo $target2 !== null ? $target2 : ''; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Save Targets</button>
                        </div>
                    </div>
                </form>

                <script>
                document.getElementById('targetForm').addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    
                    fetch('functions.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show success message
                            alert('Targets saved successfully');
                            // Reload page with current selections
                            const currentUrl = new URL(window.location.href);
                            currentUrl.searchParams.set('date', document.getElementById('date').value);
                            currentUrl.searchParams.set('pod', document.getElementById('pod').value);
                            window.location.href = currentUrl.toString();
                        } else {
                            alert('Failed to save targets: ' + (data.error || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to save targets');
                    });
                });

                // Auto-submit selection form when changes occur
                document.querySelectorAll('#selectionForm select, #selectionForm input').forEach(element => {
                    element.addEventListener('change', function() {
                        document.getElementById('selectionForm').submit();
                    });
                });
                </script>
            </div>
        </div>

        <!-- Results Card -->
        <div class="card">
            <div class="card-body">
                <!-- Rules Key -->
                <?php if (!empty($rules)): ?>
                    <div class="rules-key mb-3">
                        <?php foreach ($rules as $rule): ?>
                            <span class="me-3">
                                <?php echo $rule['emoji'] . ' ' . htmlspecialchars($rule['name']); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Results Table -->
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
                        <div class="d-flex align-items-center">
                            <?php 
                            $targetDisplay = [];
                            if ($selectedRule1) {
                                $rule1Name = $rules[array_search($selectedRule1, array_column($rules, 'id'))]['name'];
                                $targetDisplay[] = sprintf("%s: %d/%d",
                                    htmlspecialchars($rule1Name),
                                    ($ruleTotals[$selectedRule1] ?? 0),
                                    $target1
                                );
                            }
                            if ($selectedRule2) {
                                $rule2Name = $rules[array_search($selectedRule2, array_column($rules, 'id'))]['name'];
                                $targetDisplay[] = sprintf("%s: %d/%d",
                                    htmlspecialchars($rule2Name),
                                    ($ruleTotals[$selectedRule2] ?? 0),
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
    const resultsForm = document.getElementById('resultsForm');
    const rule1Select = document.querySelector('select[name="rule1"]');
    const rule2Select = document.querySelector('select[name="rule2"]');
    const target1Input = document.querySelector('input[name="target1"]');
    const target2Input = document.querySelector('input[name="target2"]');

    function updateTargets(event) {
        event.preventDefault();
        const formData = new FormData(resultsForm);
        
        fetch(resultsForm.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update target displays
                if (data.ruleTotals) {
                    Object.keys(data.ruleTotals).forEach(ruleId => {
                        const targetElement = document.querySelector(`[data-rule-target="${ruleId}"]`);
                        if (targetElement) {
                            targetElement.textContent = `${data.ruleTotals[ruleId].name}: ${data.ruleTotals[ruleId].current}/${data.ruleTotals[ruleId].target}`;
                        }
                    });
                }
            }
        })
        .catch(error => console.error('Error:', error));
    }

    // Add change event listeners
    [rule1Select, rule2Select, target1Input, target2Input].forEach(element => {
        if (element) {
            element.addEventListener('change', updateTargets);
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const targetForm = document.getElementById('targetForm');

    targetForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(targetForm);
        
        fetch('functions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the target totals display
                const targetDisplay = document.querySelector('.target-totals');
                if (targetDisplay && data.ruleTotals) {
                    let displayHtml = [];
                    Object.keys(data.ruleTotals).forEach(ruleId => {
                        const ruleData = data.ruleTotals[ruleId];
                        displayHtml.push(`${ruleData.name}: ${ruleData.current}/${ruleData.target}`);
                    });
                    targetDisplay.innerHTML = displayHtml.join(' | ');
                }
            } else {
                console.error('Failed to save targets:', data.error);
            }
        })
        .catch(error => console.error('Error:', error));
    });
});

document.getElementById('pod').addEventListener('change', function() {
    document.getElementById('selectionForm').submit();
});

document.getElementById('date').addEventListener('change', function() {
    document.getElementById('selectionForm').submit();
});

document.getElementById('targetForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('functions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('date', document.getElementById('date').value);
            currentUrl.searchParams.set('pod', document.getElementById('pod').value);
            window.location.href = currentUrl.toString();
        } else {
            alert('Failed to save targets: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to save targets');
    });
});
</script>