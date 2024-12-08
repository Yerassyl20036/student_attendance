<?php
session_start();
require_once "../../config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];
$conn = (new Database())->getConnection();

// Fetch group and sections associated with the teacher
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
    $current_date = date('Y-m-d');

    foreach ($attendance_data as $attendance) {
        $student_id = $attendance['student_id'];
        $is_present = isset($attendance['is_present']) ? 1 : 0;
        $add_info = $attendance['add_info'];

        $insert_query = "
            INSERT INTO attendance (teacher_id, `group`, section_id, student_id, date, is_present, add_info)
            VALUES (:teacher_id, :group, :section_id, :student_id, :current_date, :is_present, :add_info)
        ";
        $stmt = $conn->prepare($insert_query);
        $stmt->bindParam(':teacher_id', $teacher_id);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':is_present', $is_present);
        $stmt->bindParam(':add_info', $add_info);
        $stmt->bindParam(':current_date', $current_date);

        if ($selected_type === 'group') {
            $stmt->bindParam(':group', $selected_id);
            $stmt->bindValue(':section_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':section_id', $selected_id);
            $stmt->bindValue(':group', null, PDO::PARAM_NULL);
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
    <link rel="stylesheet" href="../../../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900&display=swap">
    <link rel="stylesheet" href="../../../assets/fonts/fontawesome-all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div id="wrapper">
        <!-- Sidebar -->
        <nav class="navbar align-items-start sidebar sidebar-dark accordion bg-gradient-primary p-0 navbar-dark">
            <div class="container-fluid d-flex flex-column p-0">
                <a class="navbar-brand d-flex justify-content-center align-items-center sidebar-brand m-0" href="#">
                    <div class="sidebar-brand-icon rotate-n-15"><i class="fas fa-laugh-wink"></i></div>
                    <div class="sidebar-brand-text mx-3"><span>Панель Учителя</span></div>
                </a>
                <hr class="sidebar-divider my-0">
                <ul class="navbar-nav text-light" id="accordionSidebar">
                    <li class="nav-item active">
                        <a class="nav-link active" href="attendance.php">
                            <i class="fas fa-user-check"></i><span>Посещяемость</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="add_student.php">
                            <i class="fas fa-user-plus"></i><span>Добавить Ученика</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="remove_student.php">
                            <i class="fas fa-user-minus"></i><span>Убрать Ученика</span>
                        </a>
                    </li>
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
                                    <span class="d-none d-lg-inline me-2 text-gray-600 small">Teacher</span>
                                    <img class="border rounded-circle img-profile" src="../../../assets/img/avatars/avatar1.jpeg">
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
                <div class="container mt-5">
                    <h2>Record Attendance</h2>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success" role="alert">
                            <?= $success_message ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger" role="alert">
                            <?= $error_message ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Attendance Form -->
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="selected_type" class="form-label">Продленка\Секция</label>
                            <select class="form-select" id="selected_type" name="selected_type" required>
                                <option value="">Выберите...</option>
                                <option value="group">Продленка</option>
                                <option value="section">Секция</option>
                            </select>
                        </div>
                    
                        <div class="mb-3">
                            <label for="selected_id" class="form-label">Название Продленкий\Секций</label>
                            <select class="form-select" id="selected_id" name="selected_id" required></select>
                        </div>
                    
                        <!-- Student Attendance Table -->
                        <div id="student_table" class="mt-4"></div>
                    
                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </form>
                </div>
                <!-- End of Main Content -->
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function () {
        $('#selected_type').on('change', function () {
            const selectedType = $(this).val();
            const selectedIdDropdown = $('#selected_id');
            selectedIdDropdown.empty();
            $('#student_table').empty();

            if (selectedType === 'group') {
                selectedIdDropdown.append(`<option value="<?= $teacher_group; ?>">Group - <?= $teacher_group; ?></option>`);
                fetchStudentsByTypeAndId('group', '<?= $teacher_group; ?>');
            } else if (selectedType === 'section') {
                <?php if (count($teacher_sections) === 1): ?>
                    selectedIdDropdown.append(`<option value="<?= $teacher_sections[0]['section_id']; ?>">Section - <?= $teacher_sections[0]['section_name']; ?></option>`);
                    fetchStudentsByTypeAndId('section', '<?= $teacher_sections[0]['section_id']; ?>');
                <?php else: ?>
                    <?php foreach ($teacher_sections as $section): ?>
                        selectedIdDropdown.append(`<option value="<?= $section['section_id']; ?>">Section - <?= $section['section_name']; ?></option>`);
                    <?php endforeach; ?>
                <?php endif; ?>
            }
        });

        $('#selected_id').on('change', function () {
            const selectedType = $('#selected_type').val();
            const selectedId = $(this).val();
            if (selectedId) {
                fetchStudentsByTypeAndId(selectedType, selectedId);
            }
        });

        function fetchStudentsByTypeAndId(type, id) {
            const studentTable = $('#student_table');
            studentTable.empty();

            $.ajax({
                url: "fetch2.php",
                method: "POST",
                data: {
                    section_id: id,
                    type: type
                },
                success: function(data) {
                    const students = JSON.parse(data);
                    let tableHTML = `<table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Class</th>
                                        <th>Present</th>
                                        <th>Additional Comments</th>
                                    </tr>
                                </thead>
                                <tbody>`;
                    students.forEach(function(student) {
                        tableHTML += `<tr>
                                <td>${student.student_id}</td>
                                <td>${student.student_surname} ${student.student_name}</td>
                                <td>${student.student_class}</td>
                                <td><input type="checkbox" name="attendance[${student.student_id}][is_present]"></td>
                                <td><input type="text" name="attendance[${student.student_id}][add_info]" class="form-control"></td>
                                <input type="hidden" name="attendance[${student.student_id}][student_id]" value="${student.student_id}">
                              </tr>`;
                    });
                    tableHTML += `</tbody></table>`;
                    studentTable.html(tableHTML);
                }
            });
        }

        $('#selected_type').trigger('change');
    });
    </script>
    <!-- Load Select2 JavaScript and CSS for searchable dropdowns -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script src="../../../assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
</body>

</html>