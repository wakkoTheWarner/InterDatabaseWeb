<?php
#ini_set('display_errors', 1);
#ini_set('display_startup_errors', 1);
#error_reporting(E_ALL);

$config = require_once '../backend/php/config.php';

session_start([
    'cookie_lifetime' => 86400,
]);

ini_set('session.cookie_secure', '1');
ini_set('session.cookie_httponly', '1');
if (!empty($_POST)) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = filter_var($_POST['password'], FILTER_SANITIZE_STRING);

    if ($config['db']['type'] === 'sqlite') {
        $db = new SQLite3($config['db']['sqlite']['path']);
    } elseif ($config['db']['type'] === 'mysql') {
        $dsn = "mysql:host={$config['db']['mysql']['host']};dbname={$config['db']['mysql']['dbname']}";
        $db = new PDO($dsn, $config['db']['mysql']['username'], $config['db']['mysql']['password']);
    }

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
        $_SESSION['userID'] = $user['UserID'];
        $_SESSION['email'] = $user['Email'];
        $_SESSION['firstName'] = $user['FirstName'];
        $_SESSION['lastName'] = $user['LastName'];
        $_SESSION['accountType'] = $user['AccountType'];

        if ($user['AccountType'] === 'Root' || $user['AccountType'] === 'Admin' || $user['AccountType'] === 'Staff') {
            header('Location: adminPages/adminDashboard.php');
            exit;
        } elseif ($user['AccountType'] === 'Professor') {
            header('Location: staffPages/profPage.php');
            exit;
        }
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
                <button type="submit">Login</button>
            </form>
        </div>
    </div>
    <footer>
        <p>&copy; 2024 Inter CurricuLab</p>
    </footer>
</body>
</html>