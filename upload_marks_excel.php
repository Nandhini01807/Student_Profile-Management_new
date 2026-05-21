<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'staff'){
    header("Location: login.php");
    exit();
}

include('db_connect.php');
require 'vendor/autoload.php'; // Ensure PhpSpreadsheet is installed via Composer

use PhpOffice\PhpSpreadsheet\IOFactory;

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])){
    $fileName = $_FILES['file']['name'];
    $fileTmp  = $_FILES['file']['tmp_name'];
    $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if(!in_array($fileExt, ['xls', 'xlsx', 'csv'])){
        die("<h3>❌ Invalid file format. Please upload .xls, .xlsx, or .csv only.</h3>");
    }

    // Load spreadsheet
    $spreadsheet = IOFactory::load($fileTmp);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    $successCount = 0;
    $errorCount = 0;
    $errors = [];

    // Expected columns: student_id | semester | subject_name | iat1 | iat2 | semester_grade
    foreach($rows as $index => $row){
        if($index == 0) continue; // skip header row

        $student_id = trim($row[0]);
        $semester = trim($row[1]);
        $subject_name = trim($row[2]);
        $iat1 = trim($row[3]);
        $iat2 = trim($row[4]);
        $grade = trim($row[5]);

        // Validate data
        if(!is_numeric($iat1) || !is_numeric($iat2) || $iat1 < 0 || $iat1 > 100 || $iat2 < 0 || $iat2 > 100){
            $errors[] = "Row ".($index+1).": Invalid marks entered (must be between 0–100).";
            $errorCount++;
            continue;
        }

        // Check if record already exists
        $check = $conn->query("SELECT * FROM marks WHERE student_id=$student_id AND semester=$semester AND subject_name='$subject_name'");
        if($check->num_rows > 0){
            $conn->query("UPDATE marks 
                          SET iat1=$iat1, iat2=$iat2, semester_grade='$grade' 
                          WHERE student_id=$student_id AND semester=$semester AND subject_name='$subject_name'");
        } else {
            $conn->query("INSERT INTO marks (student_id, semester, subject_name, iat1, iat2, semester_grade) 
                          VALUES ($student_id, $semester, '$subject_name', $iat1, $iat2, '$grade')");
        }

        // Update student's CGPA dynamically
        $cgpaResult = $conn->query("SELECT AVG(semester_grade) AS cgpa FROM marks WHERE student_id=$student_id");
        $cgpa = $cgpaResult->fetch_assoc()['cgpa'];
        $conn->query("UPDATE students SET cgpa = ".round($cgpa,2)." WHERE id=$student_id");

        $successCount++;
    }

    echo "<div style='font-family:Arial;margin:50px auto;max-width:800px;background:#f8f9fa;padding:20px;border-radius:8px;box-shadow:0 0 10px rgba(0,0,0,0.1);'>";
    echo "<h2>✅ Upload Summary</h2>";
    echo "<p><b>Successfully updated:</b> $successCount records</p>";
    echo "<p><b>Errors:</b> $errorCount</p>";

    if($errorCount > 0){
        echo "<div style='background:#ffe6e6;padding:10px;border-radius:5px;'><b>Details:</b><ul>";
        foreach($errors as $err){
            echo "<li>$err</li>";
        }
        echo "</ul></div>";
    }

    echo "<br><a href='staff_dashboard.php' style='display:inline-block;padding:10px 20px;background:#3498db;color:white;text-decoration:none;border-radius:5px;'>← Back to Dashboard</a>";
    echo "</div>";
}
?>
