document.addEventListener('DOMContentLoaded', async () => {
    const container = document.getElementById('liderlik-tablosu-container');

    const createLeaderboardItem = (user, rank) => {
        return `
            <div class="leaderboard-item rank-${rank}">
                <span class="leaderboard-rank">#${rank}</span>
                <a href="kullanici.html?id=${user.id}" class="leaderboard-user">
                    <img src="${user.profile_image || 'assets/avatars/default.png'}" alt="${user.name}">
                    <span class="leaderboard-user-name">${user.name}</span>
                </a>
                <span class="leaderboard-score">${user.toplam_puan} Puan</span>
            </div>
        `;
    };

    try {
        const response = await fetch('liderlik_tablosu_getir.php');
        const result = await response.json();

        if (result.success && result.data.length > 0) {
            container.innerHTML = result.data.map((user, index) => {
                return createLeaderboardItem(user, index + 1);
            }).join('');
        } else {
            container.innerHTML = '<p style="text-align:center;">Liderlik tablosu için yeterli veri bulunamadı.</p>';
        }
    } catch (error) {
        console.error('Liderlik tablosu yüklenirken hata:', error);
        container.innerHTML = '<p style="text-align:center;">Liderlik tablosu yüklenirken bir sorun oluştu.</p>';
    }
});