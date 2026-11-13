/**
 * Stripe Checkout Form Handler
 * 
 * Initializes Stripe payment form by reading configuration from HTML data attributes.
 * Handles card input, validation, and payment processing.
 */

function initializeStripeCheckout() {
  // Find all Stripe checkout forms
  const forms = document.querySelectorAll('[data-stripe-checkout]');
  
  if (forms.length === 0 || typeof Stripe === 'undefined') {
    if (typeof Stripe === 'undefined') {
      console.warn('Stripe library not loaded');
    }
    return;
  }
  
  forms.forEach(function(formContainer) {
    const config = {
      publicKey: formContainer.dataset.stripePublicKey,
      clientSecret: formContainer.dataset.stripeClientSecret,
      successUrl: formContainer.dataset.stripeSuccessUrl,
      formId: formContainer.dataset.stripeFormId,
      cardElementId: formContainer.dataset.stripeCardElement || 'card-element',
      resultElementId: formContainer.dataset.stripeResultElement || 'card-result',
      buttonId: formContainer.dataset.stripeButtonId || 'card-button',
      nameInputId: formContainer.dataset.stripeNameInput || 'stripe-checkout-name',
      emailInputId: formContainer.dataset.stripeEmailInput || 'stripe-checkout-email'
    };
    
    // Validate required config
    if (!config.publicKey || !config.clientSecret) {
      console.error('Stripe Checkout: Missing required configuration', config);
      return;
    }
    
    try {
      initializeStripeForm(config);
    } catch (e) {
      console.error('Stripe Checkout initialization error:', e);
    }
  });
}

function initializeStripeForm(config) {
  const stripe = Stripe(config.publicKey);
  const elements = stripe.elements();
  
  const style = {
    base: {
      iconColor: '#111111',
      color: '#111111',
      fontWeight: 400,
      fontFamily: '"Helvetica Neue", Helvetica, Arial, sans-serif',
      fontSize: '16px',
      '::placeholder': {
        color: '#333333'
      },
      '::-ms-clear': {
        backgroundColor: '#fff'
      }
    },
    empty: {
      backgroundColor: '#fff'
    },
    completed: {
      backgroundColor: '#eee'
    }
  };
  
  const card = elements.create('card', { style: style });
  const cardElement = document.getElementById(config.cardElementId);
  
  if (!cardElement) {
    console.error('Stripe card element not found:', config.cardElementId);
    return;
  }
  
  card.mount('#' + config.cardElementId);
  
  const displayError = document.getElementById(config.resultElementId);
  const submitButton = document.getElementById(config.buttonId);
  const form = document.getElementById(config.formId);
  const nameInput = document.getElementById(config.nameInputId);
  const emailInput = document.getElementById(config.emailInputId);
  
  if (!submitButton || !displayError) {
    console.error('Stripe required elements not found');
    return;
  }
  
  // Handle card input errors
  card.addEventListener('change', ({ error }) => {
    if (error) {
      displayError.textContent = error.message;
    } else {
      displayError.textContent = '';
    }
  });
  
  // Handle form submission
  submitButton.addEventListener('click', function(ev) {
    ev.preventDefault();
    
    const name = nameInput ? nameInput.value : '';
    const email = emailInput ? emailInput.value : '';
    
    if (!name || !email) {
      displayError.innerHTML = 'Name and email are both required';
      return;
    }
    
    displayError.innerHTML = 'Please wait...';
    displayError.style.cssText = 'background-color: #fff; padding: 10px;';
    
    stripe.confirmCardPayment(config.clientSecret, {
      payment_method: {
        card: card,
        billing_details: {
          name: name,
          email: email
        }
      }
    }).then(function(result) {
      if (result.error) {
        displayError.innerHTML = result.error.message;
        console.error('Payment error:', result.error.message);
      } else {
        if (result.paymentIntent.status === 'succeeded') {
          submitButton.style.display = 'none';
          if (form) {
            form.style.display = 'none';
          }
          
          displayError.innerHTML = 'Recording payment...';
          
          const formData = new FormData(form);
          
          fetch(config.successUrl, {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            displayError.innerHTML = data.message || 'Payment successful';
            if (data.redirect_url) {
              window.location.href = data.redirect_url;
            }
          })
          .catch(error => {
            console.error('Error recording payment:', error);
            displayError.innerHTML = 'Payment recorded but there was an issue confirming. Please contact support.';
          });
        } else {
          displayError.innerHTML = 'Payment not completed. Status: ' + result.paymentIntent.status;
        }
      }
    });
  });
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializeStripeCheckout);
} else {
  initializeStripeCheckout();
}
