<?php
#ini_set('display_errors', 1);
#ini_set('display_startup_errors', 1);
#error_reporting(E_ALL);

session_start([
    'cookie_lifetime' => 86400,
    'read_and_close'  => true,
]);

if (!empty($_POST)) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $db = new SQLite3('../backend/database/interDatabase.db');
    $stmt = $db->prepare('SELECT * FROM user WHERE Email = :email');
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);

    $result = $stmt->execute();
    $user = $result->fetchArray();

    // Debug information
    #echo 'POST data: ';
    #var_dump($_POST);
    #echo 'User data: ';
    #var_dump($user);

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
    <link rel="stylesheet" href="../static/css/index.css">
</head>
<body>
    <header>
        <div class="logo">
            <a>
                <img src="../static/img/inter-logo.png" alt="Inter CurricuLab">
                <h1>CurricuLab</h1>
            </a>
        </div>
    </header>
    <div id="container">
        <div class="form-container">
            <form method="post">
                <div class="header">
                    <h2>Login</h2>
                </div>
                <?php if (isset($error)): ?>
                    <div class="error">
                        <p><?php echo $error; ?></p>
                    </div>
                <?php endif; ?>
                <div class="inputBox">
                    <label for="email">Email:</label>
                    <input type="email" name="email" id="email" placeholder="Email" required>
                </div>
                <div class="inputBox">
                    <label for="password">Password:</label>
                    <input type="password" name="password" id="password" placeholder="Password" required>
                </div>
                <div class="inputBox">
                    <label for="accountType">Account Type:</label>
                    <select name="accountType" id="accountType">
                        <option value="" selected hidden="hidden">Select Account Type...</option>
                        <option value="admin">Admin</option>
                        <option value="prof">Professor</option>
                    </select>
                </div>
                <button type="submit">Login</button>
            </form>
        </div>
    </div>
    <footer>
        <p>&copy; 2024 Inter CurricuLab</p>
    </footer>
</body>
</html>