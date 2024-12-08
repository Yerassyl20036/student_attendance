<?php
session_start();
require_once "../../config/db.php";

// Ensure the user is logged in and has the admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$conn = (new Database())->getConnection();

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize input values
    $teacher_surname = $_POST['teacher_surname'];
    $teacher_name = $_POST['teacher_name'];
    $teacher_phone = $_POST['teacher_phone'];
    $password = $_POST['password']; // Remember to hash in a real app for security
    $role = $_POST['role'];
    $group = $_POST['group'];
    $cost = $_POST['cost'];
    $add_info = $_POST['add_info'];

    try {
        // Insert new teacher into the database
        $insert_query = "
            INSERT INTO teachers (teacher_surname, teacher_name, teacher_phone, password, role, `group`, cost, add_info)
            VALUES (:teacher_surname, :teacher_name, :teacher_phone, :password, :role, :group, :cost, :add_info)
        ";

        $stmt = $conn->prepare($insert_query);
        $stmt->bindParam(':teacher_surname', $teacher_surname);
        $stmt->bindParam(':teacher_name', $teacher_name);
        $stmt->bindParam(':teacher_phone', $teacher_phone);
        $stmt->bindParam(':password', $password);  // Use password_hash() for secure storage
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':group', $group);
        $stmt->bindParam(':cost', $cost);
        $stmt->bindParam(':add_info', $add_info);

        $stmt->execute();
        $success_message = "New teacher added successfully!";
    } catch (Exception $e) {
        $error_message = "Error adding teacher: " . $e->getMessage();
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
                    <li class="nav-item"><a class="nav-link active" href="manage_teachers.php"><i class="fas fa-chalkboard-teacher"></i><span>Добавить Учителя</span></a></li>
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
                    <h2>Добавить Нового Учителя</h2>

                    <!-- Display Success or Error Message -->
                    <?php if ($success_message): ?>
                        <div class="alert alert-success" role="alert"><?= $success_message ?></div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger" role="alert"><?= $error_message ?></div>
                    <?php endif; ?>

                    <!-- Teacher Form -->
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="teacher_surname" class="form-label">Фамилия</label>
                            <input type="text" class="form-control" id="teacher_surname" name="teacher_surname" required>
                        </div>

                        <div class="mb-3">
                            <label for="teacher_name" class="form-label">Имя</label>
                            <input type="text" class="form-control" id="teacher_name" name="teacher_name" required>
                        </div>

                        <div class="mb-3">
                            <label for="teacher_phone" class="form-label">Логин</label>
                            <input type="text" class="form-control" id="teacher_phone" name="teacher_phone" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Пароль</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <div class="mb-3">
                            <label for="role" class="form-label">Роль</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="teacher">Teacher</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="group" class="form-label">Название Продленки</label>
                            <input type="text" class="form-control" id="group" name="group" required>
                        </div>

                        <div class="mb-3">
                            <label for="cost" class="form-label">Цена</label>
                            <input type="number" class="form-control" id="cost" name="cost" required>
                        </div>

                        <div class="mb-3">
                            <label for="add_info" class="form-label">Дополнительная Информация</label>
                            <textarea class="form-control" id="add_info" name="add_info" rows="3"></textarea>
                        </div>

                        <button type="submit" class="btn btn-secondary">Добавить Учителя</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="../../../assets/bootstrap/js/bootstrap.min.js"></script>
</body>

</html>