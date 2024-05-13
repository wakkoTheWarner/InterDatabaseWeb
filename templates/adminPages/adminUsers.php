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
} elseif ($_SESSION['accountType'] !== 'Admin' && $_SESSION['accountType'] !== 'Root' && $_SESSION['accountType'] !== 'Staff') {
    header('Location: ../staffPages/profPage.php');
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
        if (isset($_POST['updateUserID'], $_POST['updateEmail'], $_POST['updatePassword'], $_POST['updateFirstName'], $_POST['updateLastName'], $_POST['updateAccountType'])) {
            updateUser();
        } elseif (isset($_POST['delete'])) {
            deleteUser();
        } elseif (isset($_POST['email'], $_POST['password'], $_POST['firstName'], $_POST['lastName'], $_POST['accountType'])) {
            addUser();
        }
    }

    $users = []; // Initialize the $users variable.
    if (isset($_POST['sort'])) {
        $users = sortTable(); // Save sorted array to the $users variable
    } else {
        $stmt = $db->prepare('SELECT * FROM user');
        $result = $stmt->execute();
        $users = fetchAllRows($result);
    }
}

function logAction($action) {
    // Log all actions taken by the user to single a txt file. If txt file does not exist, create it.
    // Log Format: [Date-Time] [Log Level] [User Email] [Transaction ID] [Action] [Status] [Message]
    $log = fopen('../../backend/log/log.txt', 'a');
    fwrite($log, '[' . date('Y-m-d H:i:s') . '] [INFO] ' . $_SESSION['email'] . ' - ' . $action . ' - Success' . PHP_EOL);
    fclose($log);
}

function addUser() {
    global $db;
    $stmt = $db->prepare('INSERT INTO user (Email, Password, FirstName, LastName, AccountType) VALUES (:email, :password, :firstName, :lastName, :accountType)');
    $stmt->bindValue(':email', $_POST['email'], SQLITE3_TEXT);
    $stmt->bindValue(':firstName', $_POST['firstName'], SQLITE3_TEXT);
    $stmt->bindValue(':lastName', $_POST['lastName'], SQLITE3_TEXT);
    $stmt->bindValue(':accountType', $_POST['accountType'], SQLITE3_TEXT);

    // Print out the POST parameters
    print_r($_POST);

    echo 'POST data: ';
    var_dump($_POST);

    // Hash the password using BCrypt
    $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt->bindValue(':password', $hashedPassword, SQLITE3_TEXT);

    $result = $stmt->execute();

    // Print out the result of the execute function
    print_r($result);

    // if user typed in user key that already exists, display error message
    if ($db->lastErrorCode() === 19) {
        header('Location: adminUsers.php?error=' . urlencode('User key already exists.'));
    } else {
        logAction('User added: ' . $_POST['email']);
        header('Location: adminUsers.php');
    }
    exit;
}

function updateUser() {
    global $db;
    $stmt = $db->prepare('UPDATE user SET Email = :email, FirstName = :firstName, LastName = :lastName, AccountType = :accountType WHERE UserID = :userID');
    $stmt->bindValue(':email', $_POST['updateEmail'], SQLITE3_TEXT);
    $stmt->bindValue(':firstName', $_POST['updateFirstName'], SQLITE3_TEXT);
    $stmt->bindValue(':lastName', $_POST['updateLastName'], SQLITE3_TEXT);
    $stmt->bindValue(':accountType', $_POST['updateAccountType'], SQLITE3_TEXT);
    $stmt->bindValue(':userID', $_POST['updateUserID'], SQLITE3_INTEGER);

    // Check if the password field is not empty
    if (!empty($_POST['updatePassword'])) {
        // Hash the new password before storing it
        $hashedPassword = password_hash($_POST['updatePassword'], PASSWORD_DEFAULT);

        // Prepare a new SQL query to include the password update
        $stmt = $db->prepare('UPDATE user SET Email = :email, Password = :password, FirstName = :firstName, LastName = :lastName, AccountType = :accountType WHERE UserID = :userID');
        $stmt->bindValue(':password', $hashedPassword, SQLITE3_TEXT);
        $stmt->bindValue(':email', $_POST['updateEmail'], SQLITE3_TEXT);
        $stmt->bindValue(':firstName', $_POST['updateFirstName'], SQLITE3_TEXT);
        $stmt->bindValue(':lastName', $_POST['updateLastName'], SQLITE3_TEXT);
        $stmt->bindValue(':accountType', $_POST['updateAccountType'], SQLITE3_TEXT);
        $stmt->bindValue(':userID', $_POST['updateUserID'], SQLITE3_INTEGER);
    }

    $stmt->execute();

    // if user typed in user key that already exists, display error message
    if ($db->lastErrorCode() === 19) {
        header('Location: adminUsers.php?error=' . urlencode('User key already exists.'));
    } else {
        logAction('User updated: ' . $_POST['updateEmail']);
        header('Location: adminUsers.php');
    }
    exit;
}

