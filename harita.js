document.addEventListener('DOMContentLoaded', () => {
    const map = L.map('map').setView([39.0, 35.0], 6); // Türkiye'ye ortala
    const markersLayer = L.layerGroup().addTo(map);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(map);

    // YENİ: Yeni filtre elemanlarını seç
    const bolgeFiltre = document.getElementById('bolgeFiltre');
    const tipFiltre = document.getElementById('tipFiltre');
    const fiyatFiltre = document.getElementById('fiyatFiltre');
    const etiketFiltre = document.getElementById('etiketFiltre');
    let debounceTimer;

    const ikonlar = {
        rota: L.icon({ iconUrl: 'https://cdn-icons-png.flaticon.com/512/3448/3448609.png', iconSize: [38, 38] }),
        otel: L.icon({ iconUrl: 'https://cdn-icons-png.flaticon.com/512/148/148842.png', iconSize: [38, 38] }),
        restoran: L.icon({ iconUrl: 'https://cdn-icons-png.flaticon.com/512/857/857681.png', iconSize: [38, 38] })
    };

    const verileriYukleVeGoster = async () => {
        markersLayer.clearLayers(); // Önceki iğneleri temizle
        
        // YENİ: Tüm filtrelerin değerlerini al
        const seciliBolge = bolgeFiltre.value;
        const seciliTip = tipFiltre.value;
        const seciliFiyat = fiyatFiltre.value;
        const yazilanEtiket = etiketFiltre.value.trim();

        try {
            // YENİ: URL'ye yeni parametreleri ekle
            const url = `harita_verilerini_getir.php?bolge=${seciliBolge}&tip=${seciliTip}&fiyat=${seciliFiyat}&etiket=${encodeURIComponent(yazilanEtiket)}`;
            const response = await fetch(url);
            const data = await response.json();

            data.forEach(item => {
                if (item.koordinatlar) {
                    const [lat, lon] = item.koordinatlar.split(',').map(Number);
                    
                    const link = item.tip === 'rota' 
                        ? `rota-detay.html?id=${item.id}` 
                        : `rota-detay.html?id=${item.rota_id}#ne-yenir`;
                    
                    const popupContent = `
                        <b>${item.ad}</b><br>
                        ${item.aciklama.substring(0, 100)}...<br>
                        <a href="${link}" target="_blank">Detayları Gör</a>
                    `;

                    L.marker([lat, lon], { icon: ikonlar[item.tip] || ikonlar['rota'] })
                        .addTo(markersLayer)
                        .bindPopup(popupContent);
                }
            });
        } catch (error) {
            console.error('Harita verileri yüklenirken hata:', error);
        }
    };
    
    // YENİ: Etiket input'u için anlık arama gecikmesi (debounce)
    const debouncedFiltrele = () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(verileriYukleVeGoster, 400); // Kullanıcı yazmayı bıraktıktan 400ms sonra ara
    };

    // YENİ: Tüm filtreler için event listener'lar
    bolgeFiltre.addEventListener('change', verileriYukleVeGoster);
    tipFiltre.addEventListener('change', verileriYukleVeGoster);
    fiyatFiltre.addEventListener('change', verileriYukleVeGoster);
    etiketFiltre.addEventListener('keyup', debouncedFiltrele);

    // Sayfa ilk yüklendiğinde verileri getir
    verileriYukleVeGoster();
});