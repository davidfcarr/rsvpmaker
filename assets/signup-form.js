/**
 * Mailing List Signup Form Handler
 * 
 * Handles email signup form submission via Fetch API.
 * Reads form configuration from HTML data attributes.
 */

function initializeSignupForms() {
  // Find all signup forms
  const forms = document.querySelectorAll('[data-signup-form]');
  
  forms.forEach(function(form) {
    const config = {
      formId: form.dataset.signupForm,
      submitUrl: form.dataset.signupUrl,
      messageElementId: form.dataset.signupMessage || 'signup_message'
    };
    
    // Validate config
    if (!config.formId || !config.submitUrl) {
      console.error('Signup form: Missing required configuration', config);
      return;
    }
    
    try {
      initializeSignupForm(form, config);
    } catch (e) {
      console.error('Signup form initialization error:', e);
    }
  });
}

function initializeSignupForm(formElement, config) {
  const messageElement = document.getElementById(config.messageElementId);
  
  if (!messageElement) {
    console.warn('Signup form message element not found:', config.messageElementId);
    return;
  }
  
  formElement.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const payload = new FormData(formElement);
    
    // Clear previous message
    messageElement.innerHTML = '';
    messageElement.style.cssText = '';
    
    fetch(config.submitUrl, {
      method: 'POST',
      body: payload
    })
    .then(res => {
      // Check if response is JSON
      const contentType = res.headers.get('content-type');
      if (contentType && contentType.includes('application/json')) {
        return res.json();
      } else {
        return res.text().then(text => {
          try {
            return JSON.parse(text);
          } catch (e) {
            return { message: text };
          }
        });
      }
    })
    .then(data => {
      if (data.success) {
        messageElement.innerHTML = '<div style="color: green; padding: 10px; background-color: #f0f0f0; border-radius: 3px;">' + 
                                   escapeHtml(data.message || 'Thank you for subscribing!') + 
                                   '</div>';
        // Clear form
        formElement.reset();
      } else {
        messageElement.innerHTML = '<div style="color: red; padding: 10px; background-color: #f0f0f0; border-radius: 3px;">' + 
                                   escapeHtml(data.message || 'An error occurred') + 
                                   '</div>';
      }
    })
    .catch(error => {
      console.error('Signup form error:', error);
      messageElement.innerHTML = '<div style="color: red; padding: 10px; background-color: #f0f0f0; border-radius: 3px;">' +
                                 'Network error. Please try again.' + 
                                 '</div>';
    });
  });
}

/**
 * Escape HTML special characters to prevent XSS
 */
function escapeHtml(text) {
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return text.replace(/[&<>"']/g, m => map[m]);
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializeSignupForms);
} else {
  initializeSignupForms();
}
