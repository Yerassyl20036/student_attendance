<?php
session_start();
require_once "../../config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$conn = (new Database())->getConnection();
$success_message = '';
$error_message = '';

// Check for messages in session
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $student_id = intval($_POST['student_id']);
    $payment_date = $_POST['payment_date'];
    $amount = floatval($_POST['amount']);
    $is_paid = isset($_POST['is_paid']) ? 1 : 0; // Checkbox for payment status
    $add_info = $_POST['add_info'] ?? '';

    try {
        // Begin transaction
        $conn->beginTransaction();

        // Insert the new payment into the payments table
        $insertPaymentQuery = "
            INSERT INTO payment (student_id, payment_date, amount, is_paid, add_info) 
            VALUES (:student_id, :payment_date, :amount, :is_paid, :add_info)";
        $insertPaymentStmt = $conn->prepare($insertPaymentQuery);
        $insertPaymentStmt->execute([
            ':student_id' => $student_id,
            ':payment_date' => $payment_date,
            ':amount' => $amount,
            ':is_paid' => $is_paid,
            ':add_info' => $add_info,
        ]);

        // Update the student's balance
        $updateBalanceQuery = "UPDATE students SET balance = balance + :amount WHERE student_id = :student_id";
        $updateBalanceStmt = $conn->prepare($updateBalanceQuery);
        $updateBalanceStmt->execute([
            ':amount' => $amount,
            ':student_id' => $student_id,
        ]);

        // Commit transaction
        $conn->commit();
        $_SESSION['success_message'] = "Payment added successfully, and student balance updated!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } catch (Exception $e) {
        // Rollback in case of error
        $conn->rollBack();
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Fetch students for dropdown
$student_query = "SELECT student_id, student_surname, student_name, student_class FROM students";
$student_stmt = $conn->prepare($student_query);
$student_stmt->execute();
$students = $student_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
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
                    <li class="nav-item"><a class="nav-link active" href="manage_payments.php"><i class="fas fa-file-invoice-dollar"></i><span>Добавить Оплату</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="track_payments.php"><i class="fas fa-search-dollar"></i><span>Оплаты</span></a></li>
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
                    <h2>Добавить Оплату</h2>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($success_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                                        
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($error_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Add/Edit Payment Form -->
                    <form method="POST">
                        <div class="mb-3">
                            <label for="student_id" class="form-label">Ученик</label>
                            <select class="form-select" id="student_id" name="student_id" required>
                                <option value="">Выбрать Ученика...</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?= $student['student_id']; ?>">
                                        <?= $student['student_id'] . ' - ' . $student['student_surname'] . ' ' . $student['student_name'] . ' (' . $student['student_class'] . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="payment_date" class="form-label">Дата</label>
                            <input type="date" class="form-control" id="payment_date" name="payment_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="amount" class="form-label">Сумма</label>
                            <input type="number" class="form-control" id="amount" name="amount" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_paid" name="is_paid">
                            <label class="form-check-label" for="is_paid">Оплачено на текущий месяц</label>
                        </div>
                        <div class="mb-3">
                            <label for="add_info" class="form-label">Комментарий</label>
                            <input type="text" class="form-control" id="add_info" name="add_info">
                        </div>
                        <button type="submit" class="btn btn-secondary">Сохранить</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Load Select2 for character matching functionality -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Apply Select2 to the student dropdown
            $('#student_id').select2({
                placeholder: 'Выбрать Ученика...',
                allowClear: true
            });
        });
    </script>

    <script src="../../../assets/bootstrap/js/bootstrap.min.js"></script>
</body>

</html>