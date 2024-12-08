    <?php
    session_start();
    require_once "../../config/db.php";

    // Ensure the user is logged in and has the appropriate role (admin)
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: login.php");
        exit;
    }

    $conn = (new Database())->getConnection();

    // Query to fetch the latest 15 entries from logs, with joins for detailed information
    $log_query = "
        SELECT logs.log_id, 
            logs.action_date, 
            logs.operation,
            students.student_surname AS student_surname, 
            students.student_name AS student_name, 
            students.student_class AS student_class,
            teachers.teacher_surname AS teacher_surname,
            teachers.teacher_name AS teacher_name,
            sections.section_name AS section_name,
            logs.group
        FROM logs
        LEFT JOIN students ON logs.student_id = students.student_id
        LEFT JOIN teachers ON logs.teacher_id = teachers.teacher_id
        LEFT JOIN sections ON logs.section = sections.section_id
        ORDER BY logs.action_date DESC
        LIMIT 15
    ";

    $log_stmt = $conn->prepare($log_query);
    $log_stmt->execute();
    $logs = $log_stmt->fetchAll(PDO::FETCH_ASSOC);
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
        <style>
            .table-responsive-wrapper {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                margin-bottom: 1rem;
            }
            .table-responsive-wrapper table {
                margin-bottom: 0;
                white-space: nowrap;
            }
            @media (max-width: 768px) {
                .table-responsive-wrapper {
                    margin: 0 -15px;
                    padding: 0 15px;
                }
            }
        </style>
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
                        <li class="nav-item"><a class="nav-link active" href="log.php"><i class="fas fa-book"></i><span>Журналы Активностей</span></a></li>
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
                        <h2>Журнал Активностей</h2>
                        <div class="table-responsive-wrapper">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Учитель</th>
                                        <th>Продленка</th>
                                        <th>Секция</th>
                                        <th>Ученик</th>
                                        <th>Класс</th>
                                        <th>Дата</th>
                                        <th>Тип Операций</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td><?= $log['log_id'] ?></td>
                                            <td><?= $log['teacher_surname'] . ' ' . $log['teacher_name'] ?></td>
                                            <td><?= $log['group'] ?: '-' ?></td>
                                            <td><?= $log['section_name'] ?: '-' ?></td>
                                            <td><?= $log['student_surname'] . ' ' . $log['student_name'] ?></td>
                                            <td><?= $log['student_class'] ?></td>
                                            <td><?= $log['action_date'] ?></td>
                                            <td><?= ucfirst($log['operation']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="../../../assets/bootstrap/js/bootstrap.min.js"></script>
    </body>

    </html>