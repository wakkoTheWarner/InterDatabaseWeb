<?php

session_start();

if (!isset($_SESSION['email'])) {
    header('Location: ../index.php');
    exit;
} else {
    // Open the log file
    $logFile = fopen('../../backend/log/log.txt', 'r');

    // Check if the file was opened successfully
    if ($logFile) {
        // Read the entire contents of the file
        $logContents = fread($logFile, filesize('../../backend/log/log.txt'));

        // Close the file
        fclose($logFile);
    } else {
        // Handle the error
        $logContents = 'Error: Unable to open the log file.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Log Viewer</title>
    <link rel="stylesheet" href="../../static/css/headerFooter.css">
    <link rel="stylesheet" href="../../static/css/adminLogger.css">
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
                <div class="dropdownContent">
                    <a href="adminCompetency.php">Competencies</a>
                    <a href="adminSection.php">Section</a>
                </div>
            </div>
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
        <?php
        // check if log.txt exists, if not, show error message
        if ($logContents === 'Error: Unable to open the log file.') {
            ?>
            <div class="container-upperBox">
                <h1>Admin Log Viewer</h1>
                <textarea id="logContents" readonly>[WARNING!] The log "log.txt" file does not exist or could not be opened.</textarea>
            </div>
            <?php
        } else {
            ?>
            <div class="container-upperBox">
                <h1>Admin Log Viewer</h1>
                <textarea id="logContents" readonly><?php echo $logContents; ?></textarea>
            </div>
        <?php
        }
        ?>
    </div>
    <script>
        document.getElementById('logout').addEventListener('click', function() {
            fetch('../../backend/php/logout.php')
                .then(response => response.text())
                .then(data => {
                    if(data === 'success') {
                        window.location.href = '../index.php';
                    }
                });
        });

        function myFunction() {
            document.getElementById("userDropdown").classList.toggle("show");
        }

        window.onclick = function(event) {
            if (!event.target.matches('.userDropdownButton')) {
                var dropdowns = document.getElementsByClassName("dropdownContent");
                var i;
                for (i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }
    </script>
    <footer>
        <p>&copy; 2024 Inter CurricuLab</p>
    </footer>
</body>
</html>