<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'staff'){
    header("Location: login.php");
    exit();
}

include('db_connect.php');

if(!isset($_GET['student_id'])){
    header("Location: staff_dashboard.php");
    exit();
}

$student_id = $_GET['student_id'];

// Fetch student info
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

    // Semester GPA calculation based on grade points
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
<title>Marks for <?php echo $student['name']; ?></title>
<style>
body{font-family:Arial,sans-serif;background:#f4f6f8;margin:0;padding:0;}
.container{max-width:900px;margin:50px auto;background:white;padding:20px;border-radius:10px;box-shadow:0 0 10px rgba(0,0,0,0.1);}
h2{text-align:center;color:#2c3e50;}
table{width:100%;border-collapse:collapse;margin-top:20px;}
table, th, td{border:1px solid #ccc;}
th, td{padding:10px;text-align:center;}
th{background:#2c3e50;color:white;}
a.back{display:inline-block;margin-bottom:10px;padding:5px 10px;background:#34495e;color:white;text-decoration:none;border-radius:5px;}
button.edit-btn{background:none;border:none;color:#e67e22;cursor:pointer;font-size:16px;}
input.edit-field{width:60px;}
.semester-title{margin-top:30px;color:#34495e;font-weight:bold;text-align:left;}
</style>
<script>
function editRow(rowId){
    var row = document.getElementById('row_'+rowId);
    var iat1 = row.querySelector('.iat1');
    var iat2 = row.querySelector('.iat2');
    var grade = row.querySelector('.grade');

    iat1.innerHTML = "<input class='edit-field' type='number' value='"+iat1.innerText+"' min='0' max='100'>";
    iat2.innerHTML = "<input class='edit-field' type='number' value='"+iat2.innerText+"' min='0' max='100'>";
    grade.innerHTML = "<input class='edit-field' type='text' value='"+grade.innerText+"'>";

    var action = row.querySelector('.action');
    action.innerHTML = "<button onclick='saveRow("+rowId+")'>💾</button>";
}

function saveRow(rowId){
    var row = document.getElementById('row_'+rowId);
    var iat1 = row.querySelector('.iat1 input').value;
    var iat2 = row.querySelector('.iat2 input').value;
    var grade = row.querySelector('.grade input').value;
    var student_id = row.dataset.student;
    var semester = row.dataset.semester;
    var subject = row.dataset.subject;

    // Send via POST
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = 'update_marks_staff.php';

    var fields = {student_id:student_id, semester:semester, subject_name:subject, iat1:iat1, iat2:iat2, semester_grade:grade};
    for(var key in fields){
        var input = document.createElement('input');
        input.type='hidden'; input.name=key; input.value=fields[key];
        form.appendChild(input);
    }
    document.body.appendChild(form);
    form.submit();
}
</script>
</head>
<body>
<div class="container">
<a class="back" href="staff_dashboard.php">← Back to Dashboard</a>
<h2>Marks for <?php echo $student['name']; ?> (Overall CGPA: <?php echo $cgpa; ?> / 10)</h2>

<?php
if(!empty($sem_data)){
    foreach($sem_data as $sem => $data){
        echo "<h3 class='semester-title'>Semester $sem (GPA: {$data['gpa']}/10)</h3>";
        echo "<table>
                <tr>
                    <th>Subject</th>
                    <th>IAT1</th>
                    <th>IAT2</th>
                    <th>Total</th>
                    <th>Grade</th>
                    <th>Action</th>
                </tr>";
        foreach($data['subjects'] as $sub){
            $total = ($sub['iat1'] + $sub['iat2']) / 2; ;
            $rowId = $sub['id'];
            echo "<tr id='row_$rowId' data-student='{$student['id']}' data-semester='{$sub['semester']}' data-subject='{$sub['subject_name']}'>
                    <td>{$sub['subject_name']}</td>
                    <td class='iat1'>{$sub['iat1']}</td>
                    <td class='iat2'>{$sub['iat2']}</td>
                    <td>{$total}</td>
                    <td class='grade'>{$sub['semester_grade']}</td>
                    <td class='action'><button class='edit-btn' onclick='editRow($rowId)'>✏️</button></td>
                  </tr>";
        }
        echo "</table>";
    }
} else {
    echo "<p>No marks available for this student.</p>";
}
?>
</div>
</body>
</html>
