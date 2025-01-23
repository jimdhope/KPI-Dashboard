document.addEventListener('DOMContentLoaded', function() {
    const resultsForm = document.getElementById('resultsForm');
    const rule1Select = document.querySelector('select[name="rule1"]');
    const rule2Select = document.querySelector('select[name="rule2"]');
    const target1Input = document.querySelector('input[name="target1"]');
    const target2Input = document.querySelector('input[name="target2"]');
    const podSelect = document.getElementById('pod');
    const dateInput = document.getElementById('date');
    const competitionSelect = document.getElementById('competition');

    function updateTargets(event) {
        event.preventDefault();
        const formData = new FormData(resultsForm);

        fetch(resultsForm.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update target displays
                if (data.ruleTotals) {
                    Object.keys(data.ruleTotals).forEach(ruleId => {
                        const targetElement = document.querySelector(`[data-rule-target="${ruleId}"]`);
                        if (targetElement) {
                            targetElement.textContent = `${data.ruleTotals[ruleId].name}: ${data.ruleTotals[ruleId].current}/${data.ruleTotals[ruleId].target}`;
                        }
                    });
                }
            }
        })
        .catch(error => console.error('Error:', error));
    }

    [rule1Select, rule2Select, target1Input, target2Input].forEach(element => {
        if (element) {
            element.addEventListener('change', updateTargets);
        }
    });

    if (podSelect) {
        podSelect.addEventListener('change', function() {
            console.log('Pod changed');
            document.getElementById('selectionForm').submit();
        });
    }

    if (dateInput) {
        dateInput.addEventListener('change', function() {
            console.log('Date changed');
            document.getElementById('selectionForm').submit();
        });
    }

    if (competitionSelect) {
        competitionSelect.addEventListener('change', function() {
            console.log('Competition changed');
            document.getElementById('selectionForm').submit();
        });
    } else {
        console.error('Competition select element not found');
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const targetForm = document.getElementById('targetForm');

    targetForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(targetForm);

        fetch('functions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('date', document.getElementById('date').value);
                currentUrl.searchParams.set('pod', document.getElementById('pod').value);
                window.location.href = currentUrl.toString();
            } else {
                alert('Failed to save targets: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => console.error('Error:', error));
    });
});