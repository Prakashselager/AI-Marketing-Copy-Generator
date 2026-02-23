// Main JavaScript file for AI Marketing Copy Generator

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    initTooltips();
    
    // Handle form submissions
    handleForms();
    
    // Handle copy to clipboard functionality
    initCopyButtons();
});

// Initialize Bootstrap-like tooltips
function initTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(e) {
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = e.target.dataset.tooltip;
    tooltip.style.position = 'absolute';
    
    const rect = e.target.getBoundingClientRect();
    tooltip.style.top = rect.top - 30 + 'px';
    tooltip.style.left = rect.left + 'px';
    
    document.body.appendChild(tooltip);
    e.target._tooltip = tooltip;
}

function hideTooltip(e) {
    if (e.target._tooltip) {
        e.target._tooltip.remove();
        e.target._tooltip = null;
    }
}

// Handle form submissions
function handleForms() {
    // Login form
    const loginForm = document.querySelector('form[action="login.php"]');
    if (loginForm) {
        loginForm.addEventListener('submit', validateLoginForm);
    }
    
    // Registration form
    const registerForm = document.querySelector('form[action="register.php"]');
    if (registerForm) {
        registerForm.addEventListener('submit', validateRegisterForm);
    }
    
    // Generator form
    const generatorForm = document.getElementById('generator-form');
    if (generatorForm) {
        generatorForm.addEventListener('submit', validateGeneratorForm);
    }
}

function validateLoginForm(e) {
    const username = document.getElementById('username')?.value;
    const password = document.getElementById('password')?.value;
    
    if (!username || !password) {
        e.preventDefault();
        showAlert('Please fill in all fields', 'error');
        return false;
    }
    return true;
}

function validateRegisterForm(e) {
    const username = document.getElementById('username')?.value;
    const email = document.getElementById('email')?.value;
    const password = document.getElementById('password')?.value;
    const confirmPassword = document.getElementById('confirm_password')?.value;
    
    if (!username || !email || !password || !confirmPassword) {
        e.preventDefault();
        showAlert('Please fill in all fields', 'error');
        return false;
    }
    
    if (password !== confirmPassword) {
        e.preventDefault();
        showAlert('Passwords do not match', 'error');
        return false;
    }
    
    if (password.length < 6) {
        e.preventDefault();
        showAlert('Password must be at least 6 characters long', 'error');
        return false;
    }
    
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        e.preventDefault();
        showAlert('Please enter a valid email address', 'error');
        return false;
    }
    
    return true;
}

function validateGeneratorForm(e) {
    const platform = document.getElementById('platform')?.value;
    const productName = document.getElementById('product_name')?.value;
    const targetAudience = document.getElementById('target_audience')?.value;
    const keyBenefits = document.getElementById('key_benefits')?.value;
    
    if (!platform || !productName || !targetAudience || !keyBenefits) {
        e.preventDefault();
        showAlert('Please fill in all required fields', 'error');
        return false;
    }
    
    return true;
}

// Show alert messages
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    const container = document.querySelector('.container');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
}

// Copy to clipboard functionality
function initCopyButtons() {
    document.querySelectorAll('.copy-btn').forEach(btn => {
        btn.addEventListener('click', copyToClipboard);
    });
}

function copyToClipboard(text) {
    if (!text) {
        text = this.dataset.copy || this.previousElementSibling?.textContent;
    }
    
    navigator.clipboard.writeText(text).then(() => {
        showAlert('Copied to clipboard!', 'success');
        
        // Change button text temporarily
        const originalText = this.textContent;
        this.textContent = 'Copied!';
        setTimeout(() => {
            this.textContent = originalText;
        }, 2000);
    }).catch(err => {
        showAlert('Failed to copy text', 'error');
    });
}

// AJAX functions
async function fetchWithAuth(url, options = {}) {
    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'same-origin'
    };
    
    const fetchOptions = { ...defaultOptions, ...options };
    
    try {
        const response = await fetch(url, fetchOptions);
        return await response.json();
    } catch (error) {
        console.error('Fetch error:', error);
        showAlert('Network error occurred', 'error');
        return { success: false, error: 'Network error' };
    }
}

