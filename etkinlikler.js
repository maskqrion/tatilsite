document.addEventListener('DOMContentLoaded', async () => {
    const etkinlikListesi = document.getElementById('etkinlik-listesi');
    const reservationModal = document.getElementById('reservationModal');
    const modalCloseBtn = document.getElementById('modalCloseBtn');
    const reservationForm = document.getElementById('reservationForm');
    const modalEventName = document.getElementById('modalEventName');
    const modalEventId = document.getElementById('modalEventId');
    const modalNotification = document.getElementById('modal-notification');

    const createEventCard = (etkinlik) => {
        const tarihAraligi = etkinlik.baslangic_tarihi === etkinlik.bitis_tarihi 
            ? etkinlik.baslangic_tarihi_formatli 
            : `${etkinlik.baslangic_tarihi_formatli} - ${etkinlik.bitis_tarihi_formatli}`;

        const rezervasyonButonu = etkinlik.rezervasyon_aktif == 1
            ? `<button class="hero-cta reservation-btn" data-etkinlik-id="${etkinlik.id}" data-etkinlik-adi="${etkinlik.etkinlik_adi}" style="margin-left: 15px; background-color: var(--color-success);">Rezervasyon Yap</button>`
            : '';

        return `
            <div class="event-card fade-in">
                <img src="${etkinlik.resim_url || 'https://via.placeholder.com/300x300.png?text=Etkinlik'}" alt="${etkinlik.etkinlik_adi}" class="event-image" loading="lazy">
                <div class="event-content">
                    <h3>${etkinlik.etkinlik_adi}</h3>
                    <div class="event-meta">
                        <span>📅 <strong>Tarih:</strong> ${tarihAraligi}</span>
                        <span>📍 <strong>Konum:</strong> ${etkinlik.konum}</span>
                    </div>
                    <p>${etkinlik.aciklama}</p>
                    <div style="display: flex; align-items: center; margin-top: 20px;">
                        <a href="rota-detay.html?id=${etkinlik.rota_id}" class="hero-cta">İlişkili Rotayı Gör</a>
                        ${rezervasyonButonu}
                    </div>
                </div>
            </div>
        `;
    };
    
    const openModal = (etkinlikId, etkinlikAdi) => {
        // GÜNCELLENDİ: Giriş kontrolü
        if (!window.csrfToken) {
            alert('Rezervasyon yapmak için lütfen giriş yapın.');
            window.location.href = 'login.html';
            return;
        }
        modalEventName.textContent = etkinlikAdi;
        modalEventId.value = etkinlikId;
        reservationModal.classList.add('show');
    };

    const closeModal = () => {
        reservationModal.classList.remove('show');
        reservationForm.reset();
        modalNotification.style.display = 'none';
        reservationForm.style.display = 'block'; 
        const submitBtn = reservationForm.querySelector('button[type="submit"]');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Rezervasyon Talebi Gönder';
    };

    etkinlikListesi.addEventListener('click', (event) => {
        if (event.target.classList.contains('reservation-btn')) {
            const button = event.target;
            openModal(button.dataset.etkinlikId, button.dataset.etkinlikAdi);
        }
    });

    modalCloseBtn.addEventListener('click', closeModal);
    reservationModal.addEventListener('click', (event) => {
        if (event.target === reservationModal) {
            closeModal();
        }
    });

    // GÜNCELLENMİŞ FORM GÖNDERİMİ
    reservationForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(reservationForm);
        const submitBtn = reservationForm.querySelector('button[type="submit"]');

        // === YENİ EKLENDİ: CSRF Token ===
        if (window.csrfToken) {
            formData.append('csrf_token', window.csrfToken);
        } else {
            alert('Güvenlik anahtarı yüklenemedi. Lütfen sayfayı yenileyin.');
            return;
        }
        // === GÜNCELLEME SONU ===
        
        submitBtn.disabled = true;
        submitBtn.textContent = 'Gönderiliyor...';
        
        try {
            const response = await fetch('rezervasyon_yap.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            modalNotification.textContent = result.message;
            if (result.success) {
                modalNotification.className = 'notification success';
                reservationForm.style.display = 'none';
                setTimeout(closeModal, 4000); // 4 saniye sonra modalı kapat
            } else {
                 modalNotification.className = 'notification error';
                 submitBtn.disabled = false;
                 submitBtn.textContent = 'Rezervasyon Talebi Gönder';
                 // GÜNCELLENDİ: Token hatası
                 if (result.message && result.message.includes('güvenlik anahtarı')) {
                    alert('Oturumunuz zaman aşımına uğradı veya geçersiz. Lütfen sayfayı yenileyin.');
                    window.location.reload();
                 }
            }
        } catch (error) {
            modalNotification.className = 'notification error';
            modalNotification.textContent = 'Ağ hatası oluştu. Lütfen tekrar deneyin.';
            submitBtn.disabled = false;
            submitBtn.textContent = 'Rezervasyon Talebi Gönder';
        } finally {
            modalNotification.style.display = 'block';
        }
    });

    try {
        const response = await fetch('etkinlikler_getir.php');
        if (!response.ok) {
            throw new Error('Sunucu tarafında bir hata oluştu.');
        }
        const result = await response.json();

        // GÜNCELLEME: Gelen verinin bir dizi olup olmadığını kontrol et
        if (Array.isArray(result) && result.length > 0) {
            etkinlikListesi.innerHTML = result.map(createEventCard).join('');
            document.querySelectorAll('.fade-in').forEach(el => el.classList.add('visible'));
        } else {
            etkinlikListesi.innerHTML = '<p style="text-align:center;">Yaklaşan bir etkinlik bulunamadı.</p>';
        }
    } catch (error) {
        console.error('Etkinlikler yüklenirken hata:', error);
        etkinlikListesi.innerHTML = '<p style="text-align:center;">Etkinlikler yüklenirken bir sorun oluştu.</p>';
    }
});