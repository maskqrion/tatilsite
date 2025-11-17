document.addEventListener('DOMContentLoaded', async () => {
    const listsContainer = document.getElementById('public-lists-container');

    const createListCard = (list) => {
        return `
            <div class="public-list-card fade-in">
                <h3>${list.liste_adi}</h3>
                <p class="list-meta">
                    <strong>${list.olusturan_kullanici}</strong> tarafından oluşturuldu.
                </p>
                <p>${list.aciklama || '<em>Bu liste için bir açıklama girilmemiş.</em>'}</p>
                </div>
        `;
    };

    try {
        const response = await fetch('public_lists_getir.php');
        const result = await response.json();

        if (result.success && result.data.length > 0) {
            listsContainer.innerHTML = result.data.map(createListCard).join('');
            // Fade-in animasyonunu tetikle
            document.querySelectorAll('.fade-in').forEach((el, index) => {
                setTimeout(() => el.classList.add('visible'), index * 100);
            });
        } else if (result.success) {
            listsContainer.innerHTML = '<p style="text-align:center;">Henüz herkese açık olarak paylaşılmış bir liste bulunmuyor.</p>';
        } else {
            listsContainer.innerHTML = `<p style="text-align:center;">${result.message}</p>`;
        }
    } catch (error) {
        console.error('Listeler yüklenirken hata:', error);
        listsContainer.innerHTML = '<p style="text-align:center;">Listeler yüklenirken bir sorun oluştu.</p>';
    }
});