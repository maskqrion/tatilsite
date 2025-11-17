document.addEventListener('DOMContentLoaded', async () => {
    const favoriListesi = document.getElementById('favoriListesi');
    const favoriAciklama = document.getElementById('favori-aciklama');

    let tumRotalar = [];

    const createRotaKarti = (rota) => {
        // GÜNCELLEME: Basitleştirilmiş fonksiyon kullanıldı.
        return `
            <div class="destinasyon-karti-wrapper fade-in visible">
                <a href="rota-detay.html?id=${rota.id}" class="destinasyon-karti">
                    <img src="${rota.resim || 'https://via.placeholder.com/800x600.png?text=Resim+Bulunamadı'}" alt="${rota.ad}" loading="lazy">
                    <div class="kart-icerik">
                        <h3>${rota.ad}</h3>
                        <p>${rota.aciklama}</p>
                    </div>
                </a>
            </div>
        `;
    };

    try {
        const allRoutesResponse = await fetch('rotalari_getir.php');
        if (!allRoutesResponse.ok) throw new Error('Tüm rotalar yüklenemedi.');
        tumRotalar = await allRoutesResponse.json();

        const favoritesResponse = await fetch('favori_islemleri.php');
        const data = await favoritesResponse.json();

        if (data.status === 'unauthorized') {
            favoriAciklama.textContent = 'Favorilerinizi görmek için lütfen giriş yapın.';
            favoriListesi.innerHTML = '<div style="text-align:center; grid-column: 1 / -1;"><a href="login.html" class="hero-cta">Giriş Yap</a></div>';
            return;
        }

        if (data.success && data.favoriler.length > 0) {
            favoriListesi.innerHTML = '';
            const favoriRotalar = tumRotalar.filter(rota => data.favoriler.includes(rota.id));
            
            favoriRotalar.forEach(rota => {
                favoriListesi.innerHTML += createRotaKarti(rota);
            });
        } else {
            favoriAciklama.textContent = 'Henüz favorilerinize bir rota eklememişsiniz.';
            favoriListesi.innerHTML = '<div style="text-align:center; grid-column: 1 / -1;"><a href="rotalar.html" class="hero-cta">Rotaları Keşfet</a></div>';
        }

    } catch (error) {
        console.error('Favoriler yüklenirken bir hata oluştu:', error);
        favoriAciklama.textContent = 'Favorileriniz yüklenirken bir hata oluştu.';
    }
});