<?php
session_start();
require_once "../../config/db.php";

$conn = (new Database())->getConnection();
$type = $_POST['type'];
$selected_id = $_POST['section_id'];

if ($type === 'section') {
    // Fetch students by section ID within section_ids JSON
    $student_query = "
        SELECT student_id, student_surname, student_name, student_class
        FROM students
        WHERE JSON_CONTAINS(section_ids, JSON_QUOTE(CAST(:selected_id AS CHAR)), '$')
    ";
} else {
    // Fetch students by group ID
    $student_query = "
        SELECT student_id, student_surname, student_name, student_class
        FROM students
        WHERE group_id = :selected_id
    ";
}

$student_stmt = $conn->prepare($student_query);
$student_stmt->bindParam(':selected_id', $selected_id);
$student_stmt->execute();

$students = $student_stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($students);
?>
