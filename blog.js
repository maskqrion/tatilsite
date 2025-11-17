document.addEventListener('DOMContentLoaded', () => {
    const blogListesi = document.querySelector('.blog-listesi');
    const kategoriButonlari = document.querySelectorAll('.filter-btn[data-kategori]');
    let tumYazilar = [];

    const createBlogKarti = (yazi) => {
        // GÜNCELLEME: Basitleştirilmiş fonksiyon kullanıldı.
        return `
            <a href="blog-detay.html?yazi=${yazi.id}" class="blog-karti fade-in">
                <div class="blog-karti-resim">
                    <img src="${yazi.resim || 'https://via.placeholder.com/800x600.png?text=Resim+Bulunamadı'}" alt="${yazi.baslik}" loading="lazy">
                </div>
                <div class="blog-karti-icerik">
                    <span class="blog-kategori">${yazi.kategori}</span>
                    <h3>${yazi.baslik}</h3>
                    <p>${yazi.ozet}</p>
                    <span class="blog-tarih">${yazi.tarih}</span>
                </div>
            </a>
        `;
    };

    const createBlogSkeleton = () => {
        return `
            <div class="blog-skeleton-card">
                 <div class="skeleton skeleton-image"></div>
                 <div class="skeleton-content">
                    <div class="skeleton skeleton-text-short"></div>
                    <div class="skeleton skeleton-text"></div>
                    <div class="skeleton skeleton-text"></div>
                    <div class="skeleton skeleton-text"></div>
                 </div>
            </div>
        `;
    };

    const yazilariGoster = (kategori = 'tumu') => {
        blogListesi.innerHTML = '';
        const filtrelenmisYazilar = kategori === 'tumu'
            ? tumYazilar
            : tumYazilar.filter(yazi => yazi.kategori === kategori);

        if (filtrelenmisYazilar.length === 0) {
            blogListesi.innerHTML = '<p>Bu kategoride yazı bulunamadı.</p>';
            return;
        }
        
        filtrelenmisYazilar.forEach(yazi => {
            blogListesi.innerHTML += createBlogKarti(yazi);
        });

        const fadeInElements = blogListesi.querySelectorAll('.fade-in');
        fadeInElements.forEach((el) => {
            el.classList.add('visible');
        });
    };

    const initializeBlog = async () => {
        blogListesi.innerHTML = Array(3).fill(createBlogSkeleton()).join('');
        try {
            const response = await fetch('blog_yazilari_getir.php');
            tumYazilar = await response.json();
            yazilariGoster();
        } catch (error) {
            console.error('Blog yazıları yüklenirken hata:', error);
            blogListesi.innerHTML = '<p>Yazılar yüklenirken bir hata oluştu.</p>';
        }
    };

    kategoriButonlari.forEach(buton => {
        buton.addEventListener('click', () => {
            kategoriButonlari.forEach(btn => btn.classList.remove('active'));
            buton.classList.add('active');
            const seciliKategori = buton.dataset.kategori;
            yazilariGoster(seciliKategori);
        });
    });

    initializeBlog();
});