document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.score-input');
    const statusDiv = document.getElementById('scoreUpdateStatus');
    const podSelect = document.querySelector('select[name="pod"]');
    const competitionSelect = document.querySelector('select[name="competition"]');

    if (inputs && statusDiv) {
        inputs.forEach(input => {
            input.addEventListener('change', async function() {
                statusDiv.style.display = 'block';
                statusDiv.className = 'alert alert-info';
                statusDiv.textContent = 'Updating...';
                
                try {
                    const response = await fetch('/public/admin/pages/scores/functions.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            'action': 'update_score',
                            'user_id': this.dataset.userId,
                            'rule_id': this.dataset.ruleId,
                            'score': this.value,
                            'pod_id': this.dataset.podId,
                            'competition_id': competitionSelect.value, // Use the selected competition ID
                            'date': document.querySelector('input[name="date"]').value
                        })
                    });

                    const responseText = await response.text();
                    console.log('Server response:', responseText); // Debug log

                    let result;
                    try {
                        result = JSON.parse(responseText);
                    } catch (e) {
                        console.error('JSON Parse Error:', responseText);
                        throw new Error('Invalid server response');
                    }

                    if (!response.ok || !result.success) {
                        throw new Error(result.error || `Server error: ${response.status}`);
                    }

                    statusDiv.className = 'alert alert-success';
                    statusDiv.textContent = 'Score updated successfully';
                } catch (error) {
                    console.error('Update failed:', error);
                    statusDiv.className = 'alert alert-danger';
                    statusDiv.textContent = `Update failed: ${error.message}`;
                } finally {
                    setTimeout(() => {
                        if (statusDiv.className.includes('info')) {
                            statusDiv.style.display = 'none';
                        }
                    }, 2000);
                }
            });
        });
    }

    if (podSelect) {
        podSelect.addEventListener('change', function() {
            const podId = this.value;
            const date = document.querySelector('input[name="date"]').value;
            const competitionId = competitionSelect.value;
            
            if (podId) {
                window.location.href = `index.php?pod=${podId}&date=${date}&competition=${competitionId}`;
            }
        });
    }

    if (competitionSelect) {
        competitionSelect.addEventListener('change', function() {
            const competitionId = this.value;
            const podId = podSelect.value;
            const date = document.querySelector('input[name="date"]').value;
            
            if (competitionId) {
                window.location.href = `index.php?pod=${podId}&date=${date}&competition=${competitionId}`;
            }
        });
    }
});