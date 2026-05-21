<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student'){
    header("Location: login.php");
    exit();
}

include('db_connect.php');

$student_id = $_SESSION['user_id'];

// Fetch student details
$student_sql = "SELECT * FROM students WHERE id=$student_id";
$student_result = $conn->query($student_sql);
$student = $student_result->fetch_assoc();

// Fetch all marks for this student
$marks_sql = "SELECT * FROM marks WHERE student_id=$student_id ORDER BY semester";
$marks_result = $conn->query($marks_sql);

$sem_data = [];
$grade_points = ['O'=>10,'A+'=>9,'A'=>8,'B+'=>7,'B'=>6,'C'=>5,'F'=>0]; // Grade mapping

while($row = $marks_result->fetch_assoc()){
    $sem = $row['semester'];
    if(!isset($sem_data[$sem])) $sem_data[$sem] = ['subjects'=>[], 'total'=>0, 'gpa'=>0];
    $sem_data[$sem]['subjects'][] = $row;
    $total_marks = $row['iat1'] + $row['iat2'];
    $sem_data[$sem]['total'] += $total_marks;
    
    // Semester GPA calculation
    if(isset($grade_points[$row['semester_grade']])){
        $sem_data[$sem]['gpa'] += $grade_points[$row['semester_grade']];
    }
}

// Finalize semester GPA
foreach($sem_data as $sem => $data){
    $count = count($data['subjects']);
    $sem_data[$sem]['gpa'] = $count>0 ? round($data['gpa'] / $count, 2) : 0;
}

// Calculate overall CGPA
$total_points = 0;
$total_subjects = 0;
foreach($sem_data as $sem => $data){
    $total_points += $data['gpa'] * count($data['subjects']);
    $total_subjects += count($data['subjects']);
}
$cgpa = $total_subjects > 0 ? round($total_points / $total_subjects, 2) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Dashboard</title>
<style>
body{font-family:Arial,sans-serif;background:#f4f6f8;margin:0;padding:0;}
.container{max-width:900px;margin:50px auto;background:white;padding:20px;border-radius:10px;box-shadow:0 0 10px rgba(0,0,0,0.1);}
h2{text-align:center;color:#2c3e50;}
table{width:100%;border-collapse:collapse;margin-top:20px;}
table, th, td{border:1px solid #ccc;}
th, td{padding:10px;text-align:center;}
th{background:#2c3e50;color:white;}
.semester-title{margin-top:30px;color:#34495e;font-weight:bold;text-align:left;}
</style>
</head>
<body>
<div class="container">
<h2>Welcome, <?php echo $student['name']; ?>!</h2>
<p><strong>Department:</strong> <?php echo $student['department']; ?></p>
<p><strong>Register Number:</strong> <?php echo $student['register_no']; ?></p>
<p><strong>Overall CGPA:</strong> <?php echo $cgpa; ?> / 10</p>

<hr>
<h3>Semester-wise Marks & GPA</h3>

<?php
$max_per_subject = 200; // IAT1 + IAT2 max marks
if(!empty($sem_data)){
    foreach($sem_data as $sem => $data){
        echo "<h4 class='semester-title'>Semester $sem (GPA: {$data['gpa']}/10)</h4>";
        echo "<table>
                <tr>
                    <th>Subject</th>
                    <th>IAT1</th>
                    <th>IAT2</th>
                    <th>Total</th>
                    <th>Semester Grade</th>
                </tr>";
        foreach($data['subjects'] as $sub){
            $total = ($sub['iat1'] + $sub['iat2']) / 2;
            echo "<tr>
                    <td>{$sub['subject_name']}</td>
                    <td>{$sub['iat1']}</td>
                    <td>{$sub['iat2']}</td>
                    <td>{$total}</td>
                    <td>{$sub['semester_grade']}</td>
                  </tr>";
        }
        echo "</table>";
    }
} else {
    echo "<p>No marks available yet.</p>";
}
?>
</div>
</body>
</html>
