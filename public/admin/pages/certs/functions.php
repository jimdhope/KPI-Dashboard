<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/bootstrap.php';

function formatDate($date) {
    $timestamp = strtotime($date);
    return date('d M Y', $timestamp);
}

function getTemplateFile($certificateType) {
    $templateFiles = [
        '1st' => '1st.svg',
        '2nd' => '2nd.svg',
        '3rd' => '3rd.svg',
        'team' => 'team.svg'
    ];

    if (!isset($templateFiles[$certificateType])) {
        throw new Exception("Invalid certificate type: " . $certificateType);
    }

    $templateFile = $templateFiles[$certificateType];
    $paths = [
        $_SERVER['DOCUMENT_ROOT'] . '/public/admin/certs/' . $templateFile,
        $_SERVER['DOCUMENT_ROOT'] . '/public/admin/pages/certs/' . $templateFile,
        dirname(__FILE__) . '/' . $templateFile
    ];

    error_log("Checking template paths:");
    foreach ($paths as $path) {
        error_log("Checking path: " . $path);
        if (file_exists($path)) {
            error_log("Found template at: " . $path);
            return $path;
        }
    }
    throw new Exception("Certificate template not found. Please ensure template exists in one of these locations: " . implode(', ', $paths));
}

function getFontFile() {
    $paths = [
        $_SERVER['DOCUMENT_ROOT'] . '/public/admin/certs/fonts/trebuc.ttf',
        $_SERVER['DOCUMENT_ROOT'] . '/public/admin/pages/certs/fonts/trebuc.ttf',
        dirname(__FILE__) . '/fonts/trebuc.ttf'
    ];

    error_log("Checking font paths:");
    foreach ($paths as $path) {
        error_log("Checking path: " . $path);
        if (file_exists($path)) {
            error_log("Found font at: " . $path);
            return $path;
        }
    }
    throw new Exception("Font file not found. Please ensure font exists in one of these locations: " . implode(', ', $paths));
}

function calculateFontSize($text, $maxWidth, $initialFontSize) {
    try {
        $fontSize = $initialFontSize;
        $fontPath = getFontFile();
        $minFontSize = 12;

        while ($fontSize > $minFontSize) {
            $bbox = imagettfbbox($fontSize, 0, $fontPath, $text);
            if ($bbox === false) {
                throw new Exception("Failed to calculate text bounds");
            }
            $textWidth = $bbox[2] - $bbox[0];

            if ($textWidth <= $maxWidth) {
                break;
            }
            $fontSize--;
        }

        return $fontSize;
    } catch (Exception $e) {
        error_log("Font size calculation error: " . $e->getMessage());
        return $initialFontSize;
    }
}

function getFontBase64($fontPath) {
    try {
        if (!file_exists($fontPath)) {
            throw new Exception("Font file not found: " . $fontPath);
        }
        return base64_encode(file_get_contents($fontPath));
    } catch (Exception $e) {
        error_log("Font encoding error: " . $e->getMessage());
        throw $e;
    }
}