// Save copy via AJAX
async function saveCopy(generationId) {
    const result = await fetchWithAuth('api/save_copy.php', {
        method: 'POST',
        body: JSON.stringify({ generation_id: generationId })
    });
    
    if (result.success) {
        showAlert('Copy saved to favorites!', 'success');
    } else {
        showAlert('Error saving copy: ' + result.error, 'error');
    }
}

// Generate copy via AJAX
async function generateCopy(params) {
    const result = await fetchWithAuth('api/generate_copy.php', {
        method: 'POST',
        body: JSON.stringify(params)
    });
    
    if (result.success) {
        return result;
    } else {
        showAlert('Error: ' + result.error, 'error');
        return null;
    }
}

// Update credits display
function updateCreditsDisplay(credits) {
    const creditsBadges = document.querySelectorAll('.credits-badge');
    creditsBadges.forEach(badge => {
        badge.textContent = `Credits: ${credits}`;
    });
}

// Character counter for inputs
function initCharacterCounter(inputId, maxChars) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    const counter = document.createElement('small');
    counter.className = 'character-counter';
    input.parentNode.appendChild(counter);
    
    function updateCounter() {
        const remaining = maxChars - input.value.length;
        counter.textContent = `${remaining} characters remaining`;
        
        if (remaining < 0) {
            counter.style.color = 'var(--danger-color)';
        } else {
            counter.style.color = 'var(--gray-color)';
        }
    }
    
    input.addEventListener('input', updateCounter);
    updateCounter();
}

// Initialize character counters for textareas
document.querySelectorAll('textarea[data-max-chars]').forEach(textarea => {
    const maxChars = parseInt(textarea.dataset.maxChars);
    if (maxChars) {
        initCharacterCounter(textarea.id, maxChars);
    }
});

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        if (href !== '#') {
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }
    });
});

// Mobile menu toggle
function initMobileMenu() {
    const menuButton = document.querySelector('.mobile-menu-button');
    const navMenu = document.querySelector('.nav-menu');
    
    if (menuButton && navMenu) {
        menuButton.addEventListener('click', () => {
            navMenu.classList.toggle('active');
        });
    }
}

// Load more history items (infinite scroll)
let loadingMore = false;
let currentPage = 1;

window.addEventListener('scroll', () => {
    const historyList = document.querySelector('.history-list');
    if (!historyList) return;
    
    const scrollPosition = window.innerHeight + window.scrollY;
    const threshold = document.documentElement.scrollHeight - 1000;
    
    if (scrollPosition >= threshold && !loadingMore) {
        loadingMore = true;
        loadMoreHistory(++currentPage);
    }
});

async function loadMoreHistory(page) {
    const result = await fetchWithAuth(`api/get_history.php?page=${page}`);
    
    if (result.success && result.history.length > 0) {
        appendHistoryItems(result.history);
        loadingMore = false;
    }
}

function appendHistoryItems(items) {
    const historyList = document.querySelector('.history-list');
    if (!historyList) return;
    
    items.forEach(item => {
        const html = createHistoryItemHTML(item);
        historyList.insertAdjacentHTML('beforeend', html);
    });
}

function createHistoryItemHTML(item) {
    return `
        <div class="history-item" data-platform="${item.platform}">
            <div class="history-header">
                <span class="platform-badge">${item.platform}</span>
                <span class="history-date">${new Date(item.created_at).toLocaleDateString()}</span>
            </div>
            <h3>${escapeHtml(item.product_name)}</h3>
            <div class="history-preview">
                ${escapeHtml(item.generated_copy.substring(0, 200))}...
            </div>
            <div class="history-actions">
                <a href="view_copy.php?id=${item.generation_id}" class="btn btn-small">View Full</a>
                <button onclick="copyToClipboard('${escapeHtml(item.generated_copy)}')" class="btn btn-small btn-secondary">Copy</button>
            </div>
        </div>
    `;
}

// Escape HTML to prevent XSS
function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Initialize on page load
initMobileMenu();