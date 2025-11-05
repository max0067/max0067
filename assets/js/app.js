/**
 * RSS Legal Watch - JavaScript
 * Interactivité et amélioration UX
 */

document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        if (!alert.classList.contains('alert-permanent')) {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        }
    });

    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('[data-confirm]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm') || 'Êtes-vous sûr ?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // URL validation for RSS feeds
    const urlInput = document.querySelector('input[name="url"]');
    if (urlInput) {
        urlInput.addEventListener('blur', function() {
            const url = this.value.trim();
            if (url && !isValidUrl(url)) {
                this.classList.add('is-invalid');
                showFeedbackMessage(this, 'URL invalide. Format attendu: https://example.com/rss');
            } else {
                this.classList.remove('is-invalid');
                removeFeedbackMessage(this);
            }
        });
    }

    // Search autocomplete/debounce
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            // Optionally, add live search with AJAX here
        });
    }

    // Smooth scroll to top button
    createScrollToTopButton();

    // Highlight search terms in results
    highlightSearchTerms();

    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

/**
 * Validate URL format
 */
function isValidUrl(string) {
    try {
        const url = new URL(string);
        return url.protocol === 'http:' || url.protocol === 'https:';
    } catch (_) {
        return false;
    }
}

/**
 * Show feedback message under input
 */
function showFeedbackMessage(element, message) {
    removeFeedbackMessage(element);

    const feedback = document.createElement('div');
    feedback.className = 'invalid-feedback d-block';
    feedback.textContent = message;
    element.parentNode.appendChild(feedback);
}

/**
 * Remove feedback message
 */
function removeFeedbackMessage(element) {
    const existing = element.parentNode.querySelector('.invalid-feedback');
    if (existing) {
        existing.remove();
    }
}

/**
 * Create scroll to top button
 */
function createScrollToTopButton() {
    const button = document.createElement('button');
    button.innerHTML = '↑';
    button.className = 'btn btn-primary rounded-circle position-fixed bottom-0 end-0 m-4';
    button.style.cssText = 'width: 50px; height: 50px; display: none; z-index: 1000;';
    button.setAttribute('title', 'Retour en haut');

    document.body.appendChild(button);

    // Show/hide on scroll
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            button.style.display = 'block';
        } else {
            button.style.display = 'none';
        }
    });

    // Scroll to top on click
    button.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

/**
 * Highlight search terms in results
 */
function highlightSearchTerms() {
    const urlParams = new URLSearchParams(window.location.search);
    const searchTerm = urlParams.get('search');

    if (searchTerm) {
        const articles = document.querySelectorAll('.article-card');
        articles.forEach(article => {
            const title = article.querySelector('.card-title');
            const description = article.querySelector('.card-text');

            if (title) {
                title.innerHTML = highlightText(title.textContent, searchTerm);
            }
            if (description) {
                description.innerHTML = highlightText(description.textContent, searchTerm);
            }
        });
    }
}

/**
 * Highlight specific text in a string
 */
function highlightText(text, term) {
    const regex = new RegExp(`(${escapeRegex(term)})`, 'gi');
    return text.replace(regex, '<mark class="bg-warning">$1</mark>');
}

/**
 * Escape special characters for regex
 */
function escapeRegex(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

/**
 * Loading indicator for AJAX requests
 */
function showLoading(element) {
    const spinner = document.createElement('div');
    spinner.className = 'spinner-border spinner-border-sm ms-2';
    spinner.setAttribute('role', 'status');
    element.appendChild(spinner);
}

function hideLoading(element) {
    const spinner = element.querySelector('.spinner-border');
    if (spinner) {
        spinner.remove();
    }
}

/**
 * Copy to clipboard utility
 */
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copié dans le presse-papiers', 'success');
    });
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
    toast.style.zIndex = '9999';
    toast.textContent = message;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 3000);
}
