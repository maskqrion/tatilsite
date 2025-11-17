// Gerekli DOM elementlerini seçme
const navToggle = document.getElementById('navToggle');
const mainNav = document.getElementById('mainNav');
const navOverlay = document.getElementById('navOverlay');
const header = document.getElementById('header');
const scrollTopBtn = document.getElementById('scrollTopBtn');
const progressBar = document.getElementById('progress-bar');
const themeSwitch = document.getElementById('theme-switch');

// YENİ: Bildirim zilini global değişkene ata
let notificationBell = null;

// Mobil menüyü açma/kapatma
const toggleMenu = () => {
    mainNav.classList.toggle('is-active');
    navToggle.classList.toggle('is-active');
    navOverlay.classList.toggle('is-active');
    document.body.classList.toggle('body-no-scroll');
};

if (navToggle && mainNav && navOverlay) {
    navToggle.addEventListener('click', toggleMenu);
    navOverlay.addEventListener('click', toggleMenu);
}

// Kaydırma sırasında başlığı gizleme/gösterme
let lastScrollTop = 0;
window.addEventListener('scroll', () => {
    let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    if (scrollTop > lastScrollTop && scrollTop > 100) {
        header.classList.add('header-hidden');
    } else {
        header.classList.remove('header-hidden');
    }
    lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
}, false);

// Sayfanın üstüne kaydırma butonu
if (scrollTopBtn) {
    window.addEventListener('scroll', () => {
        if (window.pageYOffset > 300) {
            scrollTopBtn.classList.add('show');
        } else {
            scrollTopBtn.classList.remove('show');
        }
    });
}

// İlerleme çubuğu
const updateProgressBar = () => {
    const { scrollTop, scrollHeight, clientHeight } = document.documentElement;
    const scrollPercent = (scrollTop / (scrollHeight - clientHeight)) * 100;
    if (progressBar) {
        progressBar.style.width = `${scrollPercent}%`;
    }
};

if (progressBar) {
    window.addEventListener('scroll', updateProgressBar);
}

// Tema değiştirici (Açık/Koyu Mod)
const applyTheme = (theme) => {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
    if (themeSwitch) {
        // GÜNCELLENDİ: İkonları doğru ayarla
        const iconSvg = theme === 'dark' 
            ? '<svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>'
            : '<svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>';
        themeSwitch.innerHTML = iconSvg + ' Tema Değiştir';
    }
};

if (themeSwitch) {
    themeSwitch.addEventListener('click', () => {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        applyTheme(newTheme);
    });
}

// === GÜNCELLENMİŞ KULLANICI OTURUM FONKSİYONU (BİLDİRİM SİSTEMLİ) ===
const checkUserSession = async () => {
    const userSessionContainer = document.getElementById('user-session');
    if (!userSessionContainer) return;

    try {
        const response = await fetch('check_session.php');
        const session = await response.json();

        // === YENİ EKLENDİ: CSRF Token'ı global hale getir ===
        if (session.csrf_token) {
            window.csrfToken = session.csrf_token;
        }
        // === GÜNCELLEME SONU ===

        if (session.loggedin) {
            const notificationsHTML = await fetchNotifications();

            userSessionContainer.innerHTML = `
                <div class="user-dropdown">
                    <span class="dropdown-trigger">
                        Hoş geldin, ${session.name} &#9662;
                    </span>
                    <div class="dropdown-content">
                        <a href="profilim.html">
                            <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            Profilim
                        </a>
                        <a href="favorilerim.html">
                            <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                            Favorilerim
                        </a>
                        <div class="menu-separator"></div>
                        <a href="logout.php">
                            <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                            Çıkış Yap
                        </a>
                    </div>
                </div>
                ${notificationsHTML} 
            `;
            
            // YENİ EKLENDİ: Bildirim zilini bul ve tıklama olayı ekle
            notificationBell = document.querySelector('.notification-bell');
            if (notificationBell) {
                // Tıklama yerine hover ile açılması daha kullanışlı
                // notificationBell.addEventListener('click', markNotificationsAsRead); 
            }
            // === GÜNCELLEME SONU ===

        } else {
            userSessionContainer.innerHTML = `
                <a href="login.html" class="auth-link">Giriş Yap</a>
                <a href="register.html" class="auth-link">Kayıt Ol</a>
            `;
        }
    } catch (error) {
        console.error('Oturum kontrolü sırasında hata:', error);
        userSessionContainer.innerHTML = `
            <a href="login.html" class="auth-link">Giriş Yap</a>
            <a href="register.html" class="auth-link">Kayıt Ol</a>
        `;
    }
};

