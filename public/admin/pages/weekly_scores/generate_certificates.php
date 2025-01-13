<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/bootstrap.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/public/admin/pages/weekly_scores/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $podId = intval($_POST['pod_id']);
    $competitionId = intval($_POST['competition_id']);
    $teamManager = $_POST['team_manager'];
    $certificateType = $_POST['certificate_type'];

    try {
        $db = Database::getInstance();
        $dbConnection = $db->getConnection();

        // Fetch competition data
        $stmt = $dbConnection->prepare("SELECT * FROM competitions WHERE id = ?");
        $stmt->execute([$competitionId]);
        $competition = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$competition) {
            throw new Exception("Competition not found");
        }

        // Fetch pod data
        $stmt = $dbConnection->prepare("SELECT * FROM pods WHERE id = ?");
        $stmt->execute([$podId]);
        $pod = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pod) {
            throw new Exception("Pod not found");
        }

        // Fetch leaderboard data
        $leaderboardData = getLeaderboardData($podId, $competition['start_date'], $competition['end_date']);

        // Determine the certificate template and recipient
        $svgTemplatePath = '';
        $recipientName = '';

        switch ($certificateType) {
            case '1st':
                $svgTemplatePath = $_SERVER['DOCUMENT_ROOT'] . '/public/admin/pages/weekly_scores/templates/1st.svg';
                $recipientName = $leaderboardData[0]['first_name'];
                break;
            case '2nd':
                $svgTemplatePath = $_SERVER['DOCUMENT_ROOT'] . '/public/admin/pages/weekly_scores/templates/2nd.svg';
                $recipientName = $leaderboardData[1]['first_name'];
                break;
            case '3rd':
                $svgTemplatePath = $_SERVER['DOCUMENT_ROOT'] . '/public/admin/pages/weekly_scores/templates/3rd.svg';
                $recipientName = $leaderboardData[2]['first_name'];
                break;
            case 'team':
                $svgTemplatePath = $_SERVER['DOCUMENT_ROOT'] . '/public/admin/pages/weekly_scores/templates/team.svg';
                $recipientName = $pod['name'];
                break;
            default:
                throw new Exception('Invalid certificate type');
        }

        // Load SVG template
        if (!file_exists($svgTemplatePath)) {
            throw new Exception('SVG template not found: ' . $svgTemplatePath);
        }
        $svgTemplate = file_get_contents($svgTemplatePath);

        // Replace placeholders
        $replacements = [
            '{{Name}}' => htmlspecialchars($recipientName),
            '{{Pod Name}}' => htmlspecialchars($pod['name']),
            '{{Team Manager}}' => htmlspecialchars($teamManager),
            '{{Date}}' => htmlspecialchars($competition['end_date'])
        ];

        $certificateContent = str_replace(array_keys($replacements), array_values($replacements), $svgTemplate);

        // Convert SVG to PNG
        $imagick = new Imagick();
        $imagick->readImageBlob($certificateContent);
        $imagick->setImageFormat("png");

        // Output the certificate for download
        header('Content-Type: image/png');
        header('Content-Disposition: attachment; filename="certificate.png"');
        echo $imagick->getImageBlob();
        exit;

    } catch (Exception $e) {
        error_log("Certificate Generation Error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        $error = "An error occurred while generating the certificate: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Certificates</title>
    <link rel="stylesheet" href="/public/admin/css/styles.css">
</head>
<body>
    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="container py-5">
        <h1 class="mb-4">Generate Certificates</h1>
        <form method="POST" action="/public/admin/pages/weekly_scores/generate_certificates.php">
            <input type="hidden" name="pod_id" value="<?php echo htmlspecialchars($_POST['pod_id'] ?? ''); ?>">
            <input type="hidden" name="competition_id" value="<?php echo htmlspecialchars($_POST['competition_id'] ?? ''); ?>">
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
</body>
</html>