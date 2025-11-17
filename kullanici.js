document.addEventListener('DOMContentLoaded', async () => {
    const profileContainer = document.getElementById('public-profile-container');
    const params = new URLSearchParams(window.location.search);
    const userId = params.get('id');

    if (!userId) {
        profileContainer.innerHTML = '<p style="text-align:center;">Kullanıcı bulunamadı.</p>';
        return;
    }

    try {
        const response = await fetch(`kullanici_profili_getir.php?id=${userId}`);
        const result = await response.json();

        if (result.success) {
            const { user, yorumlar } = result.data;
            document.title = `${user.name} | Seçkin Rotalar`;

            const yorumlarHTML = yorumlar.length > 0 ? yorumlar.map(yorum => `
                <div class="comment-history-item">
                    <div class="comment-history-meta">
                        <a href="rota-detay.html?id=${yorum.rota_id}">${yorum.rota_adi}</a> rotasına, ${yorum.tarih} tarihinde yaptığı yorum:
                    </div>
                    <p>"${yorum.yorum_metni}"</p>
                </div>
            `).join('') : '<p>Bu kullanıcı henüz hiç yorum yapmamış.</p>';

            profileContainer.innerHTML = `
                <div class="profile-header">
                    <img src="${user.profile_image || 'assets/avatars/default.png'}" alt="${user.name}" class="profile-picture">
                    <h1 class="profile-name">${user.name}</h1>
                    <p class="profile-meta">${user.created_at} tarihinden beri üye</p>
                </div>
                <h2>Kullanıcının Yorumları</h2>
                <div id="yorum-gecmisi">
                    ${yorumlarHTML}
                </div>
            `;
        } else {
            profileContainer.innerHTML = `<p style="text-align:center;">${result.message}</p>`;
        }
    } catch (error) {
        console.error('Profil bilgileri yüklenirken hata:', error);
        profileContainer.innerHTML = '<p style="text-align:center;">Profil yüklenirken bir sorun oluştu.</p>';
    }
});