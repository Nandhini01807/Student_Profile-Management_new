<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'staff'){
    header("Location: login.php");
    exit();
}

include('db_connect.php');

// Grade mapping for CGPA calculation
$grade_map = ['O'=>10,'A+'=>9,'A'=>8,'B+'=>7,'B'=>6,'C'=>5,'F'=>0];

// Fetch all students
$filter_sql = " WHERE 1 ";

if(isset($_GET['search']) && !empty(trim($_GET['search']))){
    $s = $conn->real_escape_string($_GET['search']);
    $filter_sql .= " AND (name LIKE '%$s%' OR register_no LIKE '%$s%' OR department LIKE '%$s%') ";
}

// Sorting
$sort_column = "id";
$sort_order = "ASC";
$allowed_sort = ['id','name','register_no','department','cgpa'];
if(isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sort)){
    $sort_column = $_GET['sort'];
}
if(isset($_GET['order']) && in_array($_GET['order'], ['ASC','DESC'])){
    $sort_order = $_GET['order'];
}

$students_sql = "SELECT * FROM students $filter_sql ORDER BY $sort_column $sort_order";
$students_result = $conn->query($students_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff Dashboard - Students</title>
<style>
body{font-family:Arial,sans-serif;background:#f4f6f8;margin:0;padding:0;}
.container{max-width:1000px;margin:30px auto;background:white;padding:20px;border-radius:10px;box-shadow:0 0 10px rgba(0,0,0,0.1);}
h2{text-align:center;color:#2c3e50;}
table{width:100%;border-collapse:collapse;margin-top:20px;}
table, th, td{border:1px solid #ccc;}
th, td{padding:10px;text-align:center;}
th{background:#2c3e50;color:white;cursor:pointer;}
a.button{padding:5px 10px;background:#3498db;color:white;text-decoration:none;border-radius:5px;}
input[type=text]{padding:5px;width:200px;border:1px solid #ccc;border-radius:5px;}
button.sort-btn{background:#3498db;color:white;padding:5px 10px;border:none;border-radius:5px;cursor:pointer;}
</style>
</head>
<body>
<div class="container">
<h2>📘 Staff Dashboard - Student Records</h2>

<!-- Search Form -->
<form method="GET" style="text-align:center;margin-bottom:20px;">
    <input type="text" name="search" placeholder="Search by Name, Register No, Department" value="<?php echo isset($_GET['search'])?htmlspecialchars($_GET['search']):''; ?>">
    <button type="submit">Search</button>
</form>

<!-- Students Table -->
<table>
    <tr>
        <th><a href="?<?php echo http_build_query(array_merge($_GET,['sort'=>'id','order'=>($sort_order=='ASC'?'DESC':'ASC')])); ?>">ID</a></th>
        <th><a href="?<?php echo http_build_query(array_merge($_GET,['sort'=>'name','order'=>($sort_order=='ASC'?'DESC':'ASC')])); ?>">Name</a></th>
        <th>Register No</th>
        <th>Department</th>
        <th>CGPA</th>
        <th>Action</th>
    </tr>
    <?php
    if($students_result->num_rows > 0){
        while($student = $students_result->fetch_assoc()){
            // Calculate CGPA dynamically from marks
            $marks_query = $conn->query("SELECT semester_grade FROM marks WHERE student_id=".$student['id']);
            $total_points = 0;
            $count = 0;
            while($row = $marks_query->fetch_assoc()){
                $grade = $row['semester_grade'];
                if(isset($grade_map[$grade])){
                    $total_points += $grade_map[$grade];
                    $count++;
                }
            }
            $cgpa = $count>0 ? round($total_points/$count,2) : 0;
            // Update students table CGPA
            $conn->query("UPDATE students SET cgpa=$cgpa WHERE id=".$student['id']);

            echo "<tr>
                    <td>{$student['id']}</td>
                    <td>{$student['name']}</td>
                    <td>{$student['register_no']}</td>
                    <td>{$student['department']}</td>
                    <td>{$cgpa}</td>
                    <td><a class='button' href='view_marks_staff.php?student_id={$student['id']}'>👁 View</a></td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='6'>No students found.</td></tr>";
    }
    ?>
</table>

<!-- Bulk Upload Next Semester Marks -->
<h3>📤 Upload Next Semester Marks (Excel/CSV)</h3>
<form action="upload_marks_excel.php" method="POST" enctype="multipart/form-data">
    <input type="file" name="file" required>
    <button type="submit">Upload</button>
</form>

</div>
</body>
</html>
