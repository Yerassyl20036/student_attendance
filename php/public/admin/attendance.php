<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once "../../config/db.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin')) {
    header("Location: login.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];
$conn = (new Database())->getConnection();

$group_query = "SELECT `group` FROM teachers WHERE teacher_id = :teacher_id";
$group_stmt = $conn->prepare($group_query);
$group_stmt->bindParam(':teacher_id', $teacher_id);
$group_stmt->execute();
$teacher_group = $group_stmt->fetchColumn();

$section_query = "SELECT section_id, section_name FROM sections WHERE teacher_id = :teacher_id";
$section_stmt = $conn->prepare($section_query);
$section_stmt->bindParam(':teacher_id', $teacher_id);
$section_stmt->execute();
$teacher_sections = $section_stmt->fetchAll(PDO::FETCH_ASSOC);

$success_message = '';
$error_message = '';

// Handle attendance form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attendance'])) {
    $selected_type = $_POST['selected_type'];
    $selected_id = $_POST['selected_id'];
    $attendance_data = $_POST['attendance'];
    foreach ($attendance_data as $attendance) {
        $student_id = $attendance['student_id'];
        $is_present = isset($attendance['is_present']) ? 1 : 0;
        $add_info = $attendance['add_info'];
        $insert_query = "
            INSERT INTO attendance (teacher_id, `group`, section_id, student_id, date, is_present, add_info)
            VALUES (:teacher_id, :group, :section_id, :student_id, NOW(), :is_present, :add_info)
        ";
        $stmt = $conn->prepare($insert_query);
        $stmt->bindParam(':teacher_id', $teacher_id);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':is_present', $is_present);
        $stmt->bindParam(':add_info', $add_info);

        // Set `group` or `section_id` based on the selected type
        if ($selected_type === 'group') {
            $stmt->bindParam(':group', $selected_id);
            $stmt->bindValue(':section_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':group', null, PDO::PARAM_NULL);
            $stmt->bindParam(':section_id', $selected_id);
        }

        $stmt->execute();
    }

    $success_message = "Attendance recorded successfully!";
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Attendance</title>
    <link rel="stylesheet" href="../../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900&display=swap">
    <link rel="stylesheet" href="../../assets/fonts/fontawesome-all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
        .balance-positive {
            background-color: rgba(40, 167, 69, 0.1) !important;
        }
        .balance-negative {
            background-color: rgba(220, 53, 69, 0.1) !important;
        }
        .balance-zero {
            background-color: inherit;
        }
        tr.balance-positive:hover {
            background-color: rgba(40, 167, 69, 0.2) !important;
        }
        tr.balance-negative:hover {
            background-color: rgba(220, 53, 69, 0.2) !important;
        }
        .table-success {
            background-color: rgba(40, 167, 69, 0.15) !important;
        }
        .table-danger {
            background-color: rgba(220, 53, 69, 0.15) !important;
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
                    <li class="nav-item"><a class="nav-link" href="log.php"><i class="fas fa-book"></i><span>Журналы Активностей</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_payments.php"><i class="fas fa-file-invoice-dollar"></i><span>Добавить Оплату</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="track_payments.php"><i class="fas fa-search-dollar"></i><span>Оплаты</span></a></li>
                    <li class="nav-item"><a class="nav-link active" href="attendance.php"><i class="fas fa-user-check"></i><span>Посещяемость</span></a></li>
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
                    <h2>Record Attendance</h2>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success" role="alert">
                            <?= $success_message ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="" id="attendance_form">
                        <div class="mb-3">
                            <label for="selected_type" class="form-label">Select Group or Section</label>
                            <select class="form-select" id="selected_type" name="selected_type" required>
                                <option value="">Choose...</option>
                                <option value="group">Group</option>
                                <option value="section">Section</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="selected_id" class="form-label">Select Group/Section</label>
                            <select class="form-select" id="selected_id" name="selected_id" required></select>
                        </div>

                        <div id="student_table" class="mt-4"></div>

                        <button type="submit" class="btn btn-primary">Save Attendance</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        $('#selected_type').on('change', function() {
            const selectedType = $(this).val();
            const selectedIdDropdown = $('#selected_id');
            selectedIdDropdown.empty();
            $('#student_table').empty();

            if (selectedType === 'group') {
                selectedIdDropdown.append(`<option value="<?= $teacher_group; ?>">Group - <?= $teacher_group; ?></option>`);
                fetchStudents('group', '<?= $teacher_group; ?>');
            } else if (selectedType === 'section') {
                <?php foreach ($teacher_sections as $section): ?>
                    selectedIdDropdown.append(`<option value="<?= $section['section_id']; ?>">Section - <?= $section['section_name']; ?></option>`);
                <?php endforeach; ?>
                selectedIdDropdown.on('change', function() {
                    fetchStudents('section', $(this).val());
                });
            }
        });

        function getBalanceClass(balance) {
            if (balance === null || balance === undefined) return 'balance-zero';
            balance = parseFloat(balance);
            if (balance > 0) return 'balance-positive';
            if (balance < 0) return 'balance-negative';
            return 'balance-zero';
        }

        function fetchStudents(type, id) {
            const studentTable = $('#student_table');
            studentTable.empty();
            $.ajax({
                url: "fetch2.php",
                method: "POST",
                data: {
                    section_id: id,
                    type: type
                },
                success: function(response) {
                    const students = JSON.parse(response);
                    if (students.length > 0) {
                        let table = `
                            <div class="table-responsive-wrapper">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Class</th>
                                            <th>Group</th>
                                            <th>Section</th>
                                            <th>Balance</th>
                                            <th>Present</th>
                                            <th>Comment</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;

                        students.forEach(student => {
                            // Add balance-based row coloring
                            const balanceClass = parseFloat(student.balance) < 0 ? 'table-danger' : 
                                               parseFloat(student.balance) > 0 ? 'table-success' : '';
                            
                            table += `
                                <tr class="${balanceClass}">
                                    <td>${student.student_id}</td>
                                    <td>${student.student_surname} ${student.student_name}</td>
                                    <td>${student.student_class}</td>
                                    <td>${student.group_id || 'N/A'}</td>
                                    <td>${student.section_names || 'N/A'}</td>
                                    <td>${student.balance !== null ? student.balance : '0'}</td>
                                    <td>
                                        <input type="hidden" name="attendance[${student.student_id}][student_id]" value="${student.student_id}">
                                        <input type="checkbox" class="form-check-input" name="attendance[${student.student_id}][is_present]" value="1">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" name="attendance[${student.student_id}][add_info]" placeholder="Add comment">
                                    </td>
                                </tr>
                            `;
                        });

                        table += `
                                    </tbody>
                                </table>
                            </div>
                        `;
                        studentTable.html(table);
                    } else {
                        studentTable.html('<div class="alert alert-info">No students found or all students are marked present for today.</div>');
                    }
                },
                error: function() {
                    studentTable.html('<div class="alert alert-danger">Error fetching students.</div>');
                }
            });
        }    </script>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script src="../../assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

</body>

</html>