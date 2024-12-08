<?php
session_start();
require_once "../config/db.php";

$error = '';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// if (!$conn) {
//     die("Connection to database failed. Check your database configuration.");
// } else {
//     echo "Connection to database successful.";  // This is just for testing and should be removed in production
// }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_phone = $_POST['teacher_phone'];
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember_me']);  // Check if 'Remember Me' is checked

    $db = new Database();
    $conn = $db->getConnection();

    // Check if the user exists in the database
    $query = "SELECT * FROM teachers WHERE teacher_phone = :teacher_phone AND password = :password";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':teacher_phone', $teacher_phone);
    $stmt->bindParam(':password', $password);  // Plain text password
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['user_id'] = $user['teacher_id'];
        $_SESSION['role'] = $user['role'];

        // Set cookies if 'Remember Me' is checked
        if ($remember_me) {
            // Set the cookie to expire in 30 days
            setcookie('teacher_phone', $teacher_phone, time() + (86400 * 30), "/"); // 86400 = 1 day
            setcookie('password', $password, time() + (86400 * 30), "/"); // 86400 = 1 day
        } else {
            // If 'Remember Me' is not checked, clear the cookies
            setcookie('teacher_phone', '', time() - 3600, "/");
            setcookie('password', '', time() - 3600, "/");
        }

        // Redirect based on the user's role
        if ($_SESSION['role'] === 'admin') {
            header("Location: /public/admin/dashboard.php");
        } else {
            header("Location: /public/teacher/attendance.php");
        }
        exit;
    } else {
        $error = "Invalid login credentials!";
    }
}
?>


<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Login - Parasat</title>
    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i&amp;display=swap">
    <link rel="stylesheet" href="../assets/fonts/fontawesome-all.min.css">
</head>

<body class="bg-gradient-primary">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5 col-xl-4">
                <div class="card shadow-lg o-hidden border-0 my-5">
                    <div class="card-body p-4">
                        <div class="text-center">
                            <h4 class="text-dark mb-4">Приветствую!</h4>
                        </div>

                        <!-- Login Form -->
                        <form class="user" method="POST" action="">
                            <div class="mb-3">
                                <input class="form-control form-control-user" type="text" id="phone" placeholder="Логин" name="teacher_phone" value="<?= isset($_COOKIE['teacher_phone']) ? $_COOKIE['teacher_phone'] : '' ?>" required>
                            </div>
                            <div class="mb-3">
                                <input class="form-control form-control-user" type="password" id="password" placeholder="Пароль" name="password" value="<?= isset($_COOKIE['password']) ? $_COOKIE['password'] : '' ?>" required>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="formCheck-1" name="remember_me" <?= isset($_COOKIE['teacher_phone']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="formCheck-1">Запомнить меня</label>
                                </div>
                            </div>
                            <button class="btn btn-primary d-block w-100" type="submit">Войти</button>

                            <!-- Display error message -->
                            <?php if (!empty($error)): ?>
                                <div class="text-center" style="color: red; margin-top: 10px;">
                                    <?= $error ?>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="../assets/js/bs-init.js"></script>
    <script src="../assets/js/theme.js"></script>
</body>

</html>
