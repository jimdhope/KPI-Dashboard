<?php
// public/admin/pages/daily_scores/functions.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/bootstrap.php';

function getDailyResultsData($pdo, $selectedDate, $selectedPod) {
    $results = [];
    $displayResults = [];
    $ruleCounts = [];
    $rules = [];

    if ($selectedPod) {
        // Fetch active competitions for the selected pod and date
        $activeCompetitions = [];
        $stmt = $pdo->prepare("
            SELECT c.id
            FROM competitions c
            JOIN teams t ON c.id = t.competition_id
            JOIN user_team ut ON t.id = ut.team_id
            JOIN pod_assignments pa ON ut.user_id = pa.staff_id
            WHERE pa.pod_id = ?
            AND c.start_date <= ?
            AND c.end_date >= ?
        ");
        $stmt->execute([$selectedPod, $selectedDate, $selectedDate]);
        $activeCompetitions = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Fetch rules for the active competitions
        if (!empty($activeCompetitions)) {
            $placeholders = implode(',', array_fill(0, count($activeCompetitions), '?'));
            $stmt = $pdo->prepare("
                SELECT cr.name, cr.emoji
                FROM competition_rules cr
                WHERE cr.competition_id IN ($placeholders)
                ORDER BY cr.name
            ");
            $stmt->execute($activeCompetitions);
            $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Get all pod members first
        $stmt = $pdo->prepare("
            SELECT u.id, u.first_name, u.last_name, ds.score, ds.rule_id, cr.emoji
            FROM users u
            LEFT JOIN daily_scores ds ON u.id = ds.user_id AND ds.date = ? AND ds.pod_id = ?
            LEFT JOIN competition_rules cr ON ds.rule_id = cr.id
            WHERE u.id IN (SELECT staff_id FROM pod_assignments WHERE pod_id = ?)
            ORDER BY u.first_name ASC
        ");
        $stmt->execute([$selectedDate, $selectedPod, $selectedPod]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $result) {
            $name = htmlspecialchars($result['first_name'] . ' ' . $result['last_name']);
            $emoji = htmlspecialchars($result['emoji'] ?? '');
            $score = $result['score'] ?? 0;

            if (!isset($displayResults[$name])) {
                $displayResults[$name] = ['emojis' => '', 'total' => 0];
            }
            
            if ($emoji) {
                $displayResults[$name]['emojis'] .= $emoji;
            }
            $displayResults[$name]['total'] += $score;

            if ($result['rule_id']) {
                if (!isset($ruleCounts[$result['rule_id']])) {
                    $ruleCounts[$result['rule_id']] = 0;
                }
                $ruleCounts[$result['rule_id']]++;
            }
        }
    }

    return ['rules' => $rules, 'displayResults' => $displayResults];
}
?>