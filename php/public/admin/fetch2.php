<?php
session_start();
require_once "../../config/db.php";

$conn = (new Database())->getConnection();
$type = $_POST['type'];
$selected_id = $_POST['section_id'];

// Current date for filtering
$current_date = date('Y-m-d');

// Helper function to get section names
function getSectionNames($conn, $section_ids_json) {
    if (empty($section_ids_json)) return '';
    $section_ids = json_decode($section_ids_json, true);
    if (!is_array($section_ids)) return '';
    $placeholders = implode(',', array_fill(0, count($section_ids), '?'));
    $stmt = $conn->prepare("SELECT section_name FROM sections WHERE section_id IN ($placeholders)");
    $stmt->execute($section_ids);
    $section_names = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return implode(', ', $section_names);
}

if ($type === 'section') {
    $student_query = "
        SELECT s.student_id, s.student_surname, s.student_name, s.student_class,
               s.group_id, s.section_ids, s.balance
        FROM students s
        WHERE JSON_CONTAINS(s.section_ids, JSON_QUOTE(CAST(:selected_id AS CHAR)), '$')
        AND s.student_id NOT IN (
            SELECT student_id FROM attendance 
            WHERE section_id = :selected_id 
            AND date = :current_date 
            AND is_present = 1
        )
    ";
} else {
    $student_query = "
        SELECT s.student_id, s.student_surname, s.student_name, s.student_class,
               s.group_id, s.section_ids, s.balance
        FROM students s
        WHERE s.group_id = :selected_id
        AND s.student_id NOT IN (
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

// Add section names to each student
foreach ($students as &$student) {
    $student['section_names'] = getSectionNames($conn, $student['section_ids']);
}

echo json_encode($students);
