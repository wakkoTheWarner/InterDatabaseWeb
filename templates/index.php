<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!empty($_POST)) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $db = new SQLite3('../backend/database/interDatabase.db');
    $stmt = $db->prepare('SELECT * FROM user WHERE Email = :email');
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);

    $result = $stmt->execute();
    $user = $result->fetchArray();

    // Debug information
    echo 'POST data: ';
    var_dump($_POST);
    echo 'User data: ';
    var_dump($user);

    if ($user && password_verify($password, $user['Password'])) {
        session_regenerate_id();
        $_SESSION['email'] = $user['Email'];
        header('Location: adminPages/adminDashboard.php');
        exit;
    } else {
        $error = 'Invalid email or password.';
    }
} elseif (isset($_SESSION['email'])) {
    header('Location: adminPages/adminDashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inter CurricuLab - Login</title>
</head>
<body>
    <header>
        <div class="logo">
            <img src="../static/img/inter-logo.png" alt="Inter CurricuLab">
            <h1>Inter CurricuLab</h1>
        </div>
    </header>
    <div id="container">
        <div class="form-container">
            <h1>Login</h1>
            <?php if (isset($error)): ?>
                <p><?php echo $error; ?></p>
            <?php endif; ?>
            <form method="post">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" required>
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" required>
                <label for="accountType">Account Type:</label>
                <select name="accountType" id="accountType">
                    <option value="" selected hidden="hidden">Select Account Type...</option>
                    <option value="admin">Admin</option>
                    <option value="prof">Professor</option>
                </select>
                <input type="submit" value="Login">
            </form>
        </div>
    </div>
    <footer hidden="hidden">
        <p>&copy; 2024 Inter CurricuLab</p>
    </footer>
</body>
</html>