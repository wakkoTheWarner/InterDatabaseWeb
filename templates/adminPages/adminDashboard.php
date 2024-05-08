<?php

session_start();

if (!isset($_SESSION['email'])) {
    header('Location: ../index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../../static/css/headerFooter.css">
    <link rel="stylesheet" href="../../static/css/adminDashboard.css">
</head>
<body>
    <div id="headerNav">
        <div class="logo">
            <a href="#">
                <img src="../../static/img/inter-logo-full.png" alt="Inter CurricuLab">
            </a>
        </div>
        <nav>
            <a href="#">Dashboard</a>
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
                <?php
                if ($_SESSION['accountType'] === 'Admin' || $_SESSION['accountType'] === 'Root') {
                    echo '<a href="adminLogger.php">Logger</a>';
                }
                ?>
                <a href="adminUsers.php">Users</a>
                <a id="logout">Log Out</a>
            </div>
        </div>
    </div>
    <div id="container">
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