// Bu dosya, lightbox'ın genel işlevselliğini yönetir.
// Rota-detay.js tarafından çağrılır ve kullanılır.

const lightbox = document.getElementById('lightbox');
const lightboxImg = document.getElementById('lightbox-img');
const lightboxClose = document.getElementById('lightbox-close');
const lightboxPrev = document.getElementById('lightbox-prev');
const lightboxNext = document.getElementById('lightbox-next');

let currentIndex = 0;
let images = [];

// Lightbox'ı açan fonksiyon
const openLightbox = (imgArray, index) => {
    images = imgArray;
    currentIndex = index;
    updateImage();
    lightbox.classList.add('show');
};

// Lightbox'ı kapatan fonksiyon
const closeLightbox = () => {
    lightbox.classList.remove('show');
};

// Bir sonraki resmi gösteren fonksiyon
const showNextImage = () => {
    currentIndex = (currentIndex + 1) % images.length;
    updateImage();
};

// Bir önceki resmi gösteren fonksiyon
const showPrevImage = () => {
    currentIndex = (currentIndex - 1 + images.length) % images.length;
    updateImage();
};

// Lightbox'taki resmi güncelleyen fonksiyon
const updateImage = () => {
    lightboxImg.src = images[currentIndex];
};

// Olay dinleyicilerini ekle
lightboxClose.addEventListener('click', closeLightbox);
lightboxNext.addEventListener('click', showNextImage);
lightboxPrev.addEventListener('click', showPrevImage);

lightbox.addEventListener('click', (e) => {
    if (e.target === lightbox) {
        closeLightbox();
    }
});