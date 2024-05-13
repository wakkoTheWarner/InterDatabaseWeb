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
        if (isset($_POST['email']) && isset($_POST['firstName']) && isset($_POST['lastName']) && isset($_POST['previousPassword']) && isset($_POST['password']) && isset($_POST['confirmPassword'])) {
            updateUser();
        }
    }
}

function updateUser() {
    global $db;
    global $config;

    $email = $_POST['email'];
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $previousPassword = $_POST['previousPassword'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    if ($password !== $confirmPassword) {
        echo '<script>alert("Passwords do not match.");</script>';
        return;
    }

    if ($config['db']['type'] === 'sqlite') {
        $stmt = $db->prepare('SELECT * FROM user WHERE Email = :email');
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $result = $stmt->execute();
        $user = $result->fetchArray(SQLITE3_ASSOC);

        if ($user['password'] !== $previousPassword) {
            echo '<script>alert("Previous password is incorrect.");</script>';
            return;
        }

        $stmt = $db->prepare('UPDATE user SET Email = :email, FirstName = :firstName, LastName = :lastName, Password = :password WHERE Email = :email');
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':firstName', $firstName, SQLITE3_TEXT);
        $stmt->bindValue(':lastName', $lastName, SQLITE3_TEXT);
        $stmt->bindValue(':password', $password, SQLITE3_TEXT);
        $stmt->execute();
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
    <title>Admin Profile</title>
    <link rel="stylesheet" type="text/css" href="../../static/css/headerFooter.css">
    <link rel="stylesheet" type="text/css" href="../../static/css/adminProfile.css">
</head>
<body>
    <div id="headerNav">
        <div class="logo">
            <a href="#">
                <img src="../../static/img/inter-logo-full.png" alt="Inter CurricuLab">
            </a>
        </div>
        <nav>
            <a href="adminDashboard.php">Dashboard</a>
            <a href="adminTerms.php">Terms</a>
            <a href="adminPrograms.php">Programs</a>
            <div class="dropdown">
                <a href="adminCourses.php">Courses</a>
                <div class="navDropdownContent">
                    <a href="adminCompetency.php">Competencies</a>
                    <a href="adminSection.php">Section</a>
                </div>
            </div>
            <a class="navDivider"></a>
            <a href="adminProgramCourses.php">Program/Courses</a>
            <a href="adminTermCourses.php" class="active">Term/Courses</a>
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
                <a href="#">Logger</a>
                <a href="adminUsers.php">Users</a>
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
                    <label for="email">Email:</label><br>
                    <input type="email" id="email" name="email" value="<?php echo $_SESSION['email']; ?>" required><br>
                    <label for="firstName">First Name:</label><br>
                    <input type="text" id="firstName" name="firstName" value="<?php echo $_SESSION['firstName']; ?>" required><br>
                    <label for="lastName">Last Name:</label><br>
                    <input type="text" id="lastName" name="lastName" value="<?php echo $_SESSION['lastName']; ?>" required><br>
                    <label for="previousPassword">Previous Password:</label><br>
                    <input type="password" id="previousPassword" name="previousPassword" required><br>
                    <label for="password">Password:</label><br>
                    <input type="password" id="password" name="password" required><br>
                    <label for="confirmPassword">Confirm Password:</label><br>
                    <input type="password" id="confirmPassword" name="confirmPassword" required><br>
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
