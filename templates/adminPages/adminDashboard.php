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
        <a href="#">Terms</a>
        <a href="#">Programs</a>
        <a href="#">Courses</a>
    </nav>
    <div class="userBox">
        <button onclick="myFunction()" class="userDropdownButton">Welcome, <?php echo $_SESSION['email']; ?></button>
        <div id="userDropdown" class="dropdownContent">
            <a href="#">Profile</a>
            <a href="#">Settings</a>
            <a id="logout" style="background-color: #FF9999;">Log Out</a>
        </div>
    </div>
</div>
<div id="container">
    <button id="logout">Log Out</button>
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
</body>
</html>