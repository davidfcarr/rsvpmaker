/**
 * PayPal Button Initialization
 * 
 * Finds all PayPal button containers on the page and initializes them
 * with configuration passed via HTML data attributes.
 */

function initializePayPalButtons() {
  if (typeof paypal === 'undefined') {
    // SDK not loaded yet, try again in 100ms
    setTimeout(initializePayPalButtons, 100);
    return;
  }

  // Find all PayPal button containers
  const containers = document.querySelectorAll('[data-paypal-button]');
  
  containers.forEach(function(container) {
    const config = {
      verifyUrl: container.dataset.verifyUrl,
      txCode: container.dataset.txCode,
      userId: container.dataset.userId,
      description: container.dataset.description,
      amount: container.dataset.amount,
      currency: container.dataset.currency,
      rsvpId: container.dataset.rsvpId,
      invoiceId: container.dataset.invoiceId || ''
    };

    // Validate required config
    if (!config.verifyUrl || !config.amount || !config.currency) {
      console.error('PayPal Button: Missing required configuration', config);
      return;
    }

    var purchase = {
      custom_id: config.rsvpId,
      description: config.description,
      amount: {
        value: config.amount,
        currency_code: config.currency
      }
    };
    
    if (config.invoiceId) {
      purchase.invoice_id = config.invoiceId;
    }
    
    try {
      paypal.Buttons({
        createOrder: function(data, actions) {
          return actions.order.create({
            purchase_units: [purchase]
          });
        },
        onApprove: function(data, actions) {
          return actions.order.capture().then(function(details) {
            details.rsvp_tx = config.txCode;
            details.user_id = config.userId;
            result = 'Recording transaction by ' + details.payer.name.given_name + '... ';
            container.innerHTML = result;
            
            function sendPaymentWithRetry(paymentData, retryCount = 0, maxRetries = 3) {
              const delay = Math.pow(2, retryCount) * 1000;
              
              return new Promise((resolve) => {
                setTimeout(() => {
                  fetch(config.verifyUrl, {
                    method: 'post',
                    headers: {
                      'content-type': 'application/json'
                    },
                    body: JSON.stringify(paymentData)
                  })
                  .then(function(response) {
                    console.log('PayPal verification response status:', response.status);
                    return response.json().then(data => ({status: response.status, body: data}));
                  })
                  .then(function(result) {
                    if (result.status >= 200 && result.status < 300) {
                      console.log('PayPal payment verified successfully:', result.body);
                      resolve(result.body);
                    } else {
                      if (retryCount < maxRetries && result.status >= 500) {
                        console.log('Server error, retrying... (attempt ' + (retryCount + 1) + ' of ' + maxRetries + ')');
                        resolve(sendPaymentWithRetry(paymentData, retryCount + 1, maxRetries));
                      } else {
                        console.error('Payment verification failed:', result.body);
                        resolve(result.body);
                      }
                    }
                  })
                  .catch(function(error) {
                    console.error('Fetch error:', error);
                    if (retryCount < maxRetries) {
                      console.log('Network error, retrying... (attempt ' + (retryCount + 1) + ' of ' + maxRetries + ')');
                      resolve(sendPaymentWithRetry(paymentData, retryCount + 1, maxRetries));
                    } else {
                      resolve({
                        status: 'Network error after ' + maxRetries + ' retries. Your payment was captured by PayPal. Please contact support with Transaction ID: ' + paymentData.id
                      });
                    }
                  });
                }, delay);
              });
            }
            
            return sendPaymentWithRetry(details).then(function(myJson) {
              if (myJson.status) {
                result = '<p>' + myJson.status + '</p>';
                if (myJson.gift_certificate) {
                  console.log('gift certificate');
                  console.log(myJson);
                  result += '<h1>' + myJson.gift_certificate + '</h1><p>Give this code to the recipient of your gift. To redeem, enter into the coupon field of the registration form for any event on this site.</p>';
                }
                if (myJson.receipt_link) {
                  result += '<p><a href="' + myJson.receipt_link + '">Print Receipt</a></p>';
                }
              } else {
                result = 'Transaction error';
              }
              
              const confirmblock = document.getElementById("rsvpconfirm");
              if (confirmblock && confirmblock.innerHTML) {
                confirmblock.innerHTML = '<div class="rsvpmakerpaypalresult"><p><strong>PayPal Result</strong></p>' + result + '</div>';
              } else {
                container.innerHTML = '<div class="rsvpmakerpaypalresult"><p><strong>PayPal Result</strong></p>' + result + '</div>';
              }
            });
          });
        },
        onError: function (err) {
          console.error('PayPal error:', err);
          container.innerHTML = '<div style="color: red;">Error connecting to PayPal service. Please try again.</div>';
        }
      }).render(container);
      
      // Set 30 minute timeout for payment
      let interval = 30 * 60000;
      setTimeout(() => {
        container.innerHTML = '<h1>Time to pay expired</h1>';
      }, interval);
      
    } catch (e) {
      console.error('PayPal Button initialization error:', e);
      container.innerHTML = '<div style="color: red;">Error initializing PayPal button. Please refresh the page.</div>';
    }
  });
}

// Start initialization when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializePayPalButtons);
} else {
  initializePayPalButtons();
}
