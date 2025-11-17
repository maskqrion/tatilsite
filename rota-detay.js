document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const rotaId = params.get('rota') || params.get('id');
    
    // --- GLOBAL DEĞİŞKENLER ---
    let map = null;
    let plan = [{ gun: 1, adimlar: [] }];
    let currentUserId = null;
    let currentUserRole = null;
    let csrfToken = null; 
    let currentUserLists = [];
    let itemToAdd = { type: null, id: null };
    let draggedItem = null;

    // --- DOM ELEMENTLERİ ---
    const geziPlaniUyarisi = document.getElementById('gezi-plani-uyarisi');
    const geziPlaniOlusturucu = document.getElementById('gezi-plani-olusturucu');
    const geziPlaniListesi = document.getElementById('gezi-plani-listesi');
    const planiKaydetBtn = document.getElementById('planiKaydetBtn');
    const mekanOneriUyarisi = document.getElementById('mekan-onerisi-uyarisi');
    const mekanOneriFormu = document.getElementById('mekanOneriFormu');
    const addToListModal = document.getElementById('addToListModal');
    const modalCloseBtn = document.getElementById('modalCloseBtn');
    const userListsContainer = document.getElementById('userListsContainer');
    const routeActionsContainer = document.getElementById('route-actions');
    const yeniGunEkleBtn = document.getElementById('yeniGunEkleBtn');

    // --- HATA YÖNETİMİ ---
    const handleTokenError = (message) => {
        if (message && message.includes('güvenlik anahtarı')) {
            alert('Oturumunuz zaman aşımına uğradı veya geçersiz. Lütfen sayfayı yenileyin.');
            window.location.reload();
        } else {
            alert(message || 'Bilinmeyen bir hata oluştu.');
        }
    };

    // === YENİ EKLENDİ: Tekrar kullanılabilir animasyon tetikleyicisi ===
    const tetikleFadeInAnimasyonlarini = () => {
        const fadeInElements = document.querySelectorAll('.fade-in');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        fadeInElements.forEach(el => {
            if (!el.classList.contains('visible')) {
                observer.observe(el);
            }
        });
    };
    // === GÜNCELLEME SONU ===

    // --- YARDIMCI FONKSİYONLAR ---
    const createVideoEmbed = (url) => {
        if (!url) return null;
        let videoId = '';
        try {
            if (url.includes('youtube.com/watch')) {
                videoId = new URL(url).searchParams.get('v');
            } else if (url.includes('youtu.be/')) {
                videoId = new URL(url).pathname.substring(1);
            }
        } catch (e) {
            console.error("Geçersiz video URL'si:", url);
            return null;
        }

        if (videoId) {
            return `<iframe src="https://www.youtube.com/embed/${videoId}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
        }
        return null;
    };

    const initMap = (rotaData) => {
        if (map !== null) { map.remove(); }
        if (rotaData.koordinatlar) {
            const [lat, lon] = rotaData.koordinatlar.split(',').map(Number);
            map = L.map('map').setView([lat, lon], 11);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' }).addTo(map);

            const addMarkers = (mekanList) => {
                if(mekanList && Array.isArray(mekanList)) {
                    mekanList.forEach(mekan => {
                        if(mekan.koordinatlar) {
                            const [mekanLat, mekanLon] = mekan.koordinatlar.split(',').map(Number);
                            const popupContent = `<div style="font-family: 'Inter', sans-serif; max-width: 250px;"><h4 style="font-family: 'Playfair Display', serif; font-size: 18px; margin: 0 0 5px 0; color: #007782;">${mekan.ad}</h4><span style="font-size: 12px; background-color: #e6f7f8; color: #007782; padding: 3px 8px; border-radius: 12px;">${mekan.kategori}</span><p style="font-size: 14px; margin: 10px 0 0 0; line-height: 1.5;">${mekan.aciklama.substring(0, 100)}...</p></div>`;
                            L.marker([mekanLat, mekanLon]).addTo(map).bindPopup(popupContent);
                        }
                    });
                }
            };
            addMarkers(rotaData.neredeKalinir);
            addMarkers(rotaData.neYenir);
        } else {
            document.getElementById('map-section').style.display = 'none';
        }
    };

    const initGallery = (galeriResimleri) => {
        const gallerySection = document.getElementById('gallery-section');
        const galleryContainer = document.getElementById('gallery-container');
        if (galeriResimleri && galeriResimleri.length > 0) {
            gallerySection.style.display = 'block';
            galleryContainer.innerHTML = '';
            galeriResimleri.forEach((imgSrc, index) => {
                const galleryItem = document.createElement('div');
                galleryItem.className = 'gallery-item';
                galleryItem.innerHTML = `<img src="${imgSrc}" alt="Galeri Resmi ${index + 1}" loading="lazy">`;
                galleryItem.addEventListener('click', () => { 
                    if (typeof openLightbox === 'function') {
                        openLightbox(galeriResimleri, index); 
                    }
                });
                galleryContainer.appendChild(galleryItem);
            });
        }
    };

    const initQuickNav = () => {
        const quickNav = document.getElementById('quickNavLinks');
        quickNav.innerHTML = '';
        const sections = [
            { id: 'tanitim', title: 'Genel Bakış' }, { id: 'video-container-wrapper', title: 'Video'}, { id: 'gallery-section', title: 'Galeri' },
            { id: 'community-gallery-section', title: 'Topluluk'}, { id: 'map-section', title: 'Harita' }, { id: 'ipuclari', title: 'İpuçları' },
            { id: 'nerede-kalinir', title: 'Konaklama' }, { id: 'ne-yenir', title: 'Yeme-İçme' },
            { id: 'mekan-onerisi-section', title: 'Öneri Yap' },
            { id: 'gezi-plani', title: 'Gezi Planı' }, { id: 'benzer-rotalar-section', title: 'Benzer Rotalar'},
            { id: 'sorular', title: 'Sorular'}, { id: 'yorumlar', title: 'Yorumlar' }
        ];
        sections.forEach(sec => {
            const targetElement = document.getElementById(sec.id);
            if (targetElement && getComputedStyle(targetElement).display !== 'none') {
                const link = document.createElement('a');
                link.href = `#${sec.id}`;
                link.className = 'quick-nav-link';
                link.textContent = sec.title;
                quickNav.appendChild(link);
            }
        });
        quickNav.addEventListener('click', (e) => {
            if(e.target.tagName === 'A'){
                e.preventDefault();
                const targetId = e.target.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                if (targetElement) {
                    window.scrollTo({ top: targetElement.offsetTop - 120, behavior: 'smooth' });
                }
            }
        });
    };

    const renderPlanListesi = () => {
        geziPlaniListesi.innerHTML = '';
        plan.forEach((gun, gunIndex) => {
            const gunElementi = document.createElement('div');
            gunElementi.className = 'plan-gun';
            gunElementi.dataset.gunIndex = gunIndex;
            gunElementi.innerHTML = `<h3>${gunIndex + 1}. Gün</h3>`;
            
            const adimlarListesi = document.createElement('div');
            adimlarListesi.className = 'plan-gun-adimlar';

            if (gun.adimlar.length === 0) {
                adimlarListesi.innerHTML = '<div class="plan-item-placeholder">Bu güne öğe eklemek için sürükleyin veya butona tıklayın.</div>';
            } else {
                gun.adimlar.forEach((adim, adimIndex) => {
                    adimlarListesi.innerHTML += `<div class="plan-item" draggable="true" data-gun-index="${gunIndex}" data-adim-index="${adimIndex}"><span><strong>${adim.tip === 'mekan' ? 'Mekan' : 'İpucu'}:</strong> ${adim.referans_id}</span><button class="remove-plan-item-btn">×</button></div>`;
                });
            }
            gunElementi.appendChild(adimlarListesi);
            geziPlaniListesi.appendChild(gunElementi);
        });
    };

    const addToPlan = (tip, referans_id) => {
        const sonGunIndex = plan.length - 1;
        if (plan.some(gun => gun.adimlar.some(item => item.tip === tip && item.referans_id === referans_id))) {
             if(typeof window.showToast === 'function') window.showToast('Bu öğe zaten planınızda mevcut.', 'error');
             return;
        }
        plan[sonGunIndex].adimlar.push({ tip, referans_id });
        renderPlanListesi();
        if(typeof window.showToast === 'function') window.showToast('Öğe plana eklendi!', 'success');
    };

    const removeFromPlan = (gunIndex, adimIndex) => {
        plan[gunIndex].adimlar.splice(adimIndex, 1);
        renderPlanListesi();
        if(typeof window.showToast === 'function') window.showToast('Öğe plandan kaldırıldı.', 'favorite');
    };

    const savePlan = async () => {
        const planAdi = document.getElementById('geziPlaniAdi').value;
        if (!planAdi.trim() || plan.every(gun => gun.adimlar.length === 0)) {
            alert('Lütfen planınıza bir isim verin ve en az bir öğe ekleyin.');
            return;
        }
        
        const formData = new FormData();
        formData.append('rota_id', rotaId);
        formData.append('plan_adi', planAdi);
        formData.append('adimlari', JSON.stringify(plan));
        
        if (!csrfToken) {
            alert('Güvenlik anahtarı yüklenemedi. Lütfen sayfayı yenileyin.');
            return;
        }
        formData.append('csrf_token', csrfToken);

        try {
            planiKaydetBtn.textContent = 'Kaydediliyor...';
            planiKaydetBtn.disabled = true;
            const response = await fetch('gezi_plani_kaydet.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                alert(result.message);
                window.location.href = 'profilim.html';
            } else {
                handleTokenError(result.message); 
            }
        } catch (error) {
            alert('Plan kaydedilirken bir ağ hatası oluştu.');
        } finally {
            planiKaydetBtn.textContent = 'Planı Kaydet';
            planiKaydetBtn.disabled = false;
        }
    };

    const benzerRotalariYukle = async (rotaId) => {
        const container = document.getElementById('benzer-rotalar-section');
        const listContainer = document.getElementById('benzerRotalarListesi');
        try {
            const response = await fetch(`benzer_rotalari_getir.php?id=${rotaId}`);
            const result = await response.json();
            
            if(result.success && result.items.length > 0) {
                container.style.display = 'block';
                
                listContainer.innerHTML = result.items.map(item => {
                    if (item.item_type === 'rota') {
                        return `
                        <div class="destinasyon-karti-wrapper fade-in">
                            <a href="rota-detay.html?id=${item.id}" class="destinasyon-karti">
                                <img src="${item.resim || 'https://via.placeholder.com/400x250.png?text=Resim+Yok'}" alt="${item.ad}" loading="lazy">
                                <div class="kart-icerik"><h3>${item.ad}</h3><p>${item.aciklama}</p></div>
                            </a>
                        </div>`;
                    }
                    if (item.item_type === 'blog') {
                        const addToListButton = currentUserId 
                            ? `<button class="hero-cta add-to-list-btn" 
                                        data-item-type="blog" 
                                        data-item-id="${item.id}" 
                                        style="padding: 8px 16px; font-size: 14px; position: absolute; bottom: 20px; right: 20px; z-index: 10;">
                                 Listeye Ekle
                               </button>` 
                            : '';
                        return `
                        <div class="destinasyon-karti-wrapper fade-in" style="position: relative;">
                            <a href="blog-detay.html?yazi=${item.id}" class="destinasyon-karti" style="padding-bottom: 70px;">
                                <img src="${item.resim || 'https://via.placeholder.com/400x250.png?text=Resim+Yok'}" alt="${item.ad}" loading="lazy">
                                <div class="kart-icerik">
                                    <span class="blog-kategori" style="font-size: 12px; padding: 4px 10px;">BLOG YAZISI</span>
                                    <h3>${item.ad}</h3>
                                    <p>${item.aciklama}</p>
                                </div>
                            </a>
                            ${addToListButton}
                        </div>`;
                    }
                    return '';
                }).join('');

                // === YENİ EKLENDİ: Benzer rotalar yüklendikten sonra animasyonu tetikle ===
                tetikleFadeInAnimasyonlarini();
                // === GÜNCELLEME SONU ===
            }
        } catch(error) {
            console.error("Benzer öğeler yüklenirken hata:", error);
        }
    };

    const toplulukGalerisiYukle = async (rotaId) => {
        const section = document.getElementById('community-gallery-section');
        const container = document.getElementById('community-gallery-container');
        const uploadForm = document.getElementById('photoUploadForm');
        const uploadWarning = document.getElementById('photo-upload-auth-warning');
        
        if(currentUserId) {
            uploadWarning.style.display = 'none';
            uploadForm.style.display = 'block';
            document.getElementById('csrfTokenPhoto').value = csrfToken;
        } else {
            uploadWarning.style.display = 'block';
            uploadForm.style.display = 'none';
        }

        try {
            const response = await fetch(`kullanici_fotograflari_getir.php?rota_id=${rotaId}`);
            const result = await response.json();

            if (result.success && result.data.length > 0) {
                section.style.display = 'block';
                container.innerHTML = result.data.map(foto => `
                    <div class="gallery-item community-gallery-item">
                        <img src="${foto.resim_url}" alt="Topluluk fotoğrafı" loading="lazy">
                        <div class="uploader-info">Yükleyen: ${foto.yukleyen_kullanici}</div>
                    </div>
                `).join('');
            } else {
                section.style.display = 'block';
                container.innerHTML = '<p style="text-align:center;">Henüz topluluk tarafından eklenmiş bir fotoğraf yok. İlk fotoğrafı siz ekleyin!</p>';
            }
        } catch (error) {
            console.error('Topluluk galerisi yüklenirken hata:', error);
        }
    };

    const sayfayiDoldur = (rotaData) => {
        document.title = `${rotaData.baslik} Detayları | Seçkin Rotalar`;
        document.getElementById('meta-description').setAttribute('content', rotaData.alt_baslik);
        document.getElementById('rota-baslik').textContent = rotaData.baslik;
        document.getElementById('rota-altbaslik').textContent = rotaData.alt_baslik;
        document.querySelector('#rota-hero .destination-hero-background').style.backgroundImage = `url(${rotaData.resim})`;
        
        const tanitimMetni = document.getElementById('tanitim-metni');
        if (tanitimMetni) tanitimMetni.innerHTML = rotaData.tanitim;
        
        const videoEmbedHTML = createVideoEmbed(rotaData.video_url);
        if (videoEmbedHTML) {
            document.getElementById('video-container-wrapper').style.display = 'block';
            document.getElementById('video-container').innerHTML = videoEmbedHTML;
        }

        if (currentUserId) {
            routeActionsContainer.innerHTML = `<button class="hero-cta add-to-list-btn" data-item-type="rota" data-item-id="${rotaId}">Listeye Ekle</button>`;
        }

        const puanlamaAlani = document.getElementById('rota-puanlama-alani');
        if (rotaData.yorum_sayisi > 0) {
            const puan = parseFloat(rotaData.avg_puan);
            const yildizYuzdesi = (puan / 5) * 100;
            puanlamaAlani.querySelector('.stars-inner').style.width = `${yildizYuzdesi}%`;
            puanlamaAlani.querySelector('.rating-text').textContent = `${puan.toFixed(1)} (${rotaData.yorum_sayisi} yorum)`;
            puanlamaAlani.style.display = 'flex';
        }

        initGallery(rotaData.galeri);
        initMap(rotaData);

        const createListHTML = (list, type) => {
            if (!list || !Array.isArray(list) || list.length === 0) return '<p>Bu kategori için henüz bir öneri bulunmamaktadır.</p>';
            return list.map(item => {
                const referansId = type === 'mekanlar' ? item.ad : item.baslik;
                const addToPlanButton = currentUserId ? `<button class="hero-cta add-to-plan-btn" data-tip="${type === 'mekanlar' ? 'mekan' : 'ipucu'}" data-ref="${referansId}">Plana Ekle</button>` : '';
                const addToListButton = currentUserId && type === 'mekanlar' ? `<button class="hero-cta add-to-list-btn" data-item-type="mekan" data-item-id="${item.id}">Listeye Ekle</button>` : '';
                const bookingButton = item.booking_url ? `<a href="${item.booking_url}" target="_blank" rel="noopener sponsored" class="hero-cta" style="background-color: var(--color-success);">Rezervasyon Yap</a>` : '';
                let claimButton = '';
                if (type === 'mekanlar' && !item.owner_id && (currentUserRole === 'mekan_sahibi' || currentUserRole === 'premium_mekan')) {
                    claimButton = `<button class="hero-cta claim-ownership-btn" data-mekan-id="${item.id}" style="background-color: var(--color-warning);">Sahiplik Talep Et</button>`;
                }
                
                const ozelTeklifHTML = (item.ozel_teklif_baslik) ? `
                    <div class="special-offer">
                        <h5 class="special-offer-title">✨ ${item.ozel_teklif_baslik}</h5>
                        <p>${item.ozel_teklif_aciklama}</p>
                    </div>` : '';


                if (type === 'mekanlar') {
                    const approvedBadge = item.onaylandi == 1 ? '<div class="approved-badge">Onaylı Mekan</div>' : '';
                    return `<div class="venue-card fade-in">
                                <div class="venue-header">
                                    <h3>${item.ad} <span class="venue-category">${item.kategori}</span></h3>
                                    ${approvedBadge}
                                    <div class="venue-header-actions">
                                        ${bookingButton}
                                        ${claimButton}
                                        ${addToListButton}
                                        ${addToPlanButton}
                                    </div>
                                </div>
                                <p>${item.aciklama}</p>
                                ${ozelTeklifHTML} 
                            </div>`;
                }
                if (type === 'ipuclari') {
                    return `<div class="tip-card fade-in" style="display:flex; justify-content:space-between; align-items:start; gap: 15px;">
                                <div><h3>${item.baslik}</h3><p>${item.metin}</p></div>
                                ${addToPlanButton}
                            </div>`;
                }
                return '';
            }).join('');
        };
        
        document.getElementById('ipuclari-listesi').innerHTML = createListHTML(rotaData.ipuclari, 'ipuclari');
        document.getElementById('nerede-kalinir-listesi').innerHTML = createListHTML(rotaData.neredeKalinir, 'mekanlar');
        document.getElementById('ne-yenir-listesi').innerHTML = createListHTML(rotaData.neYenir, 'mekanlar');

        initQuickNav();

        // === GÜNCELLEME: Önceki eklediğimiz IntersectionObserver bloğu, ===
        // === yeni yardımcı fonksiyonumuzla değiştirildi. ===
        tetikleFadeInAnimasyonlarini();
        // === GÜNCELLEME SONU ===
    };
    
    const rotaBulunamadi = () => {
        document.getElementById('rota-baslik').textContent = 'Rota Bulunamadı';
        document.querySelector('.detail-content').innerHTML = '<p style="text-align: center;">Aradığınız rota mevcut değil.</p>';
    };
    
    const initializePage = async () => {
        try {
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
            csrfToken = window.csrfToken; 
            
            const sessionResponse = await fetch('check_session.php');
            const sessionData = await sessionResponse.json();
            
            if (sessionData.loggedin) {
                currentUserId = sessionData.id; 
                currentUserRole = sessionData.rol;
                geziPlaniUyarisi.style.display = 'none';
                geziPlaniOlusturucu.style.display = 'block';
                mekanOneriUyarisi.style.display = 'none';
                mekanOneriFormu.style.display = 'block';
                
                document.getElementById('csrfTokenOneri').value = csrfToken;
                document.getElementById('csrfTokenPhoto').value = csrfToken;
                document.getElementById('csrfTokenSoru').value = csrfToken;
                document.getElementById('csrfTokenYorum').value = csrfToken;
            } else {
                geziPlaniUyarisi.innerHTML = '<p>Kendi gezi planınızı oluşturmak için lütfen <a href="login.html">giriş yapın</a> veya <a href="register.html">kayıt olun</a>.</p>';
                mekanOneriUyarisi.innerHTML = '<p>Bu rotaya bir mekan veya ipucu önermek için lütfen <a href="login.html">giriş yapın</a>.</p>';
            }

            if (!rotaId) { rotaBulunamadi(); return; }

            const response = await fetch(`rota_detay_getir.php?id=${rotaId}`);
            const data = await response.json();
            
            if (data.success) {
                sayfayiDoldur(data.details);
                benzerRotalariYukle(rotaId);
                toplulukGalerisiYukle(rotaId);
            } else {
                rotaBulunamadi();
            }
        } catch (error) { 
            console.error('Sayfa başlatılırken hata:', error); 
            rotaBulunamadi();
        }
        renderPlanListesi();
    };
    
    const handleOwnershipClaim = async (event) => {
        const button = event.target;
        const mekanId = button.dataset.mekanId;
        if (!mekanId) return;

        if (!confirm('Bu mekanın sahipliğini talep etmek istediğinizden emin misiniz? Talebiniz admin onayına gönderilecektir.')) return;

        const formData = new FormData();
        formData.append('mekan_id', mekanId);

        if (!csrfToken) {
            alert('Güvenlik anahtarı yüklenemedi. Lütfen sayfayı yenileyin.');
            return;
        }
        formData.append('csrf_token', csrfToken);

        try {
            button.disabled = true;
            button.textContent = 'Gönderiliyor...';

            const response = await fetch('mekan_talep_et.php', { method: 'POST', body: formData });
            const result = await response.json();
            
            alert(result.message);

            if (result.success) {
                button.textContent = 'Talep Gönderildi';
                button.classList.remove('claim-ownership-btn');
            } else {
                handleTokenError(result.message); 
                button.disabled = false;
                button.textContent = 'Sahiplik Talep Et';
            }

        } catch (error) {
            alert('Talep gönderilirken bir ağ hatası oluştu.');
            button.disabled = false;
            button.textContent = 'Sahiplik Talep Et';
        }
    };
    
    const initEventListeners = () => {
        if(yeniGunEkleBtn) {
            yeniGunEkleBtn.addEventListener('click', () => {
                plan.push({ gun: plan.length + 1, adimlar: [] });
                renderPlanListesi();
            });
        }

        // === SÜRÜKLE-BIRAK ===
        if (geziPlaniListesi) {
            geziPlaniListesi.addEventListener('dragstart', (e) => {
                if (e.target.classList.contains('plan-item')) {
                    const gunIndex = e.target.dataset.gunIndex;
                    const adimIndex = e.target.dataset.adimIndex;
                    draggedItem = plan[gunIndex].adimlar[adimIndex];
                    draggedItem.originalGunIndex = parseInt(gunIndex, 10);
                    draggedItem.originalAdimIndex = parseInt(adimIndex, 10);
                    e.target.classList.add('dragging');
                    e.dataTransfer.setData('text/plain', null);
                    e.dataTransfer.effectAllowed = 'move';
                }
            });
            geziPlaniListesi.addEventListener('dragover', (e) => {
                const dropTarget = e.target.closest('.plan-gun-adimlar');
                if (dropTarget && draggedItem) {
                    e.preventDefault();
                    document.querySelectorAll('.plan-gun-adimlar').forEach(el => el.classList.remove('drag-over'));
                    dropTarget.classList.add('drag-over');
                }
            });
            geziPlaniListesi.addEventListener('dragleave', (e) => {
                const dropTarget = e.target.closest('.plan-gun-adimlar');
                if (dropTarget) {
                    dropTarget.classList.remove('drag-over');
                }
            });
            geziPlaniListesi.addEventListener('dragend', (e) => {
                if (e.target.classList.contains('plan-item')) {
                    e.target.classList.remove('dragging');
                }
                document.querySelectorAll('.plan-gun-adimlar').forEach(el => el.classList.remove('drag-over'));
                draggedItem = null;
            });
            geziPlaniListesi.addEventListener('drop', (e) => {
                const dropTarget = e.target.closest('.plan-gun');
                if (dropTarget && draggedItem) {
                    e.preventDefault();
                    const targetGunIndex = parseInt(dropTarget.dataset.gunIndex, 10);
                    const item = plan[draggedItem.originalGunIndex].adimlar.splice(draggedItem.originalAdimIndex, 1)[0];
                    plan[targetGunIndex].adimlar.push(item);
                    renderPlanListesi();
                }
            });
        }
        // === SÜRÜKLE-BIRAK SONU ===

        document.body.addEventListener('click', function(event) {
            if (event.target.classList.contains('add-to-plan-btn')) {
                addToPlan(event.target.dataset.tip, event.target.dataset.ref);
            }
            if (event.target.classList.contains('remove-plan-item-btn')) {
                const itemElement = event.target.closest('.plan-item');
                removeFromPlan(itemElement.dataset.gunIndex, itemElement.dataset.adimIndex);
            }
            if (event.target.classList.contains('claim-ownership-btn')) {
                handleOwnershipClaim(event);
            }
        });
        
        if (planiKaydetBtn) {
            planiKaydetBtn.addEventListener('click', savePlan);
        }

        const photoUploadForm = document.getElementById('photoUploadForm');
        if(photoUploadForm) {
            photoUploadForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(photoUploadForm);
                formData.append('rota_id', rotaId);
                
                const submitBtn = photoUploadForm.querySelector('button[type="submit"]');
                const notification = document.getElementById('upload-notification');
                submitBtn.disabled = true;
                submitBtn.textContent = 'Yükleniyor...';

                try {
                    const response = await fetch('kullanici_fotograf_yukle.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    
                    notification.textContent = result.message;
                    notification.className = `notification ${result.success ? 'success' : 'error'}`;
                    notification.style.display = 'block';

                    if(result.success) {
                        photoUploadForm.reset();
                    } else {
                        handleTokenError(result.message); 
                    }
                } catch(error) {
                    notification.textContent = 'Yükleme sırasında bir ağ hatası oluştu.';
                    notification.className = 'notification error';
                    notification.style.display = 'block';
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Fotoğrafı Gönder';
                }
            });
        }
    };

    initializePage();
    initEventListeners();
});