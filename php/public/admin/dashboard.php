<?php
session_start();
require_once "../../config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$conn = (new Database())->getConnection();

// Define the current month period
$current_month = date('Y-m');

// Fetch total statistics
$total_students = $conn->query("SELECT COUNT(*) FROM students")->fetchColumn();
$total_sections = $conn->query("SELECT COUNT(*) FROM sections")->fetchColumn();
$total_teachers = $conn->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
$total_logs = $conn->query("SELECT COUNT(*) FROM logs")->fetchColumn();
$total_payments = $conn->query("SELECT COUNT(*) FROM payment")->fetchColumn();
$total_payment_amount = $conn->query("SELECT SUM(amount) FROM payment")->fetchColumn();

// Fetch statistics for the current month
$month_logs_query = "SELECT COUNT(*) FROM logs WHERE DATE_FORMAT(action_date, '%Y-%m') = :current_month";
$month_logs_stmt = $conn->prepare($month_logs_query);
$month_logs_stmt->bindParam(':current_month', $current_month);
$month_logs_stmt->execute();
$current_month_logs = $month_logs_stmt->fetchColumn();

$month_payments_query = "SELECT COUNT(*) FROM payment WHERE DATE_FORMAT(payment_date, '%Y-%m') = :current_month";
$month_payments_stmt = $conn->prepare($month_payments_query);
$month_payments_stmt->bindParam(':current_month', $current_month);
$month_payments_stmt->execute();
$current_month_payments = $month_payments_stmt->fetchColumn();

$month_payment_amount_query = "SELECT SUM(amount) FROM payment WHERE DATE_FORMAT(payment_date, '%Y-%m') = :current_month";
$month_payment_amount_stmt = $conn->prepare($month_payment_amount_query);
$month_payment_amount_stmt->bindParam(':current_month', $current_month);
$month_payment_amount_stmt->execute();
$current_month_payment_amount = $month_payment_amount_stmt->fetchColumn();
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
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Статистика</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_teachers.php"><i class="fas fa-chalkboard-teacher"></i><span>Добавить Учителя</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_students.php"><i class="fas fa-user-graduate"></i><span>Добавить Ученика</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_sections.php"><i class="fas fa-layer-group"></i><span>Добавить Секцию</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="log.php"><i class="fas fa-book"></i><span>Журналы Активностей</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_payments.php"><i class="fas fa-file-invoice-dollar"></i><span>Добавить Оплату</span></a></li>
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
                    <h2>Статистика</h2>
                    <div class="row">

                        <!-- Display Total Counts -->
                        <div class="col-md-4">
                            <div class="card text-dark bg-secondary mb-3">
                                <div class="card-header">Всего Учеников</div>
                                <div class="card-body">
                                    <h5 class="card-title"><?= $total_students ?></h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-dark bg-secondary mb-3">
                                <div class="card-header">Всего Секций</div>
                                <div class="card-body">
                                    <h5 class="card-title"><?= $total_sections ?></h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-dark bg-secondary mb-3">
                                <div class="card-header">Всего Учителей</div>
                                <div class="card-body">
                                    <h5 class="card-title"><?= $total_teachers ?></h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-dark bg-secondary mb-3">
                                <div class="card-header">Всего Активностей</div>
                                <div class="card-body">
                                    <h5 class="card-title"><?= $total_logs ?></h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-dark bg-secondary mb-3">
                                <div class="card-header">Всего Оплат</div>
                                <div class="card-body">
                                    <h5 class="card-title"><?= $total_payments ?></h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-dark bg-secondary mb-3">
                                <div class="card-header">Сумма Всех Оплат</div>
                                <div class="card-body">
                                    <h5 class="card-title"><?= $total_payment_amount ?: 0 ?> KZT</h5>
                                </div>
                            </div>
                        </div>

                        <!-- Display Monthly Counts -->
                        <div class="col-md-4">
                            <div class="card text-dark bg-secondary mb-3">
                                <div class="card-header">Активности Месяца</div>
                                <div class="card-body">
                                    <h5 class="card-title"><?= $current_month_logs ?></h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-dark bg-secondary mb-3">
                                <div class="card-header">Количество Оплат Месяца</div>
                                <div class="card-body">
                                    <h5 class="card-title"><?= $current_month_payments ?></h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-dark bg-secondary mb-3">
                                <div class="card-header">Сумма Оплат Месяца</div>
                                <div class="card-body">
                                    <h5 class="card-title"><?= $current_month_payment_amount ?: 0 ?> KZT</h5>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../../../assets/bootstrap/js/bootstrap.min.js"></script>
</body>

</html>