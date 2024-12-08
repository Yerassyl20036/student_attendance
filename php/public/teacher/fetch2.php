<?php
session_start();
require_once "../../config/db.php";

$conn = (new Database())->getConnection();
$type = $_POST['type'];
$selected_id = $_POST['section_id'];

// Current date for filtering
$current_date = date('Y-m-d');

if ($type === 'section') {
    // Fetch students by section ID within section_ids JSON, excluding those marked present today
    $student_query = "
        SELECT student_id, student_surname, student_name, student_class
        FROM students
        WHERE JSON_CONTAINS(section_ids, JSON_QUOTE(CAST(:selected_id AS CHAR)), '$')
        AND student_id NOT IN (
            SELECT student_id FROM attendance 
            WHERE section_id = :selected_id 
            AND date = :current_date 
            AND is_present = 1
        )
    ";
} else {
    // Fetch students by group ID, excluding those marked present today
    $student_query = "
        SELECT student_id, student_surname, student_name, student_class
        FROM students
        WHERE group_id = :selected_id
        AND student_id NOT IN (
            SELECT student_id FROM attendance 
            WHERE `group` = :selected_id 
            AND date = :current_date 
            AND is_present = 1
        )
    ";
}

$student_stmt = $conn->prepare($student_query);
$student_stmt->bindParam(':selected_id', $selected_id);
$student_stmt->bindParam(':current_date', $current_date);
$student_stmt->execute();

$students = $student_stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($students);