const fetchNotifications = async () => {
    try {
        const response = await fetch('bildirim_getir.php');
        const result = await response.json();

        // GÜNCELLENDİ: Hata kontrolü
        if (result.success && Array.isArray(result.bildirimler)) {
            const bildirimler = result.bildirimler;
            const unreadCount = bildirimler.filter(b => b.okundu_mu == 0).length;
            
            const notificationItems = bildirimler.length > 0 ? bildirimler.map(b => {
                let message = '';
                // GÜNCELLENDİ: Olası null değerler için kontrol
                let yorumParcasi = b.yorum_metni ? `"${b.yorum_metni.substring(0, 30)}..."` : 'bir yorumunuzu';

                switch(b.bildirim_tipi) {
                    case 'yorum_begeni':
                        message = `<strong>${b.tetikleyici_kullanici_adi}</strong>, ${yorumParcasi} beğendi.`;
                        break;
                    case 'bahsedilme':
                        message = `<strong>${b.tetikleyici_kullanici_adi}</strong>, sizden bir yorumda bahsetti.`;
                        break;
                    case 'favori':
                         // GÜNCELLENDİ: Olası null değerler için kontrol
                        message = `<strong>${b.tetikleyici_kullanici_adi}</strong>, <strong>${b.rota_adi || 'bir rotayı'}</strong> takip etmeye başladı.`;
                        break;
                    default:
                        message = 'Yeni bir bildiriminiz var.';
                }
                // YENİ EKLENDİ: Okundu olarak işaretlemek için data-id
                return `<li class="notification-item ${b.okundu_mu == 0 ? 'unread' : ''}" data-id="${b.id}">${message}</li>`;
            }).join('') : '<li class="notification-item">Yeni bildiriminiz yok.</li>';

            return `
                <div class="notification-bell">
                    <svg class="menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                    ${unreadCount > 0 ? `<span class="notification-count">${unreadCount}</span>` : ''}
                    <div class="notification-dropdown" onmouseover="markNotificationsAsRead()">
                        <div class="notification-header">Bildirimler</div>
                        <ul class="notification-list">${notificationItems}</ul>
                    </div>
                </div>
            `;
        }
        return ''; // Başarısızsa veya bildirim yoksa boş döndür
    } catch (error) {
        console.error('Bildirimler alınırken hata:', error);
        return '';
    }
};

// YENİ EKLENDİ: Bildirimleri okundu olarak işaretleyen fonksiyon
const markNotificationsAsRead = async () => {
    if (!notificationBell) return;
    
    const countBadge = notificationBell.querySelector('.notification-count');
    if (!countBadge) return; // Okunmamış bildirim yoksa bir şey yapma

    // 1. Görsel olarak sayacı hemen kaldır
    countBadge.style.display = 'none';

    // 2. Arka planda sunucuyu güncelle
    try {
        const formData = new FormData();
        // Global CSRF token'ı ekle
        if (window.csrfToken) {
            formData.append('csrf_token', window.csrfToken);
        }
        
        const response = await fetch('bildirim_okundu_isaretle.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) throw new Error('Sunucu onayı alınamadı.');
        
        // 3. Menüdeki "unread" class'larını kaldır
        document.querySelectorAll('.notification-item.unread').forEach(item => {
            item.classList.remove('unread');
        });

    } catch (error) {
        console.error('Bildirimler okunurken hata:', error);
        // Hata olursa sayacı geri getir (opsiyonel)
        countBadge.style.display = 'flex';
    }
};
// === GÜNCELLEME SONU ===


window.showToast = (message, type = 'success', duration = 3000) => {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('show');
    }, 100);

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 500);
    }, duration);
};

document.addEventListener('DOMContentLoaded', () => {
    checkUserSession();
    const savedTheme = localStorage.getItem('theme') || 'light';
    applyTheme(savedTheme);
    
    const fadeInElements = document.querySelectorAll('.fade-in');
    const scrollObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    fadeInElements.forEach(el => {
        scrollObserver.observe(el);
    });
});