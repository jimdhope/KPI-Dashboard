<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/bootstrap.php';

$db = Database::getInstance()->getConnection();

function getPods($pdo) {
    return $pdo->query("SELECT * FROM pods ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
}

function getCompetitions($pdo, $selectedPod) {
    $stmt = $pdo->prepare("SELECT * FROM competitions WHERE id IN (SELECT competition_id FROM teams WHERE id IN (SELECT team_id FROM user_team WHERE user_id IN (SELECT staff_id FROM pod_assignments WHERE pod_id = ?))) ORDER BY start_date DESC");
    $stmt->execute([$selectedPod]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getSavedTargets($pdo, $selectedPod, $selectedDate) {
    $stmt = $pdo->prepare("
        SELECT pt.rule_id, pt.target_value, cr.name as rule_name
        FROM pod_targets pt
        JOIN competition_rules cr ON pt.rule_id = cr.id
        WHERE pt.pod_id = ? AND pt.date = ?
    ");
    $stmt->execute([$selectedPod, $selectedDate]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRules($pdo, $selectedCompetition) {
    $stmt = $pdo->prepare("SELECT * FROM competition_rules WHERE competition_id = ? ORDER BY name");
    $stmt->execute([$selectedCompetition]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPodMembers($pdo, $selectedPod, $selectedDate) {
    $stmt = $pdo->prepare("
        SELECT u.id, u.first_name, u.last_name, ds.score, ds.rule_id
        FROM users u
        LEFT JOIN daily_scores ds ON u.id = ds.user_id AND ds.date = ? AND ds.pod_id = ?
        WHERE u.id IN (SELECT staff_id FROM pod_assignments WHERE pod_id = ?)
        ORDER BY u.first_name ASC
    ");
    $stmt->execute([$selectedDate, $selectedPod, $selectedPod]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function formatTeamsContent($results, $selectedRule1, $selectedRule2) {
    $teamsFormattedContent = '';
    if (!empty($results)) {
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
    return $teamsFormattedContent;
}

function calculateResults($pdo, $selectedPod, $selectedDate) {
    $displayResults = [];
    $ruleTotals = [];
    $ruleCounts = [];
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
            $displayResults[$name]['total'] += ($result['score'] * $result['points']);
        }

        if (isset($result['rule_id']) && isset($result['score'])) {
            if (!isset($ruleTotals[$result['rule_id']])) {
                $ruleTotals[$result['rule_id']] = 0;
            }
            $ruleTotals[$result['rule_id']] += ($result['score'] * ($result['points'] ?? 1));

            if (!isset($ruleCounts[$result['rule_id']])) {
                $ruleCounts[$result['rule_id']] = 0;
            }
            $ruleCounts[$result['rule_id']] += $result['score'];
        }
    }

    return [$displayResults, $ruleTotals, $ruleCounts];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_targets') {
    try {
        $podId = intval($_POST['pod_id']);
        $date = $_POST['date'];
        $rule1Id = !empty($_POST['rule1']) ? intval($_POST['rule1']) : null;
        $rule2Id = !empty($_POST['rule2']) ? intval($_POST['rule2']) : null;
        $target1 = isset($_POST['target1']) ? intval($_POST['target1']) : null;
        $target2 = isset($_POST['target2']) ? intval($_POST['target2']) : null;

        $db->beginTransaction();

        $stmt = $db->prepare("DELETE FROM pod_targets WHERE pod_id = ? AND date = ?");
        $stmt->execute([$podId, $date]);

        $stmt = $db->prepare("INSERT INTO pod_targets (pod_id, rule_id, target_value, date) VALUES (?, ?, ?, ?)");

        if ($rule1Id && $target1 !== null) {
            $stmt->execute([$podId, $rule1Id, $target1, $date]);
        }
        if ($rule2Id && $target2 !== null) {
            $stmt->execute([$podId, $rule2Id, $target2, $date]);
        }

        $db->commit();
        echo json_encode(['success' => true]);
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Target update error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit();
    }
}
?>