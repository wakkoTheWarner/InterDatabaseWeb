// -- Logout Function --
document.getElementById('logout').addEventListener('click', function() {
    fetch('../../backend/php/logout.php')
        .then(response => response.text())
        .then(data => {
            if(data === 'success') {
                window.location.href = '../index.php';
            }
        });
});

// -- UserBox Dropdown --
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

document.getElementById('termSelectorButton').addEventListener('click', function() {
    const selectedTermKey = document.getElementById('term').value;
    window.location.href = window.location.pathname + '?term=' + selectedTermKey;
});

document.getElementById('resetButton').addEventListener('click', function(event) {
    event.preventDefault(); // Prevent the form from submitting
    window.location.href = window.location.pathname; // Redirect to the current page without query parameters
});

// Modal