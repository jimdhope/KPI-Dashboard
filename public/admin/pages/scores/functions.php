<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/bootstrap.php';

function getExistingScores($db, $selectedPod, $selectedDate) {
    $scores = $db->query("
        SELECT user_id, rule_id, score 
        FROM daily_scores 
        WHERE pod_id = ? AND date = ?",
        [$selectedPod, $selectedDate])->fetchAll(PDO::FETCH_ASSOC);
    
    $existingScores = [];
    foreach ($scores as $score) {
        $existingScores[$score['user_id']][$score['rule_id']] = $score['score'];
    }
    return $existingScores;
}

function getPods($db) {
    return $db->query("SELECT * FROM pods ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
}

function getUsers($db, $selectedPod) {
    $usersQuery = "
        SELECT DISTINCT u.*, pa.pod_id, p.name as pod_name
        FROM users u 
        JOIN pod_assignments pa ON u.id = pa.staff_id
        JOIN pods p ON pa.pod_id = p.id 
        WHERE pa.pod_id = ?
        ORDER BY u.first_name ASC";
    
    return $db->query($usersQuery, [$selectedPod])->fetchAll(PDO::FETCH_ASSOC);
}

function getTeamInfo($db, $selectedPod) {
    return $db->query("
        SELECT DISTINCT t.id as team_id, t.name as team_name, c.id as competition_id, c.name as competition_name
        FROM teams t
        JOIN competitions c ON t.competition_id = c.id
        JOIN user_team ut ON ut.team_id = t.id
        JOIN users u ON u.id = ut.user_id
        JOIN pod_assignments pa ON pa.staff_id = u.id
        WHERE pa.pod_id = ?
        LIMIT 1", 
        [$selectedPod])->fetch(PDO::FETCH_ASSOC);
}

function getCompetitionRules($db, $competitionId) {
    return $db->query("
        SELECT id, name 
        FROM competition_rules 
        WHERE competition_id = ?",
        [$competitionId])->fetchAll(PDO::FETCH_ASSOC);
}

function getCompetitions($db) {
    return $db->query("SELECT * FROM competitions ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
}

function saveScores($db, $scores, $podId, $date, $competitionId) {
    $dbConnection = $db->getConnection();
    $stmt = $dbConnection->prepare("
        INSERT INTO daily_scores (user_id, rule_id, score, date, pod_id, competition_id)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE score = VALUES(score)
    ");

    foreach ($scores as $userId => $userScores) {
        foreach ($userScores as $ruleId => $score) {
            $stmt->execute([
                $userId,
                $ruleId,
                intval($score),
                $date,
                $podId,
                $competitionId
            ]);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'update_score') {
    $db = Database::getInstance();
    $userId = $_POST['user_id'];
    $ruleId = $_POST['rule_id'];
    $score = $_POST['score'];
    $date = $_POST['date'];
    $podId = $_POST['pod_id'];
    $competitionId = $_POST['competition_id'];

    try {
        saveScores($db, [$userId => [$ruleId => $score]], $podId, $date, $competitionId);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}
?>