<?php
session_start();
require_once "config.php";

// Protect admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../../index.php");
    exit();
}

// Default filter = THIS YEAR
$filter = $_GET['filter'] ?? "year";

// Build SQL Condition
$where = "";

switch ($filter) {

    case "day":
        $where = "WHERE DATE(last_login) = CURDATE()";
        break;

    case "week":
        $where = "WHERE YEARWEEK(last_login, 1) = YEARWEEK(CURDATE(), 1)";
        break;

    case "month":
        $where = "WHERE YEAR(last_login) = YEAR(CURDATE()) AND MONTH(last_login) = MONTH(CURDATE())";
        break;

    case "year":
    default:
        $where = "WHERE YEAR(last_login) = YEAR(CURDATE())";
        break;
}

// Fetch filtered login history
$stmt = $pdo->prepare("SELECT user_id, login_count, last_login FROM login_counts $where ORDER BY last_login DESC");
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Login History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

<div class="container mt-4">

    <h3>Users Login History</h3>
      <!-- FILTER BOX -->
    <form method="GET" class="mt-3">

        <label class="form-label">Select Filter:</label>
        <select name="filter" class="form-select w-25 mb-3" onchange="this.form.submit()">
            <option value="day" <?= $filter == 'day' ? 'selected' : '' ?>>Today</option>
            <option value="week" <?= $filter == 'week' ? 'selected' : '' ?>>This Week</option>
            <option value="month" <?= $filter == 'month' ? 'selected' : '' ?>>This Month</option>
            <option value="year" <?= $filter == 'year' ? 'selected' : '' ?>>This Year</option>
        </select>

    </form>

    <!-- FILTER RESULT TEXT -->
    <div class="alert alert-info">
        Showing results for: <strong><?= strtoupper($filter) ?></strong>
    </div>

    <!-- RESULT TABLE -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            Filtered Login Records
        </div>

        <div class="card-body p-0">

            <div class="table-responsive">
                <table class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Login Count</th>
                            <th>Last Login</th>
                        </tr>
                    </thead>
                    <tbody>

                    <?php if (empty($results)): ?>
                        <tr>
                            <td colspan="3" class="text-center text-muted">No login record found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($results as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['user_id']) ?></td>
                                <td><?= htmlspecialchars($row['login_count']) ?></td>
                                <td><?= htmlspecialchars($row['last_login']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    </tbody>
                </table>
            </div>

        </div>
    </div>

</div>

</body>
</html>
