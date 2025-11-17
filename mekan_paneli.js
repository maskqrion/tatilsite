document.addEventListener('DOMContentLoaded', () => {
    // Ana Elementler
    const panelContainer = document.getElementById('panel-container');
    const mekanBilgileriContainer = document.getElementById('mekan-bilgileri-container');
    const mekanDuzenlemeAlani = document.getElementById('mekanDuzenlemeAlani');
    const mekanDuzenleForm = document.getElementById('mekanDuzenleForm');

    // Sekme Elementleri
    const tabs = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');
    
    // Premium İçerik Konteynerları
    const ozelTeklifFormContainer = document.getElementById('ozelTeklifFormContainer');
    const galeriYonetimContainer = document.getElementById('galeriYonetimContainer');
    const rezervasyonListesiContainer = document.getElementById('rezervasyonListesiContainer');

    let currentMekanData = null;
    let csrfToken = null; // YENİ: Bu panelin kendi token'ını saklamak için

    // --- HATA YÖNETİMİ ---
    // YENİ EKLENDİ: Token hatalarını merkezi olarak yönet
    const handleTokenError = (message) => {
        if (message && message.includes('güvenlik anahtarı')) {
            alert('Oturumunuz zaman aşımına uğradı veya geçersiz. Lütfen sayfayı yenileyin.');
            window.location.reload();
        } else {
            alert(message || 'Bilinmeyen bir hata oluştu.');
        }
    };

    // --- RENDER FONKSİYONLARI ---

    const renderOzelTeklifForm = (userRole) => {
        if (userRole !== 'premium_mekan') {
            ozelTeklifFormContainer.innerHTML = `
                <div class="premium-feature-lock">
                    <h3>Bu Özellik Premium Üyelere Özeldir</h3>
                    <p>Müşterilerinize özel teklifler, indirimler veya menüler sunarak bir adım öne çıkın. Premium üyeliğe geçerek bu özelliği aktif edebilirsiniz.</p>
                </div>`;
            return;
        }
        ozelTeklifFormContainer.innerHTML = `
            <form id="ozelTeklifForm" class="contact-form">
                <div class="form-group">
                    <input type="text" id="ozel_teklif_baslik" name="ozel_teklif_baslik" value="${currentMekanData.ozel_teklif_baslik || ''}" placeholder=" ">
                    <label for="ozel_teklif_baslik">Teklif Başlığı (Örn: Hafta İçi %20 İndirim)</label>
                </div>
                <div class="form-group">
                    <textarea id="ozel_teklif_aciklama" name="ozel_teklif_aciklama" placeholder=" " rows="4">${currentMekanData.ozel_teklif_aciklama || ''}</textarea>
                    <label for="ozel_teklif_aciklama">Teklif Detayları</label>
                </div>
                <button type="submit" class="hero-cta">Teklifi Kaydet</button>
            </form>`;
        
        document.getElementById('ozelTeklifForm').addEventListener('submit', handleFormSubmit);
    };

    const renderGaleriYonetimi = (userRole) => {
        if (userRole !== 'premium_mekan') {
            galeriYonetimContainer.innerHTML = `
                <div class="premium-feature-lock">
                    <h3>Bu Özellik Premium Üyelere Özeldir</h3>
                    <p>Mekanınızın en güzel fotoğraflarını yükleyerek potansiyel müşterilerinizi etkileyin. Premium üyeliğe geçerek galeri özelliğini aktif edebilirsiniz.</p>
                </div>`;
            return;
        }
        galeriYonetimContainer.innerHTML = `
            <h4>Mevcut Resimler</h4>
            <div id="mevcutGaleri" class="gallery-container" style="grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));"></div>
            <h4 style="margin-top: 30px;">Yeni Resim Yükle</h4>
            <form id="galeriUploadForm" class="contact-form">
                <div class="form-group">
                    <input type="file" id="galeri_resim" name="galeri_resim" accept="image/*" required>
                </div>
                <button type="submit" class="hero-cta">Yükle</button>
            </form>`;
            
        renderMevcutGaleri();
        document.getElementById('galeriUploadForm').addEventListener('submit', handleGaleriUpload);
    };
    
    const renderMevcutGaleri = () => {
        const mevcutGaleri = document.getElementById('mevcutGaleri');
        if (!mevcutGaleri) return;
        mevcutGaleri.innerHTML = '';
        if (currentMekanData.galeri && currentMekanData.galeri.length > 0) {
            currentMekanData.galeri.forEach(resim => {
                const resimDiv = document.createElement('div');
                resimDiv.className = 'gallery-item';
                resimDiv.style.position = 'relative';
                resimDiv.innerHTML = `
                    <img src="${resim.resim_url}" alt="Galeri resmi">
                    <button class="delete-btn" data-resim-id="${resim.id}" style="position:absolute; top:5px; right:5px; z-index:10;">Sil</button>
                `;
                mevcutGaleri.appendChild(resimDiv);
            });
        } else {
             mevcutGaleri.innerHTML = '<p>Henüz galeriye resim eklenmemiş.</p>';
        }
    };

    const rezervasyonlariYukle = async () => {
        rezervasyonListesiContainer.innerHTML = '<p>Rezervasyon talepleri yükleniyor...</p>';
        try {
            const response = await fetch('mekan_rezervasyonlari_getir.php');
            const result = await response.json();
            if (result.success && result.data.length > 0) {
                rezervasyonListesiContainer.innerHTML = result.data.map(rez => `
                    <div class="reservation-card" data-id="${rez.id}">
                        <h4>${rez.etkinlik_adi}</h4>
                        <p><span class="meta">Kimden:</span> ${rez.ad_soyad} (${rez.email})</p>
                        <p><span class="meta">Kişi Sayısı:</span> ${rez.kisi_sayisi}</p>
                        <p><span class="meta">Talep Tarihi:</span> ${new Date(rez.talep_tarihi).toLocaleDateString('tr-TR')}</p>
                        <p><span class="meta">Durum:</span> <span class="durum-text">${rez.durum}</span></p>
                        ${rez.durum === 'beklemede' ? `
                        <div class="reservation-actions">
                            <button class="hero-cta approve-btn" data-id="${rez.id}" data-action="onayla">Onayla</button>
                            <button class="hero-cta reject-btn" data-id="${rez.id}" data-action="reddet">Reddet</button>
                        </div>
                        ` : ''}
                    </div>
                `).join('');
            } else {
                rezervasyonListesiContainer.innerHTML = '<p>Henüz gelen bir rezervasyon talebi bulunmamaktadır.</p>';
            }
        } catch (error) {
            console.error('Rezervasyonlar yüklenirken hata:', error);
            rezervasyonListesiContainer.innerHTML = '<p>Rezervasyonlar yüklenirken bir hata oluştu.</p>';
        }
    };

    // --- OLAY YÖNETİCİLERİ ---

    const handleFormSubmit = async (event) => {
        event.preventDefault();
        const formData = new FormData(mekanDuzenleForm);
        
        if (event.target.id === 'ozelTeklifForm') {
            const teklifForm = document.getElementById('ozelTeklifForm');
            formData.append('ozel_teklif_baslik', teklifForm.querySelector('#ozel_teklif_baslik').value);
            formData.append('ozel_teklif_aciklama', teklifForm.querySelector('#ozel_teklif_aciklama').value);
        }

        // YENİ EKLENDİ: CSRF Token
        if (!csrfToken) {
            alert('Güvenlik anahtarı yüklenemedi. Lütfen sayfayı yenileyin.');
            return;
        }
        formData.append('csrf_token', csrfToken);

        try {
            const response = await fetch('mekan_guncelle.php', { method: 'POST', body: formData });
            const result = await response.json();
            
            if (result.success) {
                alert(result.message);
                mekanlariYukle();
            } else {
                handleTokenError(result.message); // YENİ EKLENDİ
            }
        } catch (error) {
            console.error('Güncelleme hatası:', error);
            alert('Güncelleme sırasında bir ağ hatası oluştu.');
        }
    };
    
    const handleGaleriUpload = async (event) => {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData();
        formData.append('action', 'upload');
        formData.append('mekan_id', currentMekanData.id);
        formData.append('galeri_resim', form.querySelector('#galeri_resim').files[0]);

        // YENİ EKLENDİ: CSRF Token
        if (!csrfToken) {
            alert('Güvenlik anahtarı yüklenemedi. Lütfen sayfayı yenileyin.');
            return;
        }
        formData.append('csrf_token', csrfToken);

        try {
            const response = await fetch('mekan_galeri_islemleri.php', { method: 'POST', body: formData });
            const result = await response.json();
            if(result.success) {
                currentMekanData.galeri.push({id: result.id, resim_url: result.file_path});
                renderMevcutGaleri();
                form.reset();
            } else {
                handleTokenError(result.message); // YENİ EKLENDİ
            }
        } catch (error) {
            alert('Resim yüklenirken bir hata oluştu.');
        }
    };
    
    const handleReservationAction = async (rezervasyonId, action) => {
        if (!confirm(`Bu rezervasyon talebini "${action === 'onayla' ? 'ONAYLAMAK' : 'REDDETMEK'}" istediğinizden emin misiniz?`)) return;

        const formData = new FormData();
        formData.append('rezervasyon_id', rezervasyonId);
        formData.append('action', action);

        // YENİ EKLENDİ: CSRF Token
        if (!csrfToken) {
            alert('Güvenlik anahtarı yüklenemedi. Lütfen sayfayı yenileyin.');
            return;
        }
        formData.append('csrf_token', csrfToken);

        try {
            const response = await fetch('rezervasyon_yonet.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (result.success) {
                alert(result.message);
                const card = document.querySelector(`.reservation-card[data-id="${rezervasyonId}"]`);
                if(card) {
                    card.querySelector('.reservation-actions').remove();
                    card.querySelector('.durum-text').textContent = (action === 'onayla') ? 'onaylandı' : 'reddedildi';
                }
            } else {
                handleTokenError(result.message); // YENİ EKLENDİ
            }
        } catch (error) {
            alert('Rezervasyon durumu güncellenirken bir ağ hatası oluştu.');
        }
    };
    
    document.addEventListener('click', async (event) => {
        if(event.target.classList.contains('delete-btn') && event.target.dataset.resimId) {
             const resimId = event.target.dataset.resimId;
            if(confirm('Bu resmi kalıcı olarak silmek istediğinizden emin misiniz?')) {
                 const formData = new FormData();
                 formData.append('action', 'delete');
                 formData.append('mekan_id', currentMekanData.id);
                 formData.append('resim_id', resimId);

                 // YENİ EKLENDİ: CSRF Token
                if (!csrfToken) {
                    alert('Güvenlik anahtarı yüklenemedi. Lütfen sayfayı yenileyin.');
                    return;
                }
                formData.append('csrf_token', csrfToken);

                 try {
                     const response = await fetch('mekan_galeri_islemleri.php', { method: 'POST', body: formData });
                     const result = await response.json();
                     if (result.success) {
                         currentMekanData.galeri = currentMekanData.galeri.filter(r => r.id != resimId);
                         renderMevcutGaleri();
                     } else {
                         handleTokenError(result.message); // YENİ EKLENDİ
                     }
                 } catch (error) {
                     alert('Resim silinirken bir hata oluştu.');
                 }
            }
        }
        
        if (event.target.matches('.approve-btn, .reject-btn')) {
            const button = event.target;
            handleReservationAction(button.dataset.id, button.dataset.action);
        }
    });

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(item => item.classList.remove('active'));
            tab.classList.add('active');
            const tabId = tab.dataset.tab;
            tabContents.forEach(content => {
                content.classList.remove('active');
                if (content.id === tabId) {
                    content.classList.add('active');
                }
            });
            if (tabId === 'rezervasyonlar') {
                rezervasyonlariYukle();
            }
        });
    });

    const mekanlariYukle = async () => {
        try {
            // GÜNCELLENDİ: main.js'i yüklemediği için kendi token'ını almalı
            const sessionResponse = await fetch('check_session.php');
            const sessionData = await sessionResponse.json();
            if (sessionData.csrf_token) {
                csrfToken = sessionData.csrf_token;
            } else if (!sessionData.loggedin) {
                 throw new Error('Giriş yapılmamış veya oturum süresi dolmuş.');
            }
            // GÜNCELLEME SONU

            const response = await fetch('mekan_paneli_getir.php');
            const result = await response.json();

            if (response.ok && result.success) {
                currentMekanData = result.data;
                const userRole = result.user_role;

                mekanBilgileriContainer.innerHTML = '';
                mekanDuzenlemeAlani.style.display = 'block';

                mekanDuzenleForm.querySelector('#mekanId').value = currentMekanData.id;
                mekanDuzenleForm.querySelector('#ad').value = currentMekanData.ad;
                mekanDuzenleForm.querySelector('#kategori').value = currentMekanData.kategori;
                mekanDuzenleForm.querySelector('#aciklama').value = currentMekanData.aciklama;
                
                renderOzelTeklifForm(userRole);
                renderGaleriYonetimi(userRole);
                
                mekanDuzenleForm.addEventListener('submit', handleFormSubmit);

            } else {
                panelContainer.innerHTML = `<h1>Erişim Reddedildi</h1><p>${result.message || 'Bu sayfayı görme yetkiniz yok veya yönetilecek bir mekanınız bulunmuyor.'}</p>`;
            }
        } catch (error) {
            console.error('Mekanlar yüklenirken hata:', error);
            panelContainer.innerHTML = `<h1>Hata</h1><p>${error.message}</p><p>Mekanlar yüklenirken bir hata oluştu. Lütfen <a href="login.html">giriş yapmayı</a> deneyin.</p>`;
        }
    };

    mekanlariYukle();
});