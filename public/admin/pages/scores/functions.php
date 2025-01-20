<?php
// public/admin/pages/scores/functions.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/bootstrap.php';

if (isset($_POST['action']) && $_POST['action'] === 'update_score') {
    $userId = $_POST['user_id'];
    $ruleId = $_POST['rule_id'];
    $score = $_POST['score'];
    $date = $_POST['date'];
    $podId = $_POST['pod_id'];
    $competitionId = $_POST['competition_id'];

    $result = updateDailyScore($db->getConnection(), $userId, $ruleId, $score, $date, $podId, $competitionId);
    header('Content-Type: application/json');
    echo json_encode($result);
    exit();
}

function updateDailyScore($pdo, $userId, $ruleId, $score, $date, $podId, $competitionId) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO daily_scores (user_id, rule_id, score, date, pod_id, competition_id)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE score = VALUES(score)
        ");
        $stmt->execute([$userId, $ruleId, intval($score), $date, $podId, $competitionId]);
        return ['success' => true];
    } catch (Exception $e) {
        error_log("Score update error: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
?>