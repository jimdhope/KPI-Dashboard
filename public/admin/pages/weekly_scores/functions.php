<?php
function getTeamPoints($podId, $startDate, $endDate) {
    global $db;
    $teamQuery = "
        WITH MemberPoints AS (
            SELECT 
                u.id,
                u.first_name,
                COALESCE(SUM(ds.score * cr.points), 0) as user_points
            FROM users u
            JOIN pod_assignments pa ON u.id = pa.staff_id AND pa.pod_id = ?
            LEFT JOIN daily_scores ds ON u.id = ds.user_id 
                AND ds.date BETWEEN ? AND ?
            LEFT JOIN competition_rules cr ON ds.rule_id = cr.id
                AND cr.competition_id = ds.competition_id
            GROUP BY u.id, u.first_name
        )
        SELECT 
            c.name as competition_name,
            t.name as team_name,
            GROUP_CONCAT(DISTINCT mp.first_name ORDER BY mp.first_name SEPARATOR ', ') as members,
            COALESCE(SUM(mp.user_points), 0) as total_points
        FROM competitions c
        INNER JOIN teams t ON t.competition_id = c.id
        INNER JOIN user_team ut ON ut.team_id = t.id
        INNER JOIN MemberPoints mp ON mp.id = ut.user_id
        WHERE c.start_date = ? 
        AND c.end_date = ?
        GROUP BY c.id, t.id, c.name, t.name
        ORDER BY c.name, total_points DESC";

    return $db->query($teamQuery, [
        $podId, 
        $startDate, 
        $endDate,
        $startDate,
        $endDate
    ])->fetchAll(PDO::FETCH_ASSOC);
}

function getLeaderboardData($podId, $startDate, $endDate) {
    global $db;
    $leaderboardQuery = "
        SELECT 
            u.first_name,
            u.last_name,
            COALESCE(SUM(ds.score * cr.points), 0) as total_points
        FROM users u
        JOIN pod_assignments pa ON u.id = pa.staff_id
        LEFT JOIN daily_scores ds ON u.id = ds.user_id 
            AND ds.date BETWEEN ? AND ?
        LEFT JOIN competition_rules cr ON ds.rule_id = cr.id
        WHERE pa.pod_id = ?
        GROUP BY u.id, u.first_name, u.last_name
        ORDER BY total_points DESC";

    return $db->query($leaderboardQuery, [$startDate, $endDate, $podId])->fetchAll(PDO::FETCH_ASSOC);
}