function deleteUser() {
    global $db;
    // query the section ID to get the section key, course key, and professor email for the logger
    $stmt = $db->prepare('SELECT * FROM user WHERE UserID = :userID');
    $stmt->bindValue(':userID', $_POST['delete'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    $section = $result->fetchArray(SQLITE3_ASSOC);

    logAction('Deleted user: ' . $section['Email']);

    $stmt = $db->prepare('DELETE FROM user WHERE UserID = :userID');
    $stmt->bindValue(':userID', $_POST['delete'], SQLITE3_INTEGER);
    $stmt->execute();
    header('Location: adminUsers.php');
    exit;
}

function sortTable() {
    global $db;
    $allowed_keys = ['Email', 'Password', 'FirstName', 'LastName', 'AccountType'];
    $sort = isset($_POST['sort']) && in_array($_POST['sort'], $allowed_keys) ? $_POST['sort'] : 'Email';

    $stmt = $db->prepare("SELECT * FROM user ORDER BY $sort");
    $result = $stmt->execute();
    $users = fetchAllRows($result);
    return $users;
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
    <meta charset="UTF-8">
    <title>Admin Users Manager</title>
    <link rel="stylesheet" href="../../static/css/headerFooter.css">
    <link rel="stylesheet" href="../../static/css/adminUsers.css">
</head>
<body>
    <div id="headerNav">
        <div class="logo">
            <a href="adminDashboard.php">
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
                <a href="adminProfile.php">Profile</a>
                <?php
                if ($_SESSION['accountType'] === 'Admin' || $_SESSION['accountType'] === 'Root') {
                    echo '<a href="adminLogger.php">Logger</a>';
                }
                ?>
                <a href="#">Users</a>
                <a id="logout">Log Out</a>
            </div>
        </div>
    </div>
    <div id="container">
        <div class="container-upperBox">
            <div class="usersForm">
                <?php if (isset($_GET['error'])): ?>
                    <div class="error">
                        <p><?php echo htmlspecialchars($_GET['error']); ?></p>
                    </div>
                <?php endif; ?>
                <div class="header">
                    <h2>Users Manager</h2>
                </div>
                <?php
                // if current session user is not root or admin, do not allow to add new user
                if ($_SESSION['accountType'] === 'Root' || $_SESSION['accountType'] === 'Admin') {
                ?>
                <div class="form-container">
                    <form method="POST">
                        <div class="inputBox">
                            <label for="email">Email:</label>
                            <input type="email" name="email" id="email" placeholder="Email" required>
                        </div>
                        <div class="inputBox">
                            <label for="password">Password:</label>
                            <input type="password" name="password" id="password" placeholder="Password" required>
                        </div>
                        <div class="inputBox">
                            <label for="firstName">First Name:</label>
                            <input type="text" name="firstName" id="firstName" placeholder="First Name" required>
                        </div>
                        <div class="inputBox">
                            <label for="lastName">Last Name:</label>
                            <input type="text" name="lastName" id="lastName" placeholder="Last Name" required>
                        </div>
                        <div class="inputBox">
                            <label for="accountType">Account Type:</label>
                            <select name="accountType" id="accountType" required>
                                <option value="" hidden="hidden" selected>Select Account Type</option>
                                <?php
                                // Use the account types from accountType table
                                $stmt = $db->prepare('SELECT * FROM accountType');
                                $result = $stmt->execute();
                                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                                    // if user is not root, root and admin account type should not be available
                                    if ($_SESSION['accountType'] !== 'Root' && $row['AccountType'] !== 'Root' && $row['AccountType'] !== 'Admin') {
                                        echo '<option value="' . htmlspecialchars($row['AccountType'] ?? '') . '">' . htmlspecialchars($row['AccountType'] ?? '') . '</option>';
                                    } elseif ($_SESSION['accountType'] === 'Root') {
                                        echo '<option value="' . htmlspecialchars($row['AccountType'] ?? '') . '">' . htmlspecialchars($row['AccountType'] ?? '') . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="inputBox">
                            <button type="submit">Add User</button>
                        </div>
                    </form>
                </div>
                <?php
                }
                ?>
            </div>
            <div class="usersTable">
                <table>
                    <tr>
                        <th hidden="hidden">User ID</th>
                        <th>
                            <form method="POST">
                                <button type="submit" name="sort" value="Email">Email</button>
                            </form>
                        </th>
                        <th>
                            <form method="POST">
                                <button type="submit" name="sort" value="Password">Password</button>
                            </form>
                        </th>
                        <th>
                            <form method="POST">
                                <button type="submit" name="sort" value="FirstName">First Name</button>
                            </form>
                        </th>
                        <th>
                            <form method="POST">
                                <button type="submit" name="sort" value="LastName">Last Name</button>
                            </form>
                        </th>
                        <th>
                            <form method="POST">
                                <button type="submit" name="sort" value="AccountType">Account Type</button>
                            </form>
                        </th>
                        <?php
                        if ($_SESSION['accountType'] === 'Root' || $_SESSION['accountType'] === 'Admin') {
                        ?>
                        <th>Actions</th>
                        <?php
                        }
                        ?>
                    </tr>
                    <?php
                    foreach ($users as $row) {
                        echo '<tr>';
                        echo '<td hidden>' . htmlspecialchars($row['UserID'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($row['Email'] ?? '') . '</td>';
                        // randomly generate asterisks for password security from 8 to 16 characters
                        $passwordLength = rand(8, 16);
                        $passwordAsterisks = str_repeat('*', $passwordLength);
                        echo '<td>' . htmlspecialchars($passwordAsterisks) . '</td>';
                        echo '<td>' . htmlspecialchars($row['FirstName'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($row['LastName'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($row['AccountType'] ?? '') . '</td>';
                        echo '<td>';
                        // if root user, do not allow to update or delete
                        if ($_SESSION['accountType'] === 'Root' || $_SESSION['accountType'] === 'Admin') {
                            if ($row['AccountType'] !== 'Root') {
                            ?>
                            <div class="actionButtons">
                                <button class="updateButton">Update</button>
                                <form method="POST">
                                    <input type="hidden" name="delete" value="<?php echo htmlspecialchars($row['UserID'] ?? ''); ?>">
                                    <?php
                                    // if session user is admin, do not allow to delete root and admin users
                                    if ($_SESSION['accountType'] === 'Admin' && $row['AccountType'] === 'Admin') {
                                        ?>
                                    <button class="deleteButton" type="submit" disabled style="background-color: grey;">Delete</button>
                                    <?php
                                    } else {
                                        ?>
                                    <button class="deleteButton" type="submit">Delete</button>
                                    <?php
                                    }
                                    ?>
                                </form>
                            </div>
                            <?php
                            } else {
                                ?>
                            <div class="actionButtons">
                                <button class="updateButton" disabled style="background-color: grey;">Update</button>
                                <form method="POST">
                                    <input type="hidden" name="delete" value="<?php echo htmlspecialchars($row['UserID'] ?? ''); ?>">
                                    <button class="deleteButton" type="submit" disabled style="background-color: grey;">Delete</button>
                                </form>
                            </div>
                            <?php
                            }
                        }
                        echo '</td>';
                        echo '</tr>';
                    }
                    ?>
                </table>
            </div>
        </div>
    </div>
    <div id="updateModal" class="modal">
        <div class="modalContainer">
            <div class="closeButton">
                <span class="close">&times;</span>
            </div>
            <div class="usersFormUpdate">
                <div class="header">
                    <h2>Update User</h2>
                </div>
                <div class="form-container">
                    <form method="POST">
                        <div class="inputBox" hidden="hidden">
                            <label for="updateUserID" hidden="hidden">User ID:</label>
                            <input type="text" name="updateUserID" id="updateUserID" placeholder="User ID" required hidden="hidden">
                        </div>
                        <div class="inputBox">
                            <label for="updateEmail">Email:</label>
                            <input type="email" name="updateEmail" id="updateEmail" placeholder="Email" required>
                        </div>
                        <div class="inputBox">
                            <label for="updatePassword">Password:</label>
                            <input type="password" name="updatePassword" id="updatePassword" placeholder="Password">
                        </div>
                        <div class="inputBox">
                            <label for="updateFirstName">First Name:</label>
                            <input type="text" name="updateFirstName" id="updateFirstName" placeholder="First Name" required>
                        </div>
                        <div class="inputBox">
                            <label for="updateLastName">Last Name:</label>
                            <input type="text" name="updateLastName" id="updateLastName" placeholder="Last Name" required>
                        </div>
                        <?php
                        // if current session user is not root, do not allow to update account type
                        if ($_SESSION['accountType'] === 'Root') {
                            ?>
                        <div class="inputBox" hidden="hidden">
                            <label for="updateAccountType">Account Type:</label>
                            <select name="updateAccountType" id="updateAccountType" required>
                                <option value="" hidden="hidden" selected>Select Account Type</option>
                                <?php
                                // Use the account types from accountType table
                                $stmt = $db->prepare('SELECT * FROM accountType');
                                $result = $stmt->execute();
                                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                                    echo '<option value="' . htmlspecialchars($row['AccountType'] ?? '') . '">' . htmlspecialchars($row['AccountType'] ?? '') . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <?php
                        } else {
                            ?>
                        <div class="inputBox" hidden="hidden">
                            <label for="updateAccountType" hidden="hidden">Account Type:</label>
                            <select name="updateAccountType" id="updateAccountType" required hidden="hidden">
                                <option value="" hidden="hidden" selected>Select Account Type</option>
                                <?php
                                // Use the account types from accountType table
                                $stmt = $db->prepare('SELECT * FROM accountType');
                                $result = $stmt->execute();
                                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                                    echo '<option value="' . htmlspecialchars($row['AccountType'] ?? '') . '">' . htmlspecialchars($row['AccountType'] ?? '') . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <?php
                        }
                        ?>
                        <div class="inputBox">
                            <button type="submit">Update User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <footer>
        <p>&copy; 2024 Inter CurricuLab</p>
    </footer>
    <script src="../../static/js/adminUsers.js"></script>
</body>
</html>