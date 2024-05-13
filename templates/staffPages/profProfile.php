<?php
// Modify error reporting for production use
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/php-error.log');

$config = require_once '../../backend/php/config.php';

session_start();

if (!isset($_SESSION['email'])) {
    header('Location: ../index.php');
    exit;
} elseif ($_SESSION['accountType'] !== 'Professor') {
    header('Location: ../adminPages/adminProfile.php');
    exit;
} else {
    if ($config['db']['type'] === 'sqlite') {
        $db = new SQLite3($config['db']['sqlite']['path']);
    } elseif ($config['db']['type'] === 'mysql') {
        $dsn = "mysql:host={$config['db']['mysql']['host']};dbname={$config['db']['mysql']['dbname']}";
        $db = new PDO($dsn, $config['db']['mysql']['username'], $config['db']['mysql']['password']);
    }

    $stmt = $db->prepare('SELECT * FROM user');
    $result = $stmt->execute();
    $users = $result->fetchArray(SQLITE3_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['userID'])) {
            updateUser();
        }
    }
}

function logAction($action) {
    // Log all actions taken by the user to single a txt file. If txt file does not exist, create it.
    // Log Format: [Date-Time] [Log Level] [User Email] [Transaction ID] [Action] [Status] [Message]
    $log = fopen('../../backend/log/log.txt', 'a');
    fwrite($log, '[' . date('Y-m-d H:i:s') . '] [INFO] ' . $_SESSION['email'] . ' - ' . $action . ' - Success' . PHP_EOL);
    fclose($log);
}

function updateUser() {
    global $db;

    $userID = $_POST['userID'];
    $email = $_POST['email'];
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $previousPassword = $_POST['previousPassword'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    $stmt = $db->prepare('SELECT * FROM user WHERE user.UserID = :userID');
    $stmt->bindValue(':userID', $userID, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);

    if (password_verify($previousPassword, $user['Password'])) {
        if ($password === $confirmPassword && $password !== '' && $confirmPassword !== '') {
            $password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare('UPDATE user SET Email = :email, FirstName = :firstName, LastName = :lastName, Password = :password WHERE user.UserID = :userID');
            $stmt->bindValue(':email', $email, SQLITE3_TEXT);
            $stmt->bindValue(':firstName', $firstName, SQLITE3_TEXT);
            $stmt->bindValue(':lastName', $lastName, SQLITE3_TEXT);
            $stmt->bindValue(':password', $password, SQLITE3_TEXT);
            $stmt->bindValue(':userID', $userID, SQLITE3_INTEGER);
            $stmt->execute();

            $_SESSION['email'] = $email;
            $_SESSION['firstName'] = $firstName;
            $_SESSION['lastName'] = $lastName;

            logAction('Updated account: ' . $_POST['email']);
        } elseif ($password === '' && $confirmPassword === '') {
            $stmt = $db->prepare('UPDATE user SET Email = :email, FirstName = :firstName, LastName = :lastName WHERE user.UserID = :userID');
            $stmt->bindValue(':email', $email, SQLITE3_TEXT);
            $stmt->bindValue(':firstName', $firstName, SQLITE3_TEXT);
            $stmt->bindValue(':lastName', $lastName, SQLITE3_TEXT);
            $stmt->bindValue(':userID', $userID, SQLITE3_INTEGER);
            $stmt->execute();

            $_SESSION['email'] = $email;
            $_SESSION['firstName'] = $firstName;
            $_SESSION['lastName'] = $lastName;

            logAction('Updated account: ' . $_POST['email']);
        } else {
            echo '<script>alert("Passwords do not match.")</script>';
        }
    } else {
        echo '<script>alert("Previous password is incorrect.")</script>';
    }
}

function fetchAllRows($result) {
    $rows = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $rows[] = $row;
    }
    return $rows;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Professor Profile</title>
    <link rel="stylesheet" type="text/css" href="../../static/css/headerFooter.css">
    <link rel="stylesheet" type="text/css" href="../../static/css/adminProfile.css">
</head>
<body>
    <div id="headerNav">
        <div class="logo">
            <a href="profPage.php">
                <img src="../../static/img/inter-logo-full.png" alt="Inter CurricuLab">
            </a>
        </div>
        <nav>
            <a href="profPage.php">Dashboard</a>
        </nav>
        <div class="userBox">
            <button onclick="myFunction()" class="userDropdownButton">
                <img src="../../static/img/userProfile.jpg" alt="User" >
                <?php
                echo $_SESSION['firstName'] . ' ' . $_SESSION['lastName'];
                ?>
            </button>
            <div id="userDropdown" class="dropdownContent">
                <a href="#">Profile</a>
                <a id="logout">Log Out</a>
            </div>
        </div>
    </div>
    <div id="container">
        <div class="container-upperBox">
            <!-- Profile, where the user can view and edit their Account/Profile Information (Username, Password, Email, etc.) -->
            <h1>Profile</h1>
            <div class="userInformation">
                <table>
                    <tr>
                        <th>Email</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                    </tr>
                    <tr>
                        <td><?php echo $_SESSION['email']; ?></td>
                        <td><?php echo $_SESSION['firstName']; ?></td>
                        <td><?php echo $_SESSION['lastName']; ?></td>
                    </tr>
                </table>
            </div>
            <?php
            // if current session email is 'root@localhost', then hide the form
            if ($_SESSION['email'] === 'root@localhost') {
                echo '<p>Root user cannot be edited.</p>';
            } else {
                ?>
                <div class="userModification">
                    <div class="header">
                        <h2>Modify User</h2>
                    </div>
                    <form method="post">
                        <label for="userID" hidden="hidden">User ID:</label><br>
                        <input hidden="hidden" type="text" id="userID" name="userID" value="<?php echo $_SESSION['userID']; ?>" readonly><br>
                        <label for="email">Email:</label><br>
                        <input type="email" id="email" name="email" value="<?php echo $_SESSION['email']; ?>" required><br>
                        <label for="firstName">First Name:</label><br>
                        <input type="text" id="firstName" name="firstName" value="<?php echo $_SESSION['firstName']; ?>" required><br>
                        <label for="lastName">Last Name:</label><br>
                        <input type="text" id="lastName" name="lastName" value="<?php echo $_SESSION['lastName']; ?>" required><br>
                        <label for="previousPassword">Previous Password:</label><br>
                        <input type="password" id="previousPassword" name="previousPassword" required><br>
                        <label for="password">Password:</label><br>
                        <input type="password" id="password" name="password"><br>
                        <label for="confirmPassword">Confirm Password:</label><br>
                        <input type="password" id="confirmPassword" name="confirmPassword"><br>
                        <input type="submit" value="Submit">
                    </form>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
    <script src="../../static/js/adminLogger.js"></script>
    <footer>
        <p>&copy; 2024 Inter CurricuLab</p>
    </footer>
</body>
</html>
