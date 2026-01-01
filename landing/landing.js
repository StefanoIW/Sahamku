// ===================================
// FEATURE DETAIL TOGGLE
// ===================================
// 
// Google Login
function loginWithGoogle() {
    window.location.href = 'google_oauth.php';
}

function toggleFeatureDetail(event, featureId) {
    event.preventDefault();
    
    const detailDiv = document.getElementById(featureId);
    const link = event.currentTarget;
    
    if (!detailDiv) return;
    
    // Toggle active class
    if (detailDiv.classList.contains('active')) {
        detailDiv.classList.remove('active');
        link.classList.remove('active');
    } else {
        // Close all other details first
        document.querySelectorAll('.feature-detail.active').forEach(el => {
            el.classList.remove('active');
        });
        document.querySelectorAll('.feature-link.active').forEach(el => {
            el.classList.remove('active');
        });
        
        // Open this detail
        detailDiv.classList.add('active');
        link.classList.add('active');
    }
}

// ===================================
// REAL IHSG CHART WITH YAHOO DATA
// ===================================
window.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('heroChart');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    const dpr = window.devicePixelRatio || 1;
    
    // Get data from canvas attributes
    let prices = [];
    let timestamps = [];
    
    try {
        prices = JSON.parse(canvas.getAttribute('data-prices') || '[]');
        timestamps = JSON.parse(canvas.getAttribute('data-timestamps') || '[]');
    } catch (e) {
        console.error('Error parsing chart data:', e);
    }
    
    // Fallback to generated data if no real data
    if (prices.length === 0) {
        let basePrice = 7200;
        for (let i = 0; i < 30; i++) {
            const trend = i < 15 ? 1.001 : 1.0005;
            const volatility = Math.random() * 50 - 25;
            basePrice = basePrice * trend + volatility;
            prices.push(Math.max(7000, Math.min(7500, basePrice)));
        }
    }
    
    // Setup canvas
    canvas.width = canvas.offsetWidth * dpr;
    canvas.height = canvas.offsetHeight * dpr;
    ctx.scale(dpr, dpr);
    
    const width = canvas.offsetWidth;
    const height = canvas.offsetHeight;
    
    function drawChart() {
        ctx.clearRect(0, 0, width, height);
        
        if (prices.length === 0) return;
        
        const max = Math.max(...prices);
        const min = Math.min(...prices);
        const range = max - min;
        const padding = 15;
        
        // Determine if trend is up or down
        const isUptrend = prices[prices.length - 1] > prices[0];
        const primaryColor = isUptrend ? '#10b981' : '#ef4444';
        const gradientColor1 = isUptrend ? 'rgba(16, 185, 129, 0.3)' : 'rgba(239, 68, 68, 0.3)';
        const gradientColor2 = isUptrend ? 'rgba(16, 185, 129, 0)' : 'rgba(239, 68, 68, 0)';
        
        // Draw gradient fill
        const gradient = ctx.createLinearGradient(0, 0, 0, height);
        gradient.addColorStop(0, gradientColor1);
        gradient.addColorStop(1, gradientColor2);
        
        ctx.beginPath();
        prices.forEach((price, i) => {
            const x = (i / (prices.length - 1)) * width;
            const y = height - padding - ((price - min) / range) * (height - padding * 2);
            
            if (i === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        });
        ctx.lineTo(width, height);
        ctx.lineTo(0, height);
        ctx.closePath();
        ctx.fillStyle = gradient;
        ctx.fill();
        
        // Draw line
        ctx.strokeStyle = primaryColor;
        ctx.lineWidth = 2.5;
        ctx.lineJoin = 'round';
        ctx.lineCap = 'round';
        
        ctx.beginPath();
        prices.forEach((price, i) => {
            const x = (i / (prices.length - 1)) * width;
            const y = height - padding - ((price - min) / range) * (height - padding * 2);
            
            if (i === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        });
        ctx.stroke();
        
        // Draw last point
        const lastX = width;
        const lastY = height - padding - ((prices[prices.length - 1] - min) / range) * (height - padding * 2);
        
        // Outer glow
        ctx.beginPath();
        ctx.arc(lastX, lastY, 8, 0, Math.PI * 2);
        ctx.fillStyle = primaryColor + '40';
        ctx.fill();
        
        // Inner point
        ctx.beginPath();
        ctx.arc(lastX, lastY, 4, 0, Math.PI * 2);
        ctx.fillStyle = primaryColor;
        ctx.fill();
        ctx.strokeStyle = 'white';
        ctx.lineWidth = 2;
        ctx.stroke();
    }
    
    drawChart();
    
    // Auto-refresh chart every 60 seconds (simulated real-time update)
    setInterval(function() {
        // In production, fetch new data from server
        // For demo, simulate small price change
        if (prices.length > 30) {
            prices.shift();
        }
        const lastPrice = prices[prices.length - 1];
        const newPrice = lastPrice + (Math.random() - 0.5) * 20;
        prices.push(newPrice);
        drawChart();
    }, 60000);
});

// ===================================
// MODAL FUNCTIONS (Keep existing)
// ===================================
function openModal(type) {
    const modals = {
        'login': 'loginModal',
        'register': 'registerModal',
        'verify': 'verifyModal',
        'forgot': 'forgotModal'
    };
    
    const modalId = modals[type];
    if (modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    }
}

function closeModal(type) {
    const modals = {
        'login': 'loginModal',
        'register': 'registerModal',
        'verify': 'verifyModal',
        'forgot': 'forgotModal'
    };
    
    const modalId = modals[type];
    if (modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }
}

