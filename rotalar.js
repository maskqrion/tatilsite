document.addEventListener('DOMContentLoaded', () => {
    const rotalarListesi = document.getElementById('rotalarListesi');
    const searchInput = document.getElementById('searchInput');
    const searchButton = document.getElementById('searchButton');
    const bolgeFiltre = document.getElementById('bolgeFiltre');
    const fiyatFiltre = document.getElementById('fiyatFiltre');
    const siralamaFiltre = document.getElementById('siralamaFiltre');
    const aktiviteButonlari = document.querySelectorAll('#aktiviteButonlari .filter-btn');
    const etiketButonlari = document.querySelectorAll('#etiketButonlari .filter-btn');
    
    let debounceTimer;

    const filtreleVeAra = async () => {
        const aramaTerimi = searchInput.value.toLowerCase().trim();
        const seciliBolge = bolgeFiltre.value;
        const seciliFiyat = fiyatFiltre.value;
        const seciliSiralama = siralamaFiltre.value;
        const aktifAktiviteButon = document.querySelector('#aktiviteButonlari .filter-btn.active').dataset.filter;
        const aktifEtiketButon = document.querySelector('#etiketButonlari .filter-btn.active').dataset.etiket;
        
        rotalarListesi.innerHTML = Array(8).fill(createRotaSkeleton()).join('');
        
        try {
            const url = `rotalari_ara.php?arama=${encodeURIComponent(aramaTerimi)}&bolge=${seciliBolge}&fiyat=${seciliFiyat}&aktivite=${aktifAktiviteButon}&etiket=${encodeURIComponent(aktifEtiketButon)}&siralama=${seciliSiralama}`;
            const response = await fetch(url);
            let filtrelenmisRotalar = await response.json();
            
            rotalariGoster(filtrelenmisRotalar);
        } catch (error) {
            console.error("Arama ve filtreleme sırasında hata oluştu:", error);
            rotalarListesi.innerHTML = '<p style="text-align:center; grid-column: 1 / -1;">Arama sırasında bir hata oluştu. Lütfen tekrar deneyin.</p>';
        }
    };
    
    const createRotaKarti = (rota) => {
        // GÜNCELLEME: Basitleştirilmiş fonksiyon kullanıldı.
        return `
            <div class="destinasyon-karti-wrapper fade-in">
                <button class="favorite-btn" data-rota-id="${rota.id}" aria-label="Favorilere ekle">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                </button>
                <a href="rota-detay.html?id=${rota.id}" class="destinasyon-karti">
                    <img src="${rota.resim || 'https://via.placeholder.com/800x600.png?text=Resim+Bulunamadı'}" alt="${rota.ad}" loading="lazy">
                    <div class="kart-icerik">
                        <h3>${rota.ad}</h3>
                        <p>${rota.aciklama}</p>
                    </div>
                </a>
            </div>`;
    };
    
    const rotalariGoster = (rotalar) => {
        rotalarListesi.innerHTML = '';
        if (rotalar.length === 0) {
            rotalarListesi.innerHTML = '<p style="text-align:center; grid-column: 1 / -1;">Bu kriterlere uygun rota bulunamadı.</p>';
            return;
        }

        const bolgeGruplari = rotalar.reduce((acc, rota) => {
            (acc[rota.bolge] = acc[rota.bolge] || []).push(rota);
            return acc;
        }, {});

        const bolgeAdlari = { 'ege': 'Ege Bölgesi', 'akdeniz': 'Akdeniz Bölgesi', 'marmara': 'Marmara Bölgesi', 'karadeniz': 'Karadeniz Bölgesi', 'ic-anadolu': 'İç Anadolu Bölgesi', 'dogu-anadolu': 'Doğu Anadolu Bölgesi', 'guneydogu-anadolu': 'Güneydoğu Anadolu Bölgesi' };

        for (const bolge in bolgeGruplari) {
            const bolgeBaslik = bolgeAdlari[bolge] || bolge;
            const bolgeSection = document.createElement('section');
            bolgeSection.className = 'bolge-gruplama';
            bolgeSection.innerHTML = `
                <h2 class="section-title bolge-baslik-btn fade-in" data-bolge="${bolge}">${bolgeBaslik} <span class="toggle-icon">▼</span></h2>
                <div class="destinasyon-listesi bolge-listesi" id="list-${bolge}" style="display: none;">
                    ${bolgeGruplari[bolge].map(rota => createRotaKarti(rota)).join('')}
                </div>
            `;
            rotalarListesi.appendChild(bolgeSection);
        }

        const fadeInElements = rotalarListesi.querySelectorAll('.fade-in');
        fadeInElements.forEach((el) => { el.classList.add('visible'); });
        document.dispatchEvent(new Event('uiUpdated'));
    };
    
    const createRotaSkeleton = () => {
        return `<div class="destinasyon-karti-wrapper"><div class="skeleton-card"><div class="skeleton skeleton-image"></div><div class="skeleton skeleton-text"></div><div class="skeleton skeleton-text"></div></div></div>`;
    };

    rotalarListesi.addEventListener('click', (event) => {
        const target = event.target.closest('.bolge-baslik-btn');
        if (target) {
            const bolge = target.dataset.bolge;
            const liste = document.getElementById(`list-${bolge}`);
            target.classList.toggle('active');
            liste.style.display = target.classList.contains('active') ? 'grid' : 'none';
        }
    });

    const initializePage = async () => { await filtreleVeAra(); };
    const debouncedFiltrele = () => { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => { filtreleVeAra(); }, 300); };

    searchInput.addEventListener('keyup', debouncedFiltrele);
    bolgeFiltre.addEventListener('change', filtreleVeAra);
    fiyatFiltre.addEventListener('change', filtreleVeAra);
    siralamaFiltre.addEventListener('change', filtreleVeAra);
    searchButton.addEventListener('click', filtreleVeAra);
    
    aktiviteButonlari.forEach(button => {
        button.addEventListener('click', () => {
            aktiviteButonlari.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            filtreleVeAra();
        });
    });

    etiketButonlari.forEach(button => {
        button.addEventListener('click', () => {
            etiketButonlari.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            filtreleVeAra();
        });
    });
    document.querySelector('#etiketButonlari .filter-btn[data-etiket=""]').classList.add('active');


    initializePage();
});