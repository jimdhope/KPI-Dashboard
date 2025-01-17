<?php
// public/admin/pages/daily_scores/results.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/bootstrap.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/public/admin/includes/header.php';
require_once 'functions.php';

$db = Database::getInstance();
$pdo = $db->getConnection();
$pageTitle = 'Daily Results'; // Set the page title here

$selectedDate = $_GET['date'] ?? date('Y-m-d');
$selectedPod = $_GET['pod'] ?? '';

$pods = $pdo->query("SELECT * FROM pods ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$dailyResultsData = getDailyResultsData($pdo, $selectedDate, $selectedPod);
$rules = $dailyResultsData['rules'];
$displayResults = $dailyResultsData['displayResults'];

?>
<title>Daily Results</title> 
<link rel="icon" href="/favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="style.css">
<script src="scripts.js"></script>
<div class="container">
    <h1 class="mb-4"><?php echo $pageTitle; ?></h1>
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-3">
                <div class="card-body">
                    <form id="selectionForm" method="get">
                        <div class="row">
                            <div class="mb-3 col-md-6">
                                <label for="date" class="form-label">Date:</label>
                                <input type="date" class="form-control" id="date" name="date" value="<?php echo $selectedDate; ?>">
                            </div>
                            <div class="mb-3 col-md-6">
                                <label for="pod" class="form-label">Pod:</label>
                                <select class="form-select" id="pod" name="pod">
                                    <option value="">Select Pod</option>
                                    <?php foreach ($pods as $pod) : ?>
                                        <option value="<?php echo $pod['id']; ?>" <?php if ($selectedPod == $pod['id']) echo 'selected'; ?>><?php echo $pod['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php if ($selectedPod) : ?>
        <div class="card">
            <div class="card-body">
                <?php if (!empty($rules)): ?>
                    <div class="rules-key mb-3">
                        <?php foreach ($rules as $rule): ?>
                            <span class="me-3">
                                <?php echo htmlspecialchars($rule['name']); ?>
                                <?php if (!empty($rule['emoji'])): ?>
                                    <span><?php echo htmlspecialchars($rule['emoji']); ?></span>
                                <?php endif; ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <table class="table table-sm mb-0">
                    <tbody>
                        <?php if(isset($displayResults) && is_array($displayResults)): ?>
                            <?php foreach ($displayResults as $name => $data): ?>
                                <tr>
                                    <td class="text-nowrap"><?php echo htmlspecialchars($name); ?></td>
                                    <td class="text-nowrap"><?php echo $data['emojis']; ?></td>
                                    <td class="text-nowrap text-end"><?php echo $data['total']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/public/admin/includes/footer.php'; ?>