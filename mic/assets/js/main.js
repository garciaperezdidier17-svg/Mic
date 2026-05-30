// assets/js/main.js

// Funciones para modales
function openModal(id) {
    document.getElementById(id).classList.add('active');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

// Toggle dropdown del usuario
function toggleDropdown() {
    const menu = document.getElementById('dropdownMenu');
    if (menu) {
        menu.classList.toggle('show');
    }
}

// Toggle sidebar en móvil
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.classList.toggle('open');
    }
}

// Cerrar modales al hacer clic fuera
document.addEventListener('click', function(e) {
    // Cerrar dropdown
    if (!e.target.closest('.user-menu') && !e.target.closest('.dropdown-menu')) {
        const dropdown = document.getElementById('dropdownMenu');
        if (dropdown) dropdown.classList.remove('show');
    }
    
    // Cerrar modales
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('active');
    }
});

// Mostrar alerta (para mensajes temporales)
function showAlert(message, type) {
    const container = document.getElementById('alertContainer');
    if (!container) return;
    
    const alert = document.createElement('div');
    alert.className = 'alert alert-' + (type || 'success');
    const icon = type === 'success' ? 'fa-check-circle' : 
                 type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
    alert.innerHTML = '<i class="fas ' + icon + '"></i><span>' + message + '</span>';
    container.appendChild(alert);
    
    setTimeout(function() {
        alert.remove();
    }, 3000);
}

// Confirmar eliminación
function confirmarEliminar(url) {
    if (confirm('¿Estás seguro de eliminar este registro? Esta acción no se puede deshacer.')) {
        window.location.href = url;
    }
}

// Formatear moneda
function formatMoney(amount) {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0
    }).format(amount || 0);
}
function toggleDarkMode() {
    document.body.classList.toggle('theme-dark');
    localStorage.setItem('theme', document.body.classList.contains('theme-dark') ? 'dark' : 'light');
}

// Cargar tema guardado
if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('theme-dark');
}
// ========== EFECTO DE PARTÍCULAS FLOTANTES ==========
function createParticles() {
    const particlesContainer = document.createElement('div');
    particlesContainer.className = 'particles';
    document.body.appendChild(particlesContainer);
    
    const particleCount = 30;
    
    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        
        const size = Math.random() * 6 + 2;
        const left = Math.random() * 100;
        const duration = Math.random() * 20 + 10;
        const delay = Math.random() * 10;
        
        particle.style.cssText = `
            width: ${size}px;
            height: ${size}px;
            left: ${left}%;
            animation-duration: ${duration}s;
            animation-delay: -${delay}s;
            opacity: ${Math.random() * 0.3 + 0.1};
        `;
        
        particlesContainer.appendChild(particle);
    }
}

// ========== EFECTO DE ONDA EN BOTONES ==========
function addWaveEffect() {
    const buttons = document.querySelectorAll('.btn-primary, .quick-action-btn');
    
    buttons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const wave = document.createElement('span');
            wave.className = 'wave';
            wave.style.left = x + 'px';
            wave.style.top = y + 'px';
            
            this.appendChild(wave);
            
            setTimeout(() => {
                wave.remove();
            }, 600);
        });
    });
}

// ========== EFECTO DE REVELADO AL HACER SCROLL ==========
function initScrollReveal() {
    const revealElements = document.querySelectorAll('.glass-card, .kpi-card, .user-card');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '0';
                entry.target.style.animation = 'fadeInUp 0.6s ease forwards';
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });
    
    revealElements.forEach(el => {
        el.style.opacity = '0';
        observer.observe(el);
    });
}

// ========== EFECTO DE CARGA ESCALONADA ==========
function initStaggerAnimation() {
    const containers = document.querySelectorAll('.kpi-grid, .users-grid, .charts-grid');
    
    containers.forEach(container => {
        const children = container.children;
        for (let i = 0; i < children.length; i++) {
            children[i].style.animationDelay = (i * 0.1) + 's';
            children[i].classList.add('animate-scale');
        }
    });
}

// ========== EFECTO DE TEXTO TIPEO EN BIENVENIDA ==========
function initTypingEffect() {
    const welcomeTitle = document.querySelector('.welcome-content h1');
    if (welcomeTitle && !welcomeTitle.classList.contains('typing-done')) {
        const originalText = welcomeTitle.innerText;
        welcomeTitle.style.width = '0';
        welcomeTitle.style.overflow = 'hidden';
        welcomeTitle.style.whiteSpace = 'nowrap';
        welcomeTitle.style.borderRight = '2px solid white';
        
        let i = 0;
        function typeWriter() {
            if (i < originalText.length) {
                welcomeTitle.style.width = (i + 1) + 'ch';
                i++;
                setTimeout(typeWriter, 100);
            } else {
                welcomeTitle.style.borderRight = 'none';
                welcomeTitle.classList.add('typing-done');
            }
        }
        
        setTimeout(typeWriter, 500);
    }
}

// ========== INICIALIZAR ANIMACIONES ==========
document.addEventListener('DOMContentLoaded', function() {
    createParticles();
    addWaveEffect();
    initStaggerAnimation();
    
    // Inicializar scroll reveal después de un tiempo
    setTimeout(initScrollReveal, 500);
    
    // Efecto de tipeo en dashboard
    if (document.querySelector('.welcome-content h1')) {
        initTypingEffect();
    }
});

// ========== EFECTO DE PARALLAX AL MOVER MOUSE ==========
function initParallax() {
    const cards = document.querySelectorAll('.kpi-card, .glass-card');
    
    document.addEventListener('mousemove', (e) => {
        const mouseX = e.clientX / window.innerWidth;
        const mouseY = e.clientY / window.innerHeight;
        
        cards.forEach(card => {
            const rect = card.getBoundingClientRect();
            const cardCenterX = rect.left + rect.width / 2;
            const cardCenterY = rect.top + rect.height / 2;
            
            const deltaX = (e.clientX - cardCenterX) / 50;
            const deltaY = (e.clientY - cardCenterY) / 50;
            
            if (card.matches(':hover')) {
                card.style.transform = `perspective(1000px) rotateY(${deltaX}deg) rotateX(${-deltaY}deg) translateY(-5px)`;
            } else {
                card.style.transform = 'perspective(1000px) rotateY(0deg) rotateX(0deg) translateY(0)';
            }
        });
    });
}

// Inicializar parallax solo en desktop
if (window.innerWidth > 768) {
    initParallax();
}

// Generar QR
function verQR(id, codigo) {
    const url = window.location.origin + '/mic/equipo.php?id=' + id;
    const qrContainer = document.getElementById('qrCodeContainer');
    qrContainer.innerHTML = '';
    
    // Usar API de QR code
    const qrImg = document.createElement('img');
    qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(url)}`;
    qrImg.style.width = '200px';
    qrImg.style.height = '200px';
    qrContainer.appendChild(qrImg);
    
    document.getElementById('qrEquipoNombre').textContent = document.querySelector(`tr[data-id="${id}"] .equipo-nombre`).textContent;
    document.getElementById('qrEquipoCodigo').textContent = codigo;
    openModal('qrModal');
}

function imprimirQR() {
    const img = document.querySelector('#qrCodeContainer img');
    const ventana = window.open('');
    ventana.document.write('<img src="' + img.src + '">');
    ventana.print();
}

function descargarQR() {
    const img = document.querySelector('#qrCodeContainer img');
    const link = document.createElement('a');
    link.download = 'qr-equipo.png';
    link.href = img.src;
    link.click();
}
