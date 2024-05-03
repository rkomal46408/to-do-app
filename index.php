<?php
session_start();
include 'db.php';  // This includes our database connection

// Handle login
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = $pdo->prepare("SELECT * FROM Users WHERE username = ? AND password = ?");
    $query->execute([$username, $password]);
    $user = $query->fetch();

    if ($user) {
        $_SESSION['userID'] = $user['userID'];
        $_SESSION['username'] = $user['username'];
        header("Location: dashboard.php");  // Redirect to the dashboard page
        exit();
    } else {
        $login_error = "Incorrect username or password!";
    }
}

// Handle registration
if (isset($_POST['register'])) {
    $username = $_POST['reg_username'];
    $password = $_POST['reg_password'];
    $email = $_POST['reg_email'];

    $check = $pdo->prepare("SELECT * FROM Users WHERE username = ? OR email = ?");
    $check->execute([$username, $email]);
    if ($check->rowCount() > 0) {
        $reg_error = "Username or Email already exists!";
    } else {
        $insert = $pdo->prepare("INSERT INTO Users (username, password, email) VALUES (?, ?, ?)");
        $insert->execute([$username, $password, $email]);
        header("Location: index.php");
        exit();
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login and Register - Productivity App</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f7f7f7; }
        .card { margin-top: 10vh; }
        .big-branding { font-size: 2.5em; font-weight: bold; margin-bottom: 20px; }
        .fa { margin-right: 8px; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8" style="margin-top:10%">
            <div class="text-center big-branding">Productivity App</div>
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs">
                        <li class="nav-item">
                            <a class="nav-link active" id="login-tab" href="#login" data-toggle="tab">Login <i class="fas fa-sign-in-alt"></i></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="register-tab" href="#register" data-toggle="tab">Register <i class="fas fa-user-plus"></i></a>
                        </li>
                    </ul>
                </div>
                <div class="card-body tab-content">
                    <div class="tab-pane active" id="login">
                        <form method="POST">
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <button type="submit" name="login" class="btn btn-primary">Login</button>
                            <?php if (isset($login_error)): ?>
                                <div class="alert alert-danger mt-2"><?php echo $login_error; ?></div>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="tab-pane" id="register">
                        <form method="POST">
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" name="reg_username" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Password</label
                                <input type="password" name="reg_password" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="reg_email" class="form-control" required>
                            </div>
                            <button type="submit" name="register" class="btn btn-success">Register</button>
                            <?php if (isset($reg_error)): ?>
                                <div class="alert alert-danger mt-2"><?php echo $reg_error; ?></div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $(function () {
        $('.nav-tabs a').click(function (e) {
            e.preventDefault();
            $(this).tab('show');
        });
    });
</script>
</body>
</html>
