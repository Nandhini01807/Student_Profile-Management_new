<?php
session_start();
include('db_connect.php');

if(isset($_POST['login'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    // check students
    $res = $conn->query("SELECT * FROM students WHERE email='$email'");
    if($res->num_rows>0){
        $row = $res->fetch_assoc();
        if($password == $row['password']){
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_type'] = 'student';
            $_SESSION['user_name'] = $row['name'];
            header("Location: student_dashboard.php");
            exit;
        } else $error = "Invalid password!";
    } else {
        // check staff
        $res = $conn->query("SELECT * FROM staff WHERE email='$email'");
        if($res->num_rows>0){
            $row = $res->fetch_assoc();
            if($password == $row['password']){
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_type'] = 'staff';
                $_SESSION['user_name'] = $row['name'];
                header("Location: staff_dashboard.php");
                exit;
            } else $error = "Invalid password!";
        } else $error="No account found!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Login</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="login-box">
<h2>Login</h2>
<form method="POST">
<input type="email" name="email" placeholder="Email" required><br>
<input type="password" name="password" placeholder="Password" required><br>
<button type="submit" name="login">Login</button>
</form>
<p style="color:red;"><?php if(isset($error)) echo $error; ?></p>
</div>
</body>
</html>
