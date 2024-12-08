<?php
session_start();
require_once "../../config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$conn = (new Database())->getConnection();

// Handle balance reset
if (isset($_POST['reset_balance'])) {
    try {
        $reset_query = "UPDATE students SET balance = 0";
        $stmt = $conn->prepare($reset_query);
        $stmt->execute();
        $success_message = "All student balances have been reset to 0 successfully!";
    } catch (PDOException $e) {
        $error_message = "Error resetting balances: " . $e->getMessage();
    }
}

// Handle discount calculation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recalculate'])) {
    try {
        $discountPercentage = floatval($_POST['discountPercentage']);
        if ($discountPercentage < 0 || $discountPercentage > 100) {
            $error_message = "Invalid discount percentage.";
        } else {
            // Fetch all students
            $students_query = "SELECT student_id, group_id, section_ids, balance FROM students";
            $students = $conn->query($students_query)->fetchAll(PDO::FETCH_ASSOC);

            foreach ($students as $student) {
                $totalCost = 0;

                // Calculate group cost if group_id is not null
                if (!empty($student['group_id'])) {
                    $groupCostQuery = "SELECT cost FROM teachers WHERE `group` = :group_id";
                    $groupStmt = $conn->prepare($groupCostQuery);
                    $groupStmt->bindParam(':group_id', $student['group_id']);
                    $groupStmt->execute();
                    $groupCost = $groupStmt->fetchColumn();
                    $totalCost += floatval($groupCost);
                }

                // Calculate section costs if section_ids is not null
                if (!empty($student['section_ids'])) {
                    $sectionIds = json_decode($student['section_ids'], true);
                    if (is_array($sectionIds) && count($sectionIds) > 0) {
                        $placeholders = implode(',', array_fill(0, count($sectionIds), '?'));
                        $sectionCostQuery = "SELECT SUM(cost) AS total_section_cost FROM sections WHERE section_id IN ($placeholders)";
                        $sectionStmt = $conn->prepare($sectionCostQuery);
                        $sectionStmt->execute($sectionIds);
                        $sectionCost = $sectionStmt->fetchColumn();
                        $totalCost += floatval($sectionCost);
                    }
                }

                // Apply the discount
                $discountedAmount = $totalCost * ($discountPercentage / 100);
                $newBalance = $student['balance'] - $discountedAmount;

                // Update the student's balance
                $updateBalanceQuery = "UPDATE students SET balance = :new_balance WHERE student_id = :student_id";
                $updateStmt = $conn->prepare($updateBalanceQuery);
                $updateStmt->bindParam(':new_balance', $newBalance);
                $updateStmt->bindParam(':student_id', $student['student_id']);
                $updateStmt->execute();
            }

            $success_message = "Discount applied and balances updated successfully.";
        }
    } catch (PDOException $e) {
        $error_message = "Error applying discount: " . $e->getMessage();
    }
    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch distinct classes, groups, and sections
$class_query = "SELECT DISTINCT student_class FROM students ORDER BY student_class";
$classes = $conn->query($class_query)->fetchAll(PDO::FETCH_COLUMN);

$section_query = "SELECT section_id, section_name FROM sections ORDER BY section_name";
$sections = $conn->query($section_query)->fetchAll(PDO::FETCH_ASSOC);

$group_query = "SELECT DISTINCT group_id FROM students WHERE group_id IS NOT NULL ORDER BY group_id";
$groups = $conn->query($group_query)->fetchAll(PDO::FETCH_COLUMN);

// Filter values from GET parameters
$filter_status = $_GET['status'] ?? 'all';
$filter_date = $_GET['month'] ?? date('Y-m');
$filter_class = $_GET['class'] ?? '';
$filter_section = $_GET['section'] ?? '';
$filter_group = $_GET['group'] ?? '';

// Base query with LEFT JOIN to include students without payments
$payment_query = "
    SELECT students.student_id, students.student_surname, students.student_name, students.student_class,
           students.group_id, students.section_ids, students.balance, payment.payment_date, payment.amount, 
           payment.is_paid, payment.add_info
    FROM students
    LEFT JOIN payment ON students.student_id = payment.student_id 
    AND DATE_FORMAT(payment.payment_date, '%Y-%m') = :filter_date
    WHERE 1 = 1
