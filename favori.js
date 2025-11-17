document.addEventListener('DOMContentLoaded', () => {

    let userFavorites = [];
    let isUserLoggedIn = false;

    // 1. Kullanıcının favorilerini ve oturum durumunu sunucudan getiren fonksiyon
    const fetchUserFavorites = async () => {
        try {
            const response = await fetch('favori_islemleri.php');
            if (!response.ok) return;

            const data = await response.json();
            
            if (data.status === 'unauthorized') {
                isUserLoggedIn = false;
                userFavorites = [];
            } else if (data.success) {
                isUserLoggedIn = true;
                userFavorites = data.favoriler || [];
            }
            updateUI();
        } catch (error) {
            // Hata mesajını gizle, konsola yazmak yeterli
            console.error('Favoriler yüklenirken hata:', error);
        }
    };

    // 2. Favori butonlarının görünümünü güncelleyen fonksiyon
    const updateUI = () => {
        const favoriteButtons = document.querySelectorAll('.favorite-btn');
        favoriteButtons.forEach(button => {
            const rotaId = button.dataset.rotaId;
            if (isUserLoggedIn && userFavorites.includes(rotaId)) {
                button.classList.add('favorited');
                button.setAttribute('aria-label', 'Favorilerden kaldır');
            } else {
                button.classList.remove('favorited');
                button.setAttribute('aria-label', 'Favorilere ekle');
            }
        });
    };

    // 3. Bir favori butonuna tıklandığında çalışacak fonksiyon
    const handleFavoriteClick = async (event) => {
        if (!isUserLoggedIn) {
            alert('Favorilere eklemek için lütfen giriş yapın.');
            window.location.href = 'login.html';
            return;
        }

        const button = event.currentTarget;
        const rotaId = button.dataset.rotaId;

        const formData = new FormData();
        formData.append('rota_id', rotaId);

        // === YENİ EKLENDİ: CSRF Token ===
        // main.js'de global hale getirilen token'ı kullan
        if (window.csrfToken) {
            formData.append('csrf_token', window.csrfToken);
        } else {
            alert('Güvenlik anahtarı yüklenemedi. Lütfen sayfayı yenileyin.');
            return;
        }
        // === GÜNCELLEME SONU ===

        try {
            const response = await fetch('favori_islemleri.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                if (result.action === 'added') {
                    userFavorites.push(rotaId);
                    window.showToast('Favorilerinize eklendi!', 'favorite');
                } else if (result.action === 'removed') {
                    userFavorites = userFavorites.filter(id => id !== rotaId);
                    window.showToast('Favorilerden kaldırıldı.', 'favorite');
                }
                updateUI();
            } else {
                 // GÜNCELLENDİ: Token hatası durumunda sayfayı yenile
                if (result.message.includes('güvenlik anahtarı')) {
                    alert('Oturumunuz zaman aşımına uğradı veya geçersiz. Lütfen sayfayı yenileyin.');
                    window.location.reload();
                } else {
                    alert(result.message);
                }
            }
        } catch (error) {
            console.error('Favori işlemi sırasında hata:', error);
            alert('İşlem sırasında bir hata oluştu.');
        }
    };
    
    // 4. Sayfadaki tüm favori butonlarına tıklama olayını ekle
    const initFavoriteButtons = () => {
        const favoriteButtons = document.querySelectorAll('.favorite-btn');
        favoriteButtons.forEach(button => {
            if (!button.dataset.listenerAttached) {
                button.addEventListener('click', handleFavoriteClick);
                button.dataset.listenerAttached = 'true';
            }
        });
    };

    // Dinamik içerik yüklendiğinde (örn: rotalar filtrelendiğinde) butonları yeniden başlat
    document.addEventListener('uiUpdated', () => {
        initFavoriteButtons();
        updateUI();
    });
    
    // Sayfa ilk yüklendiğinde sistemi başlat
    const initialize = async () => {
        await fetchUserFavorites();
        initFavoriteButtons();
    };

    initialize();
});