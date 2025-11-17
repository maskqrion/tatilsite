document.addEventListener('DOMContentLoaded', () => {
    // --- DOM ELEMENTLERİ ---
    const yorumListesi = document.getElementById('yorum-listesi');
    const yorumFormuContainer = document.getElementById('yorum-formu-container');
    const sessionStatusMessage = document.getElementById('session-status-message');
    const anaYorumFormu = document.getElementById('yorumFormu');
    
    const soruListesi = document.getElementById('soru-listesi');
    const soruFormuContainer = document.getElementById('soru-formu-container');
    const soruSessionStatusMessage = document.getElementById('soru-session-status-message');
    const anaSoruFormu = document.getElementById('soruFormu');

    // --- GLOBAL DEĞİŞKENLER ---
    const params = new URLSearchParams(window.location.search);
    const rotaId = params.get('id');
    let currentUserId = null;
    // GÜNCELLENDİ: csrfToken artık main.js'deki window.csrfToken'dan okunacak.
    
    // --- YARDIMCI FONKSİYONLAR ---
    const formatCommentText = (text) => {
        if (typeof text !== 'string') return '';
        return text.replace(/@(\w+)/g, '<span class="mention">@$1</span>');
    };

    const yildizlariOlustur = (puan) => {
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            stars += i <= puan ? '★' : '☆';
        }
        return `<div class="comment-stars">${stars}</div>`;
    };
    
    // --- KART OLUŞTURMA ---
    const kartOlustur = (item, type) => {
        const puanHTML = (type === 'yorum' && !item.parent_id) ? yildizlariOlustur(item.puan) : '';
        const yanitlarContainerId = `yanitlar-${item.id}`;
        const formattedText = formatCommentText(item.yorum_metni);
        
        let typeLabel = '';
        if (type === 'soru') {
            typeLabel = '<span class="comment-type-label question">Soru</span>';
        } else if (item.yorum_tipi === 'cevap') {
            typeLabel = '<span class="comment-type-label answer">Cevap</span>';
        }

        return `
            <div class="comment-card" id="yorum-${item.id}" style="${item.parent_id ? 'margin-left: 40px;' : ''}">
                <div class="comment-header">
                    <a href="kullanici.html?id=${item.user_id}" class="comment-author">${item.kullanici_adi}</a>
                    <div class="comment-header-right">
                        ${typeLabel}
                        ${puanHTML}
                    </div>
                </div>
                <div class="comment-body"><p>${formattedText}</p></div>
                <div class="comment-footer">
                   <small class="comment-date">${item.tarih}</small>
                   <div class="comment-actions">
                       <button class="reply-btn" data-yorum-id="${item.id}">Yanıtla</button>
                       <button class="like-button ${item.kullanici_begendi ? 'liked' : ''}" data-yorum-id="${item.id}">
                           <svg viewBox="0 0 24 24"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path></svg>
                           <span class="like-count">${item.begeni_sayisi}</span>
                       </button>
                   </div>
                </div>
                <div class="reply-form-container" id="yanit-form-container-${item.id}"></div>
                <div class="replies-container" id="${yanitlarContainerId}"></div>
            </div>`;
    };

    // --- RENDER FONKSİYONLARI ---
    const verileriEkranaBas = (items, parentElement, type) => {
        parentElement.innerHTML = '';
        items.forEach(item => {
            const tempContainer = document.createElement('div');
            tempContainer.innerHTML = kartOlustur(item, type);
            const newItemCard = tempContainer.firstElementChild;
            parentElement.appendChild(newItemCard);
            
            if (item.yanitlar && item.yanitlar.length > 0) {
                const yanitlarContainer = newItemCard.querySelector(`#yanitlar-${item.id}`);
                verileriEkranaBas(item.yanitlar, yanitlarContainer, 'yorum');
            }
        });
    };
    
    // --- ASYNC İŞLEMLER ---
    const handleLikeClick = async (yorumId) => {
        if (!currentUserId) {
            alert('Yorumları beğenmek için giriş yapmalısınız.');
            return;
        }
        const likeButton = document.querySelector(`.like-button[data-yorum-id="${yorumId}"]`);
        const likeCountSpan = likeButton.querySelector('.like-count');

        const formData = new FormData();
        formData.append('yorum_id', yorumId);

        // === YENİ EKLENDİ: CSRF Token ===
        if (window.csrfToken) {
            formData.append('csrf_token', window.csrfToken);
        } else {
            alert('Güvenlik anahtarı yüklenemedi. Lütfen sayfayı yenileyin.');
            return;
        }
        // === GÜNCELLEME SONU ===

        try {
            const response = await fetch('yorum_begeni_islemleri.php', { method: 'POST', body: formData });
            const result = await response.json();
            
            if (result.success) {
                likeCountSpan.textContent = result.total_likes;
                likeButton.classList.toggle('liked', result.action === 'liked');
            } else {
                // GÜNCELLENDİ: Token hatası durumunda sayfayı yenile
                if (result.message.includes('güvenlik anahtarı')) {
                    alert('Oturumunuz zaman aşımına uğradı veya geçersiz. Lütfen sayfayı yenileyin.');
                    window.location.reload();
                } else {
                    alert(result.message || 'İşlem sırasında bir hata oluştu.');
                }
            }
        } catch (error) {
            console.error('Beğeni işlemi hatası:', error);
        }
    };

    const verileriGetir = async () => {
        if (!rotaId) return;
        try {
            const response = await fetch(`yorum_getir.php?rota_id=${rotaId}`);
            if (!response.ok) throw new Error(`HTTP hatası! Durum: ${response.status}`);
            const result = await response.json();
             if (!result.success) throw new Error(result.message);
            
            currentUserId = result.current_user_id; // GÜNCELLENDİ: Global ID'yi ayarla
            
            if (yorumListesi) {
                if(result.yorumlar && result.yorumlar.length > 0) {
                     verileriEkranaBas(result.yorumlar, yorumListesi, 'yorum');
                } else {
                    yorumListesi.innerHTML = '<p>Henüz hiç yorum yapılmamış. İlk yorumu siz yapın!</p>';
                }
            }
            if (soruListesi) {
                if(result.sorular && result.sorular.length > 0) {
                    verileriEkranaBas(result.sorular, soruListesi, 'soru');
                } else {
                     soruListesi.innerHTML = '<p>Henüz hiç soru sorulmamış. İlk soruyu siz sorun!</p>';
                }
            }
        } catch (error) {
            console.error('Yorumlar getirilirken kritik bir hata oluştu:', error);
            if(yorumListesi) yorumListesi.innerHTML = '<p>Yorumlar yüklenirken bir sorun oluştu.</p>';
            if(soruListesi) soruListesi.innerHTML = '<p>Sorular yüklenirken bir sorun oluştu.</p>';
        }
    };
    
    const yorumGonder = async (formData, formElement) => {
        // GÜNCELLENDİ: Token'ı globalden al
        if (!window.csrfToken) {
            alert('Güvenlik anahtarı geçersiz. Lütfen sayfayı yenileyin.');
            return;
        }
        formData.append('rota_id', rotaId);
        // GÜNCELLENDİ: Ana formdan değil, yanıt formundan geliyorsa token'ı manuel ekle
        if (!formData.has('csrf_token')) {
            formData.append('csrf_token', window.csrfToken);
        }

        try {
            const response = await fetch('yorum_kaydet.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                if(formElement) formElement.reset();
                // GÜNCELLENDİ: Yanıt formunu kapat
                if(formElement && formElement.classList.contains('reply-form')) {
                    formElement.parentElement.innerHTML = ''; // Yanıt formunu kaldır
                }
                await verileriGetir(); 
            } else {
                 alert(result.message || 'Mesaj gönderilirken bir hata oluştu.');
            }
        } catch (error) {
            alert('Mesaj gönderilirken bir ağ hatası oluştu.');
        }
    };
    
    // --- FORM VE OTURUM YÖNETİMİ ---
    const yanitFormuGoster = (parentYorumId) => {
        document.querySelectorAll('.reply-form-container').forEach(container => container.innerHTML = '');

        const container = document.getElementById(`yanit-form-container-${parentYorumId}`);
        if (!container) return;

        container.innerHTML = `
            <form class="reply-form contact-form" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--color-border);">
                <div class="form-group">
                    <textarea name="yorumMetni" required minlength="10" placeholder=" "></textarea>
                    <label>Yanıtınızı yazın...</label>
                </div>
                <input type="hidden" name="parent_id" value="${parentYorumId}">
                <button type="submit" class="hero-cta">Yanıtı Gönder</button>
            </form>
        `;
        
        container.querySelector('.reply-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            await yorumGonder(new FormData(e.target), e.target);
            // container.innerHTML = ''; // Bu satır yorumGonder içine taşındı
        });
    };

    const oturumDurumunuKontrolEt = async () => {
        try {
            // GÜNCELLENDİ: check_session.php'yi çağırmaya gerek yok, main.js zaten çağırıyor
            // Global token'ın yüklenmesini bekle (eğer henüz yüklenmediyse)
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

            const setupFormVisibility = (form, container, messageElement, loggedOutMessage) => {
                if (!container || !form) return;
                
                // GÜNCELLENDİ: Oturum kontrolünü global `currentUserId` üzerinden yap
                if (currentUserId) { 
                    messageElement.style.display = 'none';
                    form.style.display = 'block';
                    const tokenInput = form.querySelector('input[name="csrf_token"]');
                    if (tokenInput) tokenInput.value = window.csrfToken; // Ana formlardaki token'ı doldur
                } else {
                    messageElement.innerHTML = loggedOutMessage;
                    messageElement.style.display = 'block';
                    form.style.display = 'none';
                }
            };
            
            setupFormVisibility(anaYorumFormu, yorumFormuContainer, sessionStatusMessage, 'Yorum yapmak için <a href="login.html">giriş yapmanız</a> gerekmektedir.');
            setupFormVisibility(anaSoruFormu, soruFormuContainer, soruSessionStatusMessage, 'Soru sormak için <a href="login.html">giriş yapmanız</a> gerekmektedir.');

        } catch (error) {
            console.error('Oturum kontrol hatası:', error);
        }
    };

    // --- EVENT LISTENERS ---
    document.body.addEventListener('click', (event) => {
        const likeButton = event.target.closest('.like-button');
        if (likeButton) {
            handleLikeClick(likeButton.dataset.yorumId);
            return;
        }
        
        const replyButton = event.target.closest('.reply-btn');
        if (replyButton) {
            if (!currentUserId) {
                alert('Yanıt yazmak için giriş yapmalısınız.');
                return;
            }
            yanitFormuGoster(replyButton.dataset.yorumId);
        }
    });

    if (anaYorumFormu) {
        anaYorumFormu.addEventListener('submit', async (event) => {
            event.preventDefault();
            await yorumGonder(new FormData(anaYorumFormu), anaYorumFormu);
        });
    }

    if (anaSoruFormu) {
        anaSoruFormu.addEventListener('submit', async (event) => {
            event.preventDefault();
            await yorumGonder(new FormData(anaSoruFormu), anaSoruFormu);
        });
    }
    
    // --- BAŞLANGIÇ ---
    const initialize = async () => {
        // GÜNCELLENDİ: Sıralama değişti. Önce veriyi getir (currentUserId dolsun),
        // sonra oturumu (formları) kontrol et.
        await verileriGetir();
        await oturumDurumunuKontrolEt();
    };

    initialize();
});