function switchModal(from, to) {
    closeModal(from);
    setTimeout(() => openModal(to), 100);
}

// Close modal on outside click
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.style.display = 'none';
        });
        document.body.style.overflow = 'auto';
    }
});

// ===================================
// NAVIGATION SCROLL EFFECT
// ===================================
let lastScroll = 0;
const navbar = document.querySelector('.navbar');

window.addEventListener('scroll', function() {
    const currentScroll = window.pageYOffset;
    
    if (currentScroll > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
    
    const scrollBtn = document.querySelector('.scroll-to-top');
    if (scrollBtn) {
        if (currentScroll > 300) {
            scrollBtn.classList.add('visible');
        } else {
            scrollBtn.classList.remove('visible');
        }
    }
    
    lastScroll = currentScroll;
});

// Smooth scroll
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});

function scrollToSection(sectionId) {
    const element = document.getElementById(sectionId);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
    }
}

// Mobile menu
function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    if (menu) {
        menu.classList.toggle('active');
    }
}

// ===================================
// VERIFICATION CODE INPUT
// ===================================
document.addEventListener('DOMContentLoaded', function() {
    const codeInputs = document.querySelectorAll('.code-input');
    
    if (codeInputs.length > 0) {
        codeInputs.forEach((input, index) => {
            input.addEventListener('input', function(e) {
                const value = e.target.value;
                
                if (!/^\d$/.test(value)) {
                    e.target.value = '';
                    return;
                }
                
                if (value && index < codeInputs.length - 1) {
                    codeInputs[index + 1].focus();
                }
                
                updateVerificationCode();
            });
            
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    codeInputs[index - 1].focus();
                    codeInputs[index - 1].value = '';
                }
            });
            
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedData = e.clipboardData.getData('text').replace(/\D/g, '');
                
                pastedData.split('').forEach((char, i) => {
                    if (index + i < codeInputs.length) {
                        codeInputs[index + i].value = char;
                    }
                });
                
                const lastIndex = Math.min(index + pastedData.length, codeInputs.length - 1);
                codeInputs[lastIndex].focus();
                
                updateVerificationCode();
            });
        });
    }
});

function updateVerificationCode() {
    const codeInputs = document.querySelectorAll('.code-input');
    const code = Array.from(codeInputs).map(input => input.value).join('');
    const hiddenInput = document.getElementById('verificationCode');
    if (hiddenInput) {
        hiddenInput.value = code;
    }
}

// ===================================
// FORM VALIDATION
// ===================================
document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('registerForm');
    
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const password = this.querySelector('input[name="password"]').value;
            const confirmPassword = this.querySelector('input[name="confirm_password"]').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                showAlert('Password dan Confirm Password tidak sama!', 'error');
                return false;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                showAlert('Password minimal 8 karakter!', 'error');
                return false;
            }
            
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<span class="spinner"></span> Processing...';
            submitBtn.disabled = true;
        });
    }
});

// ===================================
// ALERT NOTIFICATION
// ===================================
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    
    const icon = type === 'error' ? 'fa-exclamation-circle' : 
                 type === 'success' ? 'fa-check-circle' : 'fa-info-circle';
    
    alertDiv.innerHTML = `
        <i class="fas ${icon}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// ===================================
// PRICING TOGGLE
// ===================================
function togglePricing() {
    const toggle = document.getElementById('pricingToggle');
    const monthlyPrices = document.querySelectorAll('.monthly-price');
    const yearlyPrices = document.querySelectorAll('.yearly-price');
    
    if (toggle.checked) {
        monthlyPrices.forEach(el => el.style.display = 'none');
        yearlyPrices.forEach(el => el.style.display = 'inline');
    } else {
        monthlyPrices.forEach(el => el.style.display = 'inline');
        yearlyPrices.forEach(el => el.style.display = 'none');
    }
}

// ===================================
// GOOGLE LOGIN
// ===================================
function loginWithGoogle() {
    window.location.href = 'google_oauth.php';
}

// ===================================
// RESEND CODE
// ===================================
function resendCode() {
    const email = document.getElementById('verifyEmail')?.textContent;
    
    if (!email) {
        showAlert('Email tidak ditemukan', 'error');
        return;
    }
    
    fetch('auth_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=resend_code&email=' + encodeURIComponent(email)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Kode verifikasi baru telah dikirim!', 'success');
        } else {
            showAlert('Gagal: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showAlert('Terjadi kesalahan', 'error');
    });
}

// ===================================
// CONTACT SALES
// ===================================
function contactSales() {
    window.location.href = 'mailto:sales@sahamqu.com?subject=Enterprise%20Plan%20Inquiry';
}

// ===================================
// SCROLL TO TOP
// ===================================
const scrollTopBtn = document.createElement('div');
scrollTopBtn.className = 'scroll-to-top';
scrollTopBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
scrollTopBtn.onclick = () => window.scrollTo({ top: 0, behavior: 'smooth' });
document.body.appendChild(scrollTopBtn);

// ===================================
// INTERSECTION OBSERVER
// ===================================
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

document.addEventListener('DOMContentLoaded', function() {
    const animateElements = document.querySelectorAll('.feature-card, .step-card, .pricing-card');
    animateElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
});

// ===================================
// CONSOLE EASTER EGG
// ===================================
console.log('%c🚀 SahamQu - Smart Trading Platform', 'font-size: 20px; font-weight: bold; color: #6366f1;');
console.log('%cMade with ❤️ by SahamQu Team', 'font-size: 12px; color: #64748b;');
