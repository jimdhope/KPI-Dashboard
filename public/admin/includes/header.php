<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/custom.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-dark text-white">
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/public/index.php"><?php echo htmlspecialchars(APP_NAME); ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#sideMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </nav>

    <!-- Offcanvas Sidebar for Mobile and Desktop -->
    <div class="offcanvas offcanvas-end bg-dark text-white" tabindex="-1" id="sideMenu">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title"><?php echo htmlspecialchars(APP_NAME); ?></h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a href="/public/admin/pages/dashboard.php" class="nav-link">Dashboard</a>
                </li>
                <li class="nav-item">
                    <div class="nav-link fw-bold mb-2">Management</div>
                    <ul class="list-unstyled ms-3">
                        <li><a class="nav-link" href="/public/admin/pages/people.php">People</a></li>
                        <li><a class="nav-link" href="/public/admin/pages/pods.php">Pods</a></li>
                        <li><a class="nav-link" href="/public/admin/pages/teams.php">Teams</a></li>
                        <li><a class="nav-link" href="/public/admin/pages/rules.php">Rules</a></li>
                        <li><a class="nav-link" href="/public/admin/pages/competitions.php">Competitions</a></li>
                        <li><a class="nav-link" href="/public/admin/pages/scores.php">Scores</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <div class="nav-link fw-bold mb-2">Scorecards</div>
                    <ul class="list-unstyled ms-3">
                        <li><a class="nav-link" href="/public/admin/pages/results.php">Daily Scorecard</a></li>
                        <li><a class="nav-link" href="/public/admin/pages/weekly_scores/weekly_scores.php">Weekly Scorecard</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a href="/public/admin/pages/settings.php" class="nav-link">Settings</a>
                </li>
            </ul>
        </div>
    </div>
</body>
</html>