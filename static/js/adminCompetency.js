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
        let row = this.closest('tr');

        // Get the data from the row
        let competencyID = row.children[0].innerText;
        let competencyKey = row.children[1].innerText;
        let competencyDesc = row.children[2].innerText;
        let competencyMetric = row.children[3].innerText;
        let metricResult = row.children[4].innerText;
        let studentStrengths = row.children[5].innerText;
        let studentWeaknesses = row.children[6].innerText;
        let recommendations = row.children[7].innerText;
        let evaluationInstrument = row.children[8].innerText;

        // Get the form fields
        let form = document.querySelector('.competenciesFormUpdate form');
        let competencyIDField = form.querySelector('input[name="updateCompetencyID"]');
        let competencyKeyField = form.querySelector('input[name="updateCompetencyKey"]');
        let competencyDescField = form.querySelector('textarea[name="updateCompetencyDesc"]');
        let competencyMetricField = form.querySelector('textarea[name="updateCompetencyMetric"]');
        let metricResultField = form.querySelector('textarea[name="updateMetricResult"]');
        let studentStrengthsField = form.querySelector('textarea[name="updateStudentStrengths"]');
        let studentWeaknessesField = form.querySelector('textarea[name="updateStudentWeaknesses"]');
        let recommendationsField = form.querySelector('textarea[name="updateRecommendations"]');
        let evaluationInstrumentField = form.querySelector('textarea[name="updateEvaluationInstrument"]');

        // Populate the form fields with the data
        competencyIDField.value = competencyID;
        competencyKeyField.value = competencyKey;
        competencyDescField.value = competencyDesc;
        competencyMetricField.value = competencyMetric;
        metricResultField.value = metricResult;
        studentStrengthsField.value = studentStrengths;
        studentWeaknessesField.value = studentWeaknesses;
        recommendationsField.value = recommendations;
        evaluationInstrumentField.value = evaluationInstrument;

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