document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('certificateForm');
    const podSelect = document.getElementById('pod');
    const competitionSelect = document.getElementById('competition');
    const dateInput = document.getElementById('competition_date');
    const teamSelect = document.getElementById('winning_team');
    const membersSelect = document.getElementById('team_members');
    const placeSelects = ['first_place', 'second_place', 'third_place'].map(id => document.getElementById(id));

    // Handle pod selection
    podSelect?.addEventListener('change', function() {
        const podId = this.value;
        if (!podId) return;

        fetch(`/public/admin/pages/certs/index.php?action=get_pod_members&pod_id=${podId}`)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(members => {
                const options = members.map(m => 
                    `<option value="${m.id}">${m.first_name}</option>`
                ).join('');

                // Update team members select
                membersSelect.innerHTML = '<option value="">Select Members</option>' + options;
                
                // Update place selects
                placeSelects.forEach(select => {
                    select.innerHTML = '<option value="">Select Winner</option>' + options;
                });
            })
            .catch(error => console.error('Error loading pod members:', error));
    });

    // Handle competition selection
    competitionSelect?.addEventListener('change', function() {
        const competitionId = this.value;
        if (!competitionId) return;

        // Update date
        const selectedOption = this.options[this.selectedIndex];
        if (dateInput && selectedOption?.dataset.endDate) {
            const date = new Date(selectedOption.dataset.endDate);
            dateInput.value = date.toISOString().split('T')[0];
        }

        // Load teams
        fetch(`/public/admin/pages/certs/index.php?action=get_teams&competition_id=${competitionId}`)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(teams => {
                teamSelect.innerHTML = '<option value="">Select Team</option>' + 
                    teams.map(t => `<option value="${t.id}">${t.name}</option>`).join('');
            })
            .catch(error => console.error('Error loading teams:', error));
    });

    // Initialize if values are pre-selected
    if (podSelect?.value) podSelect.dispatchEvent(new Event('change'));
    if (competitionSelect?.value) competitionSelect.dispatchEvent(new Event('change'));
});