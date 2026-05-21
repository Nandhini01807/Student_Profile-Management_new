<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'staff'){
    header("Location: login.php");
    exit();
}

include('db_connect.php');

if(isset($_POST['student_id'])){
    $student_id = $_POST['student_id'];
    $semester = $_POST['semester'];
    $subject_name = $_POST['subject_name'];
    $iat1 = $_POST['iat1'];
    $iat2 = $_POST['iat2'];
    $semester_grade = $_POST['semester_grade'];

    // Validate marks
    if($iat1 < 0 || $iat1 > 100 || $iat2 < 0 || $iat2 > 100){
        die("Error: Marks must be between 0 and 100");
    }

    // Update marks
    $stmt = $conn->prepare("UPDATE marks SET iat1=?, iat2=?, semester_grade=? WHERE student_id=? AND semester=? AND subject_name=?");
    $stmt->bind_param("iisiis", $iat1, $iat2, $semester_grade, $student_id, $semester, $subject_name);
    $stmt->execute();
    $stmt->close();

    // Redirect back to view marks
    header("Location: view_marks_staff.php?student_id=$student_id");
    exit();
}
?>
