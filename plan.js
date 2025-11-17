document.addEventListener('DOMContentLoaded', async () => {
    const planContainer = document.getElementById('plan-container');
    const params = new URLSearchParams(window.location.search);
    const token = params.get('token');

    if (!token) {
        planContainer.innerHTML = '<p style="text-align:center;">Geçersiz veya eksik paylaşım linki.</p>';
        return;
    }

    try {
        const response = await fetch(`plan_getir.php?token=${token}`);
        const result = await response.json();

        if (result.success) {
            const plan = result.plan;
            document.title = `${plan.plan_adi} | Seçkin Rotalar`;

            let adimlarHTML = '<ol class="plan-steps-container" style="list-style: none; padding-left: 0; counter-reset: step;">';
            plan.adimlari.forEach(adim => {
                adimlarHTML += `
                    <li class="plan-step">
                        <h4>${adim.referans_id}</h4>
                        ${adim.ozel_not ? `<p>"${adim.ozel_not}"</p>` : ''}
                    </li>
                `;
            });
            adimlarHTML += '</ol>';
            
            planContainer.innerHTML = `
                <header class="plan-header">
                    <h1 class="belde-baslik">${plan.plan_adi}</h1>
                    <p class="plan-meta">
                        <strong>${plan.kullanici_adi}</strong> tarafından, 
                        <a href="rota-detay.html?id=${plan.rota_id}" style="color: var(--color-primary);">${plan.rota_adi}</a> rotası için oluşturuldu.
                        <br>
                        (${plan.olusturulma_tarihi})
                    </p>
                </header>
                ${adimlarHTML}
                <div style="text-align:center; margin-top: 40px;">
                    <a href="rota-detay.html?id=${plan.rota_id}" class="hero-cta">Rotanın Detaylarını İncele</a>
                </div>
            `;

        } else {
            planContainer.innerHTML = `<p style="text-align:center;">${result.message}</p>`;
        }
    } catch (error) {
        console.error('Plan yüklenirken hata oluştu:', error);
        planContainer.innerHTML = '<p style="text-align:center;">Plan yüklenirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.</p>';
    }
});