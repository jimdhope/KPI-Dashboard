<?php
require_once __DIR__ . '/../../../../includes/bootstrap.php';

function generateCertificates($formData) {
    $tempDir = sys_get_temp_dir() . '/certificates_' . uniqid();
    mkdir($tempDir);
    $files = [];
    
    try {
        $db = Database::getInstance();
        
        // Get pod name
        $stmt = $db->getConnection()->prepare("SELECT name FROM pods WHERE id = ?");
        $stmt->execute([$formData['pod']]);
        $podName = $stmt->fetchColumn();

        // Get team name and members
        $stmt = $db->getConnection()->prepare("
            SELECT t.name as team_name, GROUP_CONCAT(u.first_name ORDER BY u.first_name SEPARATOR ', ') as members
            FROM teams t
            JOIN user_team ut ON t.id = ut.team_id
            JOIN users u ON ut.user_id = u.id
            WHERE t.id = ?
            GROUP BY t.id
        ");
        $stmt->execute([$formData['winning_team']]);
        $teamData = $stmt->fetch(PDO::FETCH_ASSOC);

        $baseData = [
            'teamManager' => $formData['team_manager'],
            'date' => $formData['competition_date'],
            'podName' => $podName
        ];

        // Generate Team Certificate
        if (!empty($formData['winning_team'])) {
            $teamFile = $tempDir . '/team_certificate.png';
            $certificateData = array_merge($teamData, ['pod_name' => $podName]);
            generateSingleCertificate('Team', $baseData, $certificateData, $teamFile);
            $files[] = $teamFile;
        }

        // Get member names for place winners
        $stmt = $db->getConnection()->prepare("SELECT first_name FROM users WHERE id = ?");
        
        // Generate Individual Certificates
        $places = [
            'First Place' => $formData['first_place'],
            'Second Place' => $formData['second_place'],
            'Third Place' => $formData['third_place']
        ];

        foreach ($places as $type => $winnerId) {
            if (!empty($winnerId)) {
                $stmt->execute([$winnerId]);
                $winnerName = $stmt->fetchColumn();
                
                $fileName = $tempDir . '/' . strtolower(str_replace(' ', '_', $type)) . '.png';
                $data = array_merge($baseData, ['name' => $winnerName]);
                generateSingleCertificate($type, $data, ['pod_name' => $podName], $fileName);
                $files[] = $fileName;
            }
        }

        // Create ZIP archive
        $zipFile = $tempDir . '/certificates.zip';
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
            foreach ($files as $file) {
                $zip->addFile($file, basename($file));
            }
            $zip->close();

            // Clear output buffer and send headers
            while (ob_get_level()) ob_end_clean();
            
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="certificates.zip"');
            header('Content-Length: ' . filesize($zipFile));
            header('Cache-Control: no-cache, must-revalidate');
            
            readfile($zipFile);
            exit;
        }
    } catch (Exception $e) {
        error_log("Certificate generation error: " . $e->getMessage());
        throw new Exception("Failed to generate certificates: " . $e->getMessage());
    } finally {
        // Clean up all temporary files
        foreach ($files as $file) {
            if (file_exists($file)) unlink($file);
        }
        if (isset($zipFile) && file_exists($zipFile)) unlink($zipFile);
        if (file_exists($tempDir)) rmdir($tempDir);
    }
}

function generateSingleCertificate($type, $data, $extraData, $outputFile) {
    if (!extension_loaded('imagick')) {
        throw new Exception('Imagick extension is not loaded');
    }

    $templateFile = getCertificateTemplate($type);
    $templatePath = __DIR__ . "/templates/{$templateFile}";
    
    if (!file_exists($templatePath)) {
        throw new Exception("Template file not found: {$templateFile}");
    }

    $svgContent = file_get_contents($templatePath);
    if ($svgContent === false) {
        throw new Exception("Failed to read template file");
    }

    $svgContent = prepareSvgTemplate($svgContent);
    $svgContent = replacePlaceholders($svgContent, $data, $extraData);

    $imagick = new Imagick();
    $imagick->setResolution(300, 300);
    $imagick->setBackgroundColor(new ImagickPixel('transparent'));
    
    try {
        $imagick->readImageBlob($svgContent);
        $imagick->setImageFormat('png32');
        $imagick->setOption('png:compression-level', '9');
        $imagick->setCompressionQuality(100);
        $imagick->writeImage($outputFile);
    } finally {
        $imagick->clear();
        $imagick->destroy();
    }
}

function getCertificateTemplate($type) {
    switch ($type) {
        case 'Team':
            return 'team.svg';
        case 'First Place':
            return '1st.svg';
        case 'Second Place':
            return '2nd.svg';
        case 'Third Place':
            return '3rd.svg';
        default:
            throw new Exception('Invalid certificate type');
    }
}

function prepareSvgTemplate($svgContent) {
    if (!strpos($svgContent, 'width=')) {
        $svgContent = str_replace('<svg', '<svg width="2480" height="3508"', $svgContent);
    }
    return $svgContent;
}

function replacePlaceholders($svgContent, $data, $extraData) {
    $replacements = [
        '{{Name}}' => $data['name'] ?? '',
        '{{Pod Name}}' => $data['podName'] ?? '',
        '{{Team Manager}}' => $data['teamManager'] ?? '',
        '{{Date}}' => $data['date'] ?? '',
        '{{Members}}' => $extraData['members'] ?? '',
        '{{Total Points}}' => $extraData['total_points'] ?? '',
        '{{Team Name}}' => $extraData['team_name'] ?? ''
    ];
    
    return str_replace(array_keys($replacements), array_values($replacements), $svgContent);
}
?>