document.addEventListener('DOMContentLoaded', function() {
    const certificatesContainer = document.getElementById('certificates');

    if (certificatesContainer) {
        const teamData = JSON.parse('<?php echo json_encode($teamData); ?>');
        const leaderboardData = JSON.parse('<?php echo json_encode($leaderboardData); ?>');
        const teamManager = "<?php echo htmlspecialchars($teamManager); ?>";
        const certificateType = "<?php echo htmlspecialchars($certificateType); ?>";

        const loadTemplate = async (templateName) => {
            const response = await fetch(`/public/admin/pages/certs/templates/${templateName}.svg`);
            return response.text();
        };

        const createCertificate = async (data, templateName) => {
            const template = await loadTemplate(templateName);
            let certificateContent = template
                .replace('{{Name}}', data.name)
                .replace('{{Points}}', data.points)
                .replace('{{TeamManager}}', teamManager);

            const certificate = document.createElement('div');
            certificate.className = 'certificate';
            certificate.innerHTML = certificateContent;
            certificatesContainer.appendChild(certificate);
        };

        if (certificateType === 'team') {
            teamData.forEach(async team => {
                await createCertificate({
                    name: team.team_name,
                    points: team.total_points
                }, 'team');
            });
        } else if (certificateType === 'individual') {
            leaderboardData.forEach(async user => {
                await createCertificate({
                    name: `${user.first_name} ${user.last_name}`,
                    points: user.total_points
                }, certificateType);
            });
        }
    }
});