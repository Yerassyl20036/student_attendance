<?php
session_start();
require_once "../../config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'];
    $removal_type = $_POST['removal_type'];
    $selected_id = $_POST['selected_id'];

    if ($removal_type === 'group') {
        $update_query = "UPDATE students SET group_id = NULL WHERE student_id = :student_id";
        $stmt = $conn->prepare($update_query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();

        $log_query = "INSERT INTO logs (teacher_id, `group`, section, student_id, action_date, operation)
                      VALUES (:teacher_id, :group, NULL, :student_id, NOW(), 'remove')";
        $log_stmt = $conn->prepare($log_query);
        $log_stmt->bindParam(':teacher_id', $teacher_id);
        $log_stmt->bindParam(':group', $selected_id);
        $log_stmt->bindParam(':student_id', $student_id);
        $log_stmt->execute();

        $success_message = "Group assignment removed successfully!";
    } else {
        $fetch_section_query = "SELECT section_ids FROM students WHERE student_id = :student_id";
        $stmt = $conn->prepare($fetch_section_query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        $current_section_ids = $stmt->fetchColumn();

        if ($current_section_ids) {
            $section_ids_array = json_decode($current_section_ids, true);

            $updated = false;
            foreach ($section_ids_array as $index => $value) {
                if ($value == $selected_id) {
                    $section_ids_array[$index] = null;
                    $updated = true;
                    break;
                }
            }

            if ($updated) {
                $update_section_query = "UPDATE students SET section_ids = :section_ids WHERE student_id = :student_id";
                $stmt = $conn->prepare($update_section_query);
                $stmt->bindParam(':section_ids', json_encode($section_ids_array));
                $stmt->bindParam(':student_id', $student_id);
                $stmt->execute();

                $log_query = "INSERT INTO logs (teacher_id, `group`, section, student_id, action_date, operation)
                              VALUES (:teacher_id, NULL, :section, :student_id, NOW(), 'remove')";
                $log_stmt = $conn->prepare($log_query);
                $log_stmt->bindParam(':teacher_id', $teacher_id);
                $log_stmt->bindParam(':section', $selected_id);
                $log_stmt->bindParam(':student_id', $student_id);
                $log_stmt->execute();

                $success_message = "Section assignment removed successfully!";
            } else {
                $error_message = "Section not found in the student's current assignments.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Remove Student Assignment</title>
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
                    <li class="nav-item">
                        <a class="nav-link" href="attendance.php">
                            <i class="fas fa-user-check"></i><span>Посещяемость</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="add_student.php">
                            <i class="fas fa-user-plus"></i><span>Добавить Ученика</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="remove_student.php">
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
                <!-- End of Topbar -->

                <!-- Main Content -->
                <div class="container mt-5">
                    <h2>Убрать Ученика</h2>

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

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="removal_type" class="form-label">Продленка\Секция</label>
                            <select class="form-select" id="removal_type" name="removal_type" required>
                                <option value="">Выберите...</option>
                                <option value="group">Продленка</option>
                                <option value="section">Секция</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="selected_id" class="form-label">Название Продленкий\Секций</label>
                            <select class="form-select" id="selected_id" name="selected_id" required>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="student_id" class="form-label">Ученик</label>
                            <select class="form-select" id="student_id" name="student_id" required>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-danger">Убрать Ученика</button>
                    </form>
                </div>
                <!-- End of Main Content -->
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function () {
        $('#removal_type').on('change', function () {
            const type = $(this).val();
            const selectedIdDropdown = $('#selected_id');
            selectedIdDropdown.empty();
            $('#student_id').empty();

            if (type === 'group') {
                selectedIdDropdown.append(`<option value="<?= $teacher_group; ?>">Group - <?= $teacher_group; ?></option>`);
                fetchStudents(type, '<?= $teacher_group; ?>');
            } else if (type === 'section') {
                <?php if (count($teacher_sections) === 1): ?>
                    // Automatically select the only available section
                    selectedIdDropdown.append(`<option value="<?= $teacher_sections[0]['section_id']; ?>">Section - <?= $teacher_sections[0]['section_name']; ?></option>`);
                    fetchStudents(type, '<?= $teacher_sections[0]['section_id']; ?>');
                <?php else: ?>
                    <?php foreach ($teacher_sections as $section): ?>
                        selectedIdDropdown.append(`<option value="<?= $section['section_id']; ?>">Section - <?= $section['section_name']; ?></option>`);
                    <?php endforeach; ?>
                <?php endif; ?>
            }
        });

        $('#selected_id').on('change', function () {
            const selectedType = $('#removal_type').val();
            const selectedId = $(this).val();
            if (selectedId) {
                fetchStudents(selectedType, selectedId);
            }
        });

        function fetchStudents(type, id) {
            const studentDropdown = $('#student_id');
            studentDropdown.empty();

            $.ajax({
                url: "fetch_students_by_section.php",
                method: "POST",
                data: {
                    section_id: id,
                    type: type
                },
                success: function (data) {
                    const students = JSON.parse(data);
                    students.forEach(function (student) {
                        studentDropdown.append(`<option value="${student.student_id}">${student.student_id} - ${student.student_surname} ${student.student_name} (${student.student_class})</option>`);
                    });
                }
            });
        }

        // Trigger the removal type dropdown's change event on page load to auto-select if only one option is available
        $('#removal_type').trigger('change');
    });
</script>


    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script src="../../../assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
</body>

</html>