document.addEventListener('DOMContentLoaded', async () => {
    const heroSection = document.getElementById('blog-hero');
    const blogBaslik = document.getElementById('blog-baslik');
    const blogKategori = document.getElementById('blog-kategori');
    const blogTarih = document.getElementById('blog-tarih');
    const blogIcerik = document.getElementById('blog-icerik');
    const metaDescription = document.getElementById('meta-description');

    const sayfayiDoldur = (yaziData) => {
        document.title = `${yaziData.baslik} | Seçkin Rotalar`;
        metaDescription.setAttribute('content', yaziData.ozet);
        heroSection.style.backgroundImage = `url(${yaziData.resim})`;
        blogBaslik.textContent = yaziData.baslik;
        blogKategori.textContent = yaziData.kategori;
        blogTarih.textContent = yaziData.tarih;
        blogIcerik.innerHTML = yaziData.icerik;
    };

    const yaziBulunamadi = () => {
        blogBaslik.textContent = 'Yazı Bulunamadı';
        blogIcerik.innerHTML = `
            <p style="text-align: center;">Aradığınız yazı mevcut değil veya kaldırılmış olabilir.</p>
            <div style="text-align: center; margin-top: 30px;">
                <a href="blog.html" class="hero-cta">Tüm Yazılara Göz At</a>
            </div>
        `;
    };

    const params = new URLSearchParams(window.location.search);
    const yaziId = params.get('yazi');

    if (yaziId) {
        try {
            const response = await fetch(`blog_detay_getir.php?id=${yaziId}`);
            const result = await response.json();
            if (result.success) {
                sayfayiDoldur(result.data);
            } else {
                yaziBulunamadi();
            }
        } catch (error) {
            console.error('Blog detayı yüklenirken hata:', error);
            yaziBulunamadi();
        }
    } else {
        yaziBulunamadi();
    }
});