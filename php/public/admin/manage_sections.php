<?php
session_start();
require_once "../../config/db.php";

// Ensure the user is logged in and has the admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$conn = (new Database())->getConnection();

// Fetch teachers for dropdown
$teacher_query = "SELECT teacher_id, teacher_surname, teacher_name FROM teachers";
$teacher_stmt = $conn->prepare($teacher_query);
$teacher_stmt->execute();
$teachers = $teacher_stmt->fetchAll(PDO::FETCH_ASSOC);

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize input values
    $teacher_id = $_POST['teacher_id'];
    $section_name = $_POST['section_name'];
    $cost = $_POST['cost'];

    // Get the teacher's name based on selected teacher_id
    $teacher_name_query = "SELECT teacher_name FROM teachers WHERE teacher_id = :teacher_id";
    $teacher_name_stmt = $conn->prepare($teacher_name_query);
    $teacher_name_stmt->bindParam(':teacher_id', $teacher_id);
    $teacher_name_stmt->execute();
    $teacher_name = $teacher_name_stmt->fetchColumn();

    try {
        // Insert new section into the database
        $insert_query = "
            INSERT INTO sections (teacher_id, teacher_name, section_name, cost)
            VALUES (:teacher_id, :teacher_name, :section_name, :cost)
        ";

        $stmt = $conn->prepare($insert_query);
        $stmt->bindParam(':teacher_id', $teacher_id);
        $stmt->bindParam(':teacher_name', $teacher_name);
        $stmt->bindParam(':section_name', $section_name);
        $stmt->bindParam(':cost', $cost);

        $stmt->execute();
        $success_message = "New section added successfully!";
    } catch (Exception $e) {
        $error_message = "Error adding section: " . $e->getMessage();
    }
}
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
                    <li class="nav-item"><a class="nav-link active" href="manage_sections.php"><i class="fas fa-layer-group"></i><span>Добавить Секцию</span></a></li>
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
                    <h2>Добавить Новую Секцию</h2>

                    <!-- Display Success or Error Message -->
                    <?php if ($success_message): ?>
                        <div class="alert alert-success" role="alert"><?= $success_message ?></div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger" role="alert"><?= $error_message ?></div>
                    <?php endif; ?>

                    <!-- Section Form -->
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="teacher_id" class="form-label">Учитель</label>
                            <select class="form-select" id="teacher_id" name="teacher_id" required>
                                <option value="">Выберите Учителя</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?= $teacher['teacher_id'] ?>">
                                        <?= $teacher['teacher_surname'] . ' ' . $teacher['teacher_name'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="section_name" class="form-label">Название Секций</label>
                            <input type="text" class="form-control" id="section_name" name="section_name" required>
                        </div>

                        <div class="mb-3">
                            <label for="cost" class="form-label">Цена</label>
                            <input type="number" class="form-control" id="cost" name="cost" required>
                        </div>

                        <button type="submit" class="btn btn-secondary">Добавить</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="../../../assets/bootstrap/js/bootstrap.min.js"></script>
</body>

</html>