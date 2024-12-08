<?php
session_start();
require_once "../../config/db.php";

// Check if the teacher is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];
$conn = (new Database())->getConnection();

// Fetch groups and sections associated with the teacher
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

// Fetch students (for dropdown)
$student_query = "SELECT student_id, student_surname, student_name, student_class FROM students";
$student_stmt = $conn->prepare($student_query);
$student_stmt->execute();
$students = $student_stmt->fetchAll(PDO::FETCH_ASSOC);

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'];
    $assignment_type = $_POST['assignment_type']; // 'group' or 'section'
    $selected_id = $_POST['selected_id']; // Group or Section ID

    if ($assignment_type === 'group') {
        // Update student's group in the students table
        $update_query = "UPDATE students SET group_id = :group_id WHERE student_id = :student_id";
        $stmt = $conn->prepare($update_query);
        $stmt->bindParam(':group_id', $selected_id);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();

        // Insert a log entry for the group assignment
        $log_query = "INSERT INTO logs (teacher_id, `group`, section, student_id, action_date, operation)
                      VALUES (:teacher_id, :group, NULL, :student_id, NOW(), 'add')";
        $log_stmt = $conn->prepare($log_query);
        $log_stmt->bindParam(':teacher_id', $teacher_id);
        $log_stmt->bindParam(':group', $selected_id);
        $log_stmt->bindParam(':student_id', $student_id);
        $log_stmt->execute();

        $success_message = "Group successfully assigned!";
    } else {
        // Fetch the current section_ids JSON array for the student
        $fetch_section_query = "SELECT section_ids FROM students WHERE student_id = :student_id";
        $stmt = $conn->prepare($fetch_section_query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        $current_section_ids = $stmt->fetchColumn();

        if ($current_section_ids) {
            $section_ids_array = json_decode($current_section_ids, true);

            // Find the first NULL slot
            $updated = false;
            foreach ($section_ids_array as $index => $value) {
                if (is_null($value)) {
                    $section_ids_array[$index] = $selected_id;
                    $updated = true;
                    break;
                }
            }

            if ($updated) {
                // Update the section_ids field with the modified array
                $update_section_query = "UPDATE students SET section_ids = :section_ids WHERE student_id = :student_id";
                $stmt = $conn->prepare($update_section_query);
                $stmt->bindParam(':section_ids', json_encode($section_ids_array));
                $stmt->bindParam(':student_id', $student_id);
                $stmt->execute();

                // Insert a log entry for the section assignment
                $log_query = "INSERT INTO logs (teacher_id, `group`, section, student_id, action_date, operation)
                              VALUES (:teacher_id, NULL, :section, :student_id, NOW(), 'add')";
                $log_stmt = $conn->prepare($log_query);
                $log_stmt->bindParam(':teacher_id', $teacher_id);
                $log_stmt->bindParam(':section', $selected_id);
                $log_stmt->bindParam(':student_id', $student_id);
                $log_stmt->execute();

                $success_message = "Student successfully assigned to the section!";
            } else {
                // All slots are filled; show error message
                $error_message = "The student has no free section slots available.";
            }
        }
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
                    <li class="nav-item"><a class="nav-link" href="manage_sections.php"><i class="fas fa-layer-group"></i><span>Добавить Секцию</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="log.php"><i class="fas fa-book"></i><span>Журналы Активностей</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_payments.php"><i class="fas fa-file-invoice-dollar"></i><span>Добавить Оплату</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="track_payments.php"><i class="fas fa-search-dollar"></i><span>Оплаты</span></a></li>
                    <li class="nav-item"><a class="nav-link" href="attendance.php"><i class="fas fa-user-check"></i><span>Посещяемость</span></a></li>
                    <li class="nav-item"><a class="nav-link active" href="add_student.php"><i class="fas fa-user-plus"></i><span>Добавить Ученика</span></a></li>
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

                <!-- Main Content -->

                <div class="container mt-5">
                    <h2>Назначить Ученика в Продленку/Секцию</h2>

                    <!-- Display success message if assignment was successful -->
                    <?php if ($success_message): ?>
                        <div class="alert alert-success" role="alert">
                            <?= $success_message ?>
                        </div>
                    <?php endif; ?>

                    <!-- Display error message if no free section slots -->
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger" role="alert">
                            <?= $error_message ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <!-- Step 1: Choose Assignment Type (Group or Section) -->
                        <div class="mb-3">
                            <label for="assignment_type" class="form-label">Продленка/Секция</label>
                            <select class="form-select" id="assignment_type" name="assignment_type" required>
                                <option value="">Выберите...</option>
                                <option value="group">Продленка</option>
                                <option value="section">Секция</option>
                            </select>
                        </div>

                        <!-- Step 2: Select Group or Section -->
                        <div class="mb-3">
                            <label for="selected_id" class="form-label">Название Продленки/Секций</label>
                            <select class="form-select" id="selected_id" name="selected_id" required>
                                <!-- Options populated dynamically with JavaScript -->
                            </select>
                        </div>

                        <!-- Step 3: Searchable Student Dropdown -->
                        <div class="mb-3">
                            <label for="student_id" class="form-label">Ученик</label>
                            <select class="form-select" id="student_id" name="student_id" required>
                                <option value="">Выберите Ученика...</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?= $student['student_id']; ?>">
                                        <?= $student['student_id'] . ' - ' . $student['student_surname'] . ' ' . $student['student_name'] . ' (' . $student['student_class'] . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-secondary">Добавить Ученика</button>
                    </form>
                </div>
                <!-- End of Main Content -->
            </div>
        </div>
    </div>

    <script>
        // Populate second dropdown based on assignment type selection
        document.getElementById('assignment_type').addEventListener('change', function() {
            let selectedType = this.value;
            let selectedIdDropdown = document.getElementById('selected_id');
            selectedIdDropdown.innerHTML = '';

            if (selectedType === 'group') {
                selectedIdDropdown.innerHTML = `<option value="<?= $teacher_group; ?>">Group - <?= $teacher_group; ?></option>`;
            } else if (selectedType === 'section') {
                <?php foreach ($teacher_sections as $section): ?>
                    selectedIdDropdown.innerHTML += `<option value="<?= $section['section_id']; ?>">Section - <?= $section['section_name']; ?></option>`;
                <?php endforeach; ?>
            }
        });

        // Make the student dropdown searchable using jQuery
        $(document).ready(function() {
            $('#student_id').select2({
                placeholder: 'Select a student',
                allowClear: true
            });
        });
    </script>

    <!-- Load Select2 JavaScript and CSS for searchable dropdowns -->
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