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

// -- Update Modal --
// Get the modal
var modal = document.getElementById("updateModal");

// Get the buttons that open the modal
var btns = document.getElementsByClassName("updateButton");

// Get the <span> element that closes the modal
var span = document.getElementsByClassName("close")[0];

for (let i = 0; i < btns.length; i++) {
    btns[i].onclick = function () {
        // Get the row of the clicked button
        let row = this.parentElement.parentElement;

        // Get the data from the row
        let termID = row.children[0].innerText;
        let termKey = row.children[1].innerText;
        let termName = row.children[2].innerText;
        let termStart = row.children[3].innerText;
        let termEnd = row.children[4].innerText;

        // Get the form fields
        let form = document.querySelector('.termsFormUpdate form');
        let termIDField = form.querySelector('input[name="updateTermID"]');
        let termKeyField = form.querySelector('input[name="updateTermKey"]');
        let termNameField = form.querySelector('input[name="updateTermName"]');
        let termStartField = form.querySelector('input[name="updateTermStart"]');
        let termEndField = form.querySelector('input[name="updateTermEnd"]');

        // Populate the form fields with the data
        termIDField.value = termID;
        termKeyField.value = termKey;
        termNameField.value = termName;
        termStartField.value = termStart;
        termEndField.value = termEnd;

        // Show the modal
        modal.style.display = "block";
    }
}

// When the user clicks on <span> (x), close the modal
span.onclick = function() {
    modal.style.display = "none";
}

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
    if (event.target === modal) {
        modal.style.display = "none";
    }
}