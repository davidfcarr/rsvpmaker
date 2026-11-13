/**
 * Modal Popup Handler
 * 
 * Manages modal display, timing, and user interactions.
 * Reads configuration from HTML data attributes on the modal element.
 */

function initializeModals() {
  // Find all modals
  const modals = document.querySelectorAll('[data-rsvp-modal]');
  
  modals.forEach(function(modal) {
    const config = {
      modalId: modal.id,
      modalElement: modal,
      showDelay: parseInt(modal.dataset.rsvpModalDelay) || 5000,
      cookieName: modal.dataset.rsvpModalCookie || 'rsvpmail',
      closeSelector: modal.dataset.rsvpModalCloseSelector || '.close'
    };
    
    try {
      initializeModal(config);
    } catch (e) {
      console.error('Modal initialization error:', e);
    }
  });
}

function initializeModal(config) {
  const modal = config.modalElement;
  const closeBtn = modal.querySelector(config.closeSelector);
  
  if (!modal) {
    console.warn('Modal element not found:', config.modalId);
    return;
  }
  
  // Check if force-showing via query parameter
  const forceShow = new URLSearchParams(window.location.search).has('show_rsvp_popup');
  
  // Check if user has already seen this modal (via cookie) - but allow override with show_rsvp_popup
  if (!forceShow && getCookie(config.cookieName)) {
    modal.style.display = 'none';
    return;
  }
  
  // Show modal after delay (skip delay if force-showing)
  const delay = forceShow ? 0 : config.showDelay;
  const timeoutId = setTimeout(() => {
    modal.style.display = 'block';
    setCookie(config.cookieName, '1', 365); // Set cookie for 1 year
  }, delay);
  
  // Store timeout ID for cleanup if needed
  modal.dataset.timeoutId = timeoutId;
  
  // Close button handler
  if (closeBtn) {
    closeBtn.addEventListener('click', function() {
      modal.style.display = 'none';
    });
  }
  
  // Click outside modal to close
  window.addEventListener('click', function(event) {
    if (event.target === modal) {
      modal.style.display = 'none';
    }
  });
}

/**
 * Cookie management functions
 */
function getCookie(name) {
  const nameEQ = name + '=';
  const cookies = document.cookie.split(';');
  
  for (let i = 0; i < cookies.length; i++) {
    let cookie = cookies[i].trim();
    if (cookie.indexOf(nameEQ) === 0) {
      return cookie.substring(nameEQ.length);
    }
  }
  
  return null;
}

function setCookie(name, value, days) {
  let expires = '';
  
  if (days) {
    const date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    expires = '; expires=' + date.toUTCString();
  }
  
  document.cookie = name + '=' + value + expires + '; path=/';
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializeModals);
} else {
  initializeModals();
}