";

// Add filters to the query
if ($filter_status === 'paid') {
    $payment_query .= " AND payment.is_paid = 1";
} elseif ($filter_status === 'unpaid') {
    $payment_query .= " AND (payment.is_paid = 0 OR payment.is_paid IS NULL)";
}
if (!empty($filter_class)) {
    $payment_query .= " AND students.student_class = :filter_class";
}
if (!empty($filter_section)) {
    $payment_query .= " AND JSON_CONTAINS(students.section_ids, JSON_QUOTE(CAST(:filter_section AS CHAR)), '$')";
}
if (!empty($filter_group)) {
    $payment_query .= " AND students.group_id = :filter_group";
}

$payment_stmt = $conn->prepare($payment_query);
$payment_stmt->bindParam(':filter_date', $filter_date);
if (!empty($filter_class)) $payment_stmt->bindParam(':filter_class', $filter_class);
if (!empty($filter_section)) $payment_stmt->bindParam(':filter_section', $filter_section);
if (!empty($filter_group)) $payment_stmt->bindParam(':filter_group', $filter_group);
$payment_stmt->execute();
$payments = $payment_stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper function to fetch section names based on section IDs
function getSectionNames($conn, $section_ids_json)
{
    if (empty($section_ids_json)) return '';
    $section_ids = json_decode($section_ids_json, true);
    if (!is_array($section_ids)) return '';
    $placeholders = implode(',', array_fill(0, count($section_ids), '?'));
    $stmt = $conn->prepare("SELECT section_name FROM sections WHERE section_id IN ($placeholders)");
    $stmt->execute($section_ids);
    $section_names = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return implode(', ', $section_names);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Payments</title>
    <link rel="stylesheet" href="../../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900&display=swap">
    <link rel="stylesheet" href="../../assets/fonts/fontawesome-all.min.css">
</head>

<body id="page-top">
    <div id="wrapper">
        <!-- Sidebar -->
        <nav class="navbar align-items-start sidebar sidebar-dark bg-dark p-0">
            <div class="container-fluid d-flex flex-column p-0">
                <a class="navbar-brand d-flex justify-content-center align-items-center sidebar-brand m-0" href="#">
                    <div class="sidebar-brand-icon rotate-n-15"><i class="fas fa-school"></i></div>
                    <div class="sidebar-brand-text mx-3"><span>Admin Panel</span></div>
                </a>
                <hr class="sidebar-divider my-0">
                <ul class="navbar-nav text-light" id="accordionSidebar">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Статистика</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_teachers.php"><i class="fas fa-chalkboard-teacher"></i><span>Добавить Учителя</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_students.php"><i class="fas fa-user-graduate"></i><span>Добавить Ученика</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_sections.php"><i class="fas fa-layer-group"></i><span>Добавить Секцию</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="log.php"><i class="fas fa-book"></i><span>Журналы Активностей</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_payments.php"><i class="fas fa-file-invoice-dollar"></i><span>Добавить Оплату</span></a></li>
                    <li class="nav-item"><a class="nav-link active" href="track_payments.php"><i class="fas fa-search-dollar"></i><span>Оплаты</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="attendance.php"><i class="fas fa-user-check"></i><span>Посещяемость</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="add_student.php"><i class="fas fa-user-plus"></i><span>Добавить Ученика</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="remove_student.php"><i class="fas fa-user-minus"></i><span>Убрать Ученика</span></a></li>
                </ul>
            </div>
        </nav>
        <!-- End of Sidebar -->

        <div class="d-flex flex-column" id="content-wrapper">
            <!-- Topbar -->
            <div id="content">
                <nav class="navbar navbar-expand bg-white shadow mb-4 topbar static-top navbar-light">
                    <div class="container-fluid">
                        <button class="btn btn-link d-md-none rounded-circle me-3" id="sidebarToggleTop" type="button">
                            <i class="fas fa-bars"></i>
                        </button>
                        <ul class="navbar-nav flex-nowrap ms-auto">
                            <li class="nav-item dropdown no-arrow">
                                <a class="dropdown-toggle nav-link" aria-expanded="false" data-bs-toggle="dropdown" href="#">
                                    <span class="d-none d-lg-inline me-2 text-gray-600 small">Admin</span>
                                    <img class="border rounded-circle img-profile" src="../../assets/img/avatars/avatar1.jpeg">
                                </a>
                                <div class="dropdown-menu dropdown-menu-end shadow animated--grow-in">
                                    <a class="dropdown-item" href="../logout.php">
                                        <i class="fas fa-sign-out-alt fa-sm fa-fw me-2 text-gray-400"></i>&nbsp;Выйти
                                    </a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </nav>
                <!-- End of Topbar -->
                <div class="container mt-5">
                    <h2>Track Payments</h2>

                    <!-- Action Buttons -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <!-- Balance Reset Form -->
                            <form method="POST" action="" id="resetForm" class="d-inline" onsubmit="return confirmReset();">
                                <button type="submit" name="reset_balance" class="btn btn-warning me-2">Balance Reset</button>
                            </form>
                            
                            <!-- Recalculate Button -->
                            <button type="button" class="btn btn-secondary" id="discountButton">Recalculate</button>
                            
                            <!-- Discount Form (separate form) -->
                            <form method="POST" action="" id="discountForm" style="display:none; margin-top:20px;">
                                <div class="form-group">
                                    <label for="discountPercentage">Discount Percentage (%):</label>
                                    <input type="number" name="discountPercentage" id="discountPercentage" 
                                           class="form-control" required min="0" max="100" step="0.01">
                                    <button type="submit" name="recalculate" class="btn btn-primary mt-2">Apply Discount</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= $success_message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= $error_message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Filter Form -->
                    <form method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>All</option>
                                    <option value="paid" <?= $filter_status === 'paid' ? 'selected' : '' ?>>Paid</option>
                                    <option value="unpaid" <?= $filter_status === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="month" class="form-label">Month</label>
                                <input type="month" class="form-control" id="month" name="month" value="<?= $filter_date ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="class" class="form-label">Class</label>
                                <select class="form-select" id="class" name="class">
                                    <option value="">All Classes</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?= $class ?>" <?= $filter_class === $class ? 'selected' : '' ?>><?= $class ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="section" class="form-label">Section</label>
                                <select class="form-select" id="section" name="section">
                                    <option value="">All Sections</option>
                                    <?php foreach ($sections as $section): ?>
                                        <option value="<?= $section['section_id'] ?>" <?= $filter_section === $section['section_id'] ? 'selected' : '' ?>>
                                            <?= $section['section_name'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="group" class="form-label">Group</label>
                                <select class="form-select" id="group" name="group">
                                    <option value="">All Groups</option>
                                    <?php foreach ($groups as $group): ?>
                                        <option value="<?= $group ?>" <?= $filter_group === $group ? 'selected' : '' ?>><?= $group ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 align-self-end">
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </div>
                        </div>
                    </form>

                    <!-- Payment Status Table -->
                    <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Class</th>
                            <th>Group</th>
                            <th>Section</th>
                            <th>Balance</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Comment</th>
                        </tr>
                    </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?= $payment['student_id'] ?></td>
                                    <td><?= $payment['student_surname'] . ' ' . $payment['student_name'] ?></td>
                                    <td><?= $payment['student_class'] ?></td>
                                    <td><?= $payment['group_id'] ?? 'N/A' ?></td>
                                    <td><?= getSectionNames($conn, $payment['section_ids']) ?></td>
                                    <td><?= $payment['balance'] ?></td>
                                    <td><?= $payment['payment_date'] ?></td>
                                    <td><?= $payment['amount'] ?></td>
                                    <td><?= $payment['is_paid'] ? 'Yes' : 'No' ?></td>
                                    <td><?= $payment['add_info'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="../../../assets/bootstrap/js/bootstrap.min.js"></script>
    <script>
        // Show/hide the discount form when the "Recalculate" button is clicked
        document.getElementById('discountButton').addEventListener('click', function() {
            const form = document.getElementById('discountForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        });

        function confirmReset() {
            return confirm('Are you sure you want to reset all student balances to 0? This action cannot be undone.');
        }
    </script>
</body>

</html>