function getWinningTeam($podId, $competitionId) {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("
        SELECT 
            t.name as team_name,
            GROUP_CONCAT(DISTINCT u.first_name ORDER BY u.first_name SEPARATOR ', ') as team_members,
            COALESCE(SUM(ds.score * cr.points), 0) as total_points
        FROM teams t
        JOIN user_team ut ON t.id = ut.team_id
        JOIN users u ON ut.user_id = u.id
        LEFT JOIN daily_scores ds ON u.id = ds.user_id 
            AND ds.pod_id = ? 
            AND ds.competition_id = ?
        LEFT JOIN competition_rules cr ON ds.rule_id = cr.id
            AND cr.competition_id = ds.competition_id
        WHERE t.competition_id = ?
        GROUP BY t.id, t.name
        ORDER BY total_points DESC
        LIMIT 1"
    );

    $stmt->execute([$podId, $competitionId, $competitionId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getIndividualWinner($podId, $competitionId, $position) {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("
        WITH RankedUsers AS (
            SELECT 
                u.id,
                CONCAT(u.first_name, ' ', u.last_name) as full_name,
                COALESCE(SUM(ds.score * cr.points), 0) as total_points,
                ROW_NUMBER() OVER (ORDER BY COALESCE(SUM(ds.score * cr.points), 0) DESC) as rank
            FROM users u
            JOIN pod_assignments pa ON u.id = pa.staff_id
            LEFT JOIN daily_scores ds ON u.id = ds.user_id 
                AND ds.pod_id = ?
                AND ds.competition_id = ?
            LEFT JOIN competition_rules cr ON ds.rule_id = cr.id
                AND cr.competition_id = ds.competition_id
            WHERE pa.pod_id = ?
            GROUP BY u.id, u.first_name, u.last_name
        )
        SELECT full_name, total_points
        FROM RankedUsers
        WHERE rank = ?"
    );

    $stmt->execute([$podId, $competitionId, $podId, $position]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function generateCertificate($podId, $competitionId, $teamManager, $certificateType) {
    try {
        $db = Database::getInstance()->getConnection();

        // Get competition details
        $stmt = $db->prepare("SELECT name, end_date FROM competitions WHERE id = ?");
        $stmt->execute([$competitionId]);
        $competition = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$competition) {
            throw new Exception("Competition not found");
        }

        // Get pod details
        $stmt = $db->prepare("SELECT name FROM pods WHERE id = ?");
        $stmt->execute([$podId]);
        $pod = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pod) {
            throw new Exception("Pod not found");
        }

        if ($certificateType === 'team') {
            $winner = getWinningTeam($podId, $competitionId);
            if (!$winner) {
                throw new Exception("No winning team found");
            }
            $winnerName = $winner['team_name'];
            $members = $winner['team_members'];
        } else {
            $position = intval(substr($certificateType, 0, 1));
            $winner = getIndividualWinner($podId, $competitionId, $position);
            if (!$winner) {
                throw new Exception("Individual winner not found");
            }
            $winnerName = $winner['full_name'];
            $members = '';
        }

        $templatePath = getTemplateFile($certificateType);
        $svgTemplate = file_get_contents($templatePath);
        if ($svgTemplate === false) {
            throw new Exception("Failed to read certificate template");
        }

        $fontPath = getFontFile();
        $fontBase64 = getFontBase64($fontPath);

        $nameFontSize = calculateFontSize($winnerName, 400, 40);
        $competitionNameFontSize = calculateFontSize($competition['name'], 400, 30);

        $replacements = [
            '{{Name}}' => htmlspecialchars($winnerName),
            '{{Pod Name}}' => htmlspecialchars($pod['name']),
            '{{Team Manager}}' => htmlspecialchars($teamManager),
            '{{Date}}' => formatDate($competition['end_date']),
            '{{font_base64}}' => $fontBase64,
            '{{name_font_size}}' => $nameFontSize,
            '{{competition_name_font_size}}' => $competitionNameFontSize,
            '{{Team Name}}' => htmlspecialchars($winnerName),
            '{{Members}}' => htmlspecialchars($members)
        ];

        $certificateContent = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $svgTemplate
        );

        // Convert SVG to PNG
        $imagick = new Imagick();
        $imagick->readImageBlob($certificateContent);
        $imagick->setImageFormat("png");

        // Output the certificate for download
        $fileName = sprintf('%s-%s.png', htmlspecialchars($competition['name']), htmlspecialchars($certificateType));
        header('Content-Type: image/png');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        echo $imagick->getImageBlob();
        exit;
    } catch (Exception $e) {
        error_log("Certificate Generation Error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        echo "An error occurred while generating the certificate. Please check the logs for more details.";
    }
}

// Handle certificate generation request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
    isset($_POST['pod_id'], $_POST['competition_id'], $_POST['team_manager'], $_POST['certificate_type'])) {

    $podId = intval($_POST['pod_id']);
    $competitionId = intval($_POST['competition_id']);
    $teamManager = $_POST['team_manager'];
    $certificateType = $_POST['certificate_type'];

    generateCertificate($podId, $competitionId, $teamManager, $certificateType);
}
?>