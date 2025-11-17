document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchInput');
    const resultsContainer = document.getElementById('results-container');
    let searchTimeout;

    const renderResults = (data) => {
        resultsContainer.innerHTML = ''; // Önceki sonuçları temizle

        const { rotalar, blog } = data;

        if (rotalar.length === 0 && blog.length === 0) {
            resultsContainer.innerHTML = '<p style="text-align:center;">Aramanızla eşleşen sonuç bulunamadı.</p>';
            return;
        }

        let html = '';

        if (rotalar.length > 0) {
            html += `<h2>Rotalar (${rotalar.length})</h2>`;
            rotalar.forEach(rota => {
                html += `
                    <a href="rota-detay.html?id=${rota.id}" class="result-card">
                        <img src="${rota.resim}" alt="${rota.ad}" class="result-image">
                        <div class="result-content">
                            <h3>${rota.ad}</h3>
                            <p>${rota.aciklama.substring(0, 150)}...</p>
                        </div>
                    </a>
                `;
            });
        }

        if (blog.length > 0) {
            html += `<h2>Blog Yazıları (${blog.length})</h2>`;
            blog.forEach(yazi => {
                html += `
                    <a href="blog-detay.html?yazi=${yazi.id}" class="result-card">
                        <img src="${yazi.resim}" alt="${yazi.baslik}" class="result-image">
                        <div class="result-content">
                            <h3>${yazi.baslik}</h3>
                            <p>${yazi.ozet.substring(0, 150)}...</p>
                        </div>
                    </a>
                `;
            });
        }

        resultsContainer.innerHTML = html;
    };

    const performSearch = async () => {
        const searchTerm = searchInput.value.trim();

        if (searchTerm.length < 3) {
            resultsContainer.innerHTML = '<p style="text-align:center;">Lütfen arama yapmak için en az 3 karakter girin.</p>';
            return;
        }

        resultsContainer.innerHTML = '<p style="text-align:center;">Aranıyor...</p>';

        try {
            const response = await fetch(`arama.php?term=${encodeURIComponent(searchTerm)}`);
            const result = await response.json();

            if (result.success) {
                renderResults(result.data);
            } else {
                resultsContainer.innerHTML = `<p style="text-align:center;">${result.message}</p>`;
            }
        } catch (error) {
            console.error('Arama sırasında hata:', error);
            resultsContainer.innerHTML = '<p style="text-align:center;">Arama sırasında bir hata oluştu.</p>';
        }
    };

    searchInput.addEventListener('keyup', () => {
        clearTimeout(searchTimeout);
        // Kullanıcı yazmayı bıraktıktan 500ms sonra aramayı tetikle
        searchTimeout = setTimeout(performSearch, 500);
    });
});