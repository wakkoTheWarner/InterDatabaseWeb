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