document.addEventListener('DOMContentLoaded', async () => {
    // DOM Elementleri
    const karsilamaMesaji = document.getElementById('karsilama-mesaji');
    const profilIcerigi = document.getElementById('profil-icerigi');
    const girisUyarisi = document.getElementById('giris-uyarisi');
    
    // Form ve Input Alanları
    const profileUpdateForm = document.getElementById('profileUpdateForm');
    const profileImageInput = document.getElementById('profileImageInput');
    const profileImagePreview = document.getElementById('profileImagePreview');
    const nameInput = document.getElementById('name');
    const hakkimdaInput = document.getElementById('hakkimda');
    const yeniListeForm = document.getElementById('yeniListeForm');

    // İçerik Görüntüleme Alanları
    const aktivitePaneli = document.getElementById('aktivite-paneli');
    const yorumGecmisi = document.getElementById('yorum-gecmisi');
    const geziPlanlariListesi = document.getElementById('gezi-planlari');
    const kullaniciListeleri = document.getElementById('kullanici-listeleri');
    const hakkimdaDisplay = document.getElementById('hakkimda-display');
    const rozetlerContainer = document.getElementById('rozetler-container');

    const ROZET_IKONLARI = {
        'Kaşif': '🗺️',
        'Gezgin': '✈️',
        'Planlamacı': '📝'
    };
    
    const renderRozetler = (rozetler) => {
        if (!rozetlerContainer || !rozetler) return;
        rozetlerContainer.innerHTML = rozetler.length > 0 ? rozetler.map(rozet => `
            <div class="badge" title="${rozet.aciklama}">
                <span class="badge-icon">${ROZET_IKONLARI[rozet.ad] || '⭐'}</span>
                <span class="badge-name">${rozet.ad}</span>
            </div>
        `).join('') : '<p>Henüz hiç rozet kazanmadınız.</p>';
    };

    const renderKullaniciListeleri = (listeler) => {
        if (!kullaniciListeleri || !listeler) return;
        kullaniciListeleri.innerHTML = listeler.length > 0
            ? listeler.map(liste => `
                <div class="list-card">
                    <h4>${liste.liste_adi}</h4>
                    <p>${liste.aciklama || 'Açıklama yok.'}</p>
                </div>`).join('')
            : '<p>Henüz herkese açık bir listeniz bulunmuyor.</p>';
    };
    
    profileImageInput.addEventListener('change', () => {
        const file = profileImageInput.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => { profileImagePreview.src = e.target.result; };
            reader.readAsDataURL(file);
        }
    });

    profileUpdateForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(profileUpdateForm);
        const submitBtn = profileUpdateForm.querySelector('button[type="submit"]');

        // === YENİ EKLENDİ: CSRF Token ===
        if (window.csrfToken) {
            formData.append('csrf_token', window.csrfToken);
        } else {
            alert('Güvenlik anahtarı yüklenemedi. Lütfen sayfayı yenileyin.');
            return;
        }
        // === GÜNCELLEME SONU ===

        try {
            submitBtn.textContent = 'Güncelleniyor...';
            submitBtn.disabled = true;
            const response = await fetch('profil_guncelle.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (result.success) {
                alert(result.message || 'Profiliniz başarıyla güncellendi.');
                if (result.new_image_url) {
                    profileImagePreview.src = result.new_image_url + '?t=' + new Date().getTime();
                }
                karsilamaMesaji.textContent = `Hoş Geldin, ${nameInput.value}!`;
                hakkimdaDisplay.textContent = hakkimdaInput.value.trim() ? hakkimdaInput.value : 'Henüz kendiniz hakkında bir şey yazmamışsınız.';
            } else {
                // GÜNCELLENDİ: Token hatası kontrolü
                if (result.message && result.message.includes('güvenlik anahtarı')) {
                    alert('Oturumunuz zaman aşımına uğradı veya geçersiz. Lütfen sayfayı yenileyin.');
                    window.location.reload();
                } else {
                    alert(result.message || 'Güncelleme sırasında bir hata oluştu.');
                }
            }
        } catch (error) {
            console.error('Profil güncelleme hatası:', error);
            alert('Güncelleme sırasında bir ağ hatası oluştu.');
        } finally {
            submitBtn.textContent = 'Bilgileri Güncelle';
            submitBtn.disabled = false;
        }
    });
    
    yeniListeForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(yeniListeForm);
        const submitBtn = yeniListeForm.querySelector('button[type="submit"]');

        // === YENİ EKLENDİ: CSRF Token ===
        if (window.csrfToken) {
            formData.append('csrf_token', window.csrfToken);
        } else {
            alert('Güvenlik anahtarı yüklenemedi. Lütfen sayfayı yenileyin.');
            return;
        }
        // === GÜNCELLEME SONU ===

        try {
            submitBtn.disabled = true;
            const response = await fetch('liste_olustur.php', { method: 'POST', body: formData });
            const result = await response.json();
            alert(result.message);
            if (result.success) {
                yeniListeForm.reset();
                // Sayfayı yeniden yüklemek yerine yeni veriyi çekip render etmek daha verimli
                const profileResponse = await fetch('profil_getir.php');
                const profileResult = await profileResponse.json();
                if (profileResult.success) {
                     renderKullaniciListeleri(profileResult.data.listeler);
                }
            } else {
                // GÜNCELLENDİ: Token hatası kontrolü
                if (result.message && result.message.includes('güvenlik anahtarı')) {
                    alert('Oturumunuz zaman aşımına uğradı veya geçersiz. Lütfen sayfayı yenileyin.');
                    window.location.reload();
                }
            }
        } catch (error) {
            alert('Liste oluşturulurken bir ağ hatası oluştu.');
        } finally {
            submitBtn.disabled = false;
        }
    });

    try {
        // GÜNCELLENDİ: main.js'in token'ı almasını bekle
        if (!window.csrfToken) {
            await new Promise(resolve => {
                const check = () => {
                    if (window.csrfToken) {
                        resolve();
                    } else {
                        setTimeout(check, 100);
                    }
                };
                check();
            });
        }
        
        const response = await fetch('profil_getir.php');
        if (!response.ok) {
            throw new Error('Giriş yapılmamış veya yetki sorunu.');
        }
        
        const result = await response.json();

        if (result.success) {
            profilIcerigi.style.display = 'grid';
            // GÜNCELLENDİ: planlar eklendi
            const { user, stats, yorumlar, rozetler, planlar, listeler } = result.data;
            
            karsilamaMesaji.textContent = `Hoş Geldin, ${user.name}!`;
            nameInput.value = user.name;
            profileImagePreview.src = user.profile_image || 'assets/avatars/default.png';
            hakkimdaInput.value = user.hakkimda;
            hakkimdaDisplay.textContent = user.hakkimda && user.hakkimda.trim() ? user.hakkimda : 'Henüz kendiniz hakkında bir şey yazmamışsınız.';

            if (stats && aktivitePaneli) {
                // GÜNCELLENDİ: Puan göstergesi eklendi
                aktivitePaneli.innerHTML = `
                    <div class="activity-stats">
                        <div class="stat-item">
                            <div class="stat-number">${stats.toplam_puan || 0}</div>
                            <div class="stat-label">Toplam Puan</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">${stats.toplam_yorum || 0}</div>
                            <div class="stat-label">Yorum</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">${stats.toplam_plan || 0}</div>
                            <div class="stat-label">Gezi Planı</div>
                        </div>
                    </div>`;
            }

            yorumGecmisi.innerHTML = (yorumlar && yorumlar.length > 0)
                ? yorumlar.map(yorum => `
                    <div class="comment-history-item">
                        <div class="comment-history-meta">
                            <a href="rota-detay.html?id=${yorum.rota_id}">${yorum.rota_adi}</a> rotasına, ${yorum.tarih} tarihinde yaptığınız yorum:
                        </div>
                        <p>"${yorum.yorum_metni}"</p>
                    </div>`).join('')
                : '<p>Henüz hiç yorum yapmamışsınız.</p>';
            
            // GÜNCELLENDİ: Gezi planları da render ediliyor
            geziPlanlariListesi.innerHTML = (planlar && planlar.length > 0)
                ? planlar.map(plan => `<div class="plan-card"><h4>${plan.plan_adi}</h4></div>`).join('')
                : '<p>Henüz gezi planınız bulunmuyor.</p>';

            renderRozetler(rozetler);
            renderKullaniciListeleri(listeler);

        } else {
             throw new Error(result.message || 'Profil verileri alınamadı.');
        }
    } catch (error) {
        karsilamaMesaji.textContent = 'Erişim Reddedildi';
        girisUyarisi.innerHTML = `<p>${error.message}</p><a href="login.html" class="hero-cta" style="margin-top:20px;">Giriş Yap</a>`;
        profilIcerigi.style.display = 'none';
    }
});