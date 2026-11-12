<?php

$paypal_rest_keys = rsvpmaker_get_rspmaker_paypal_rest_keys();

function rsvpmaker_paypal_recover_from_database($data) {
  global $wpdb;
  // Try to recover transaction details from post meta if transient expired
  if(empty($data->id)) {
    return array();
  }
  
  // Search for transaction in post meta by looking for the transaction ID
  $results = $wpdb->get_results($wpdb->prepare(
    "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key='rsvpmaker_paypal_tracking' AND meta_value LIKE %s",
    '%' . $wpdb->esc_like($data->id) . '%'
  ));
  
  if(!empty($results)) {
    foreach($results as $row) {
      $tracking = maybe_unserialize($row->meta_value);
      if(is_array($tracking) && !empty($tracking['amount'])) {
        error_log('PayPal: Successfully recovered transaction from post meta. Transaction ID: ' . $data->id);
        return $tracking;
      }
    }
  }
  
  return array();
}

function rsvpmaker_paypal_test_connection($type = 'live') {
  global $paypal_rest_keys;
  try {
    if('live' == $type)
      $environment = new ProductionEnvironment($paypal_rest_keys['client_id'], $paypal_rest_keys['client_secret']);
    else
      $environment = new SandBoxEnvironment($paypal_rest_keys['sandbox_client_id'], $paypal_rest_keys['sandbox_client_secret']);
  }
  catch (HttpException $e) {
    return '<span style="color:red;">'.$e->error_description.'</span>';
  }
  catch (Exception $e) {
    return '<span style="color:red;">'.var_export($e,true).'</span>';
  }
  $client = new PayPalHttpClient($environment);
  $request = new OrdersCreateRequest();
$request->prefer('return=representation');
$request->body = [
                     "intent" => "CAPTURE",
                     "purchase_units" => [[
                         "reference_id" => "test_ref_id1",
                         "amount" => [
                             "value" => "100.00",
                             "currency_code" => "USD"
                         ]
                     ]],
                     "redirect_urls" => [
                          "cancel_url" => "https://example.com/cancel",
                          "return_url" => "https://example.com/return"
                     ] 
                 ];

try {
    // Call API with your client and get a response for your call
    $response = $client->execute($request);    
    // If call returns body in response, you can get the deserialized version from the result attribute of the response
}catch (HttpException $ex) {
    return '<span style="color: red;">'.$ex->statusCode . ' '.  $ex->getMessage().'</span>';
}
return '<span style="color:green; font-weight:bold">'.__('Connected','rsvpmaker').'</span>';
}

function rsvpmaker_paypal_verify_rest () {
    global $wpdb, $rsvp_options, $paypal_rest_keys;
    $paid = $paidnow = $owed = $fee_total = $paidbefore = 0;
    $rsvptable = $wpdb->prefix.'rsvpmaker';
    $request_body = file_get_contents('php://input');        
    $data = json_decode($request_body);
    
    // Add comprehensive debug logging for all payment attempts
    $log_entry = array(
      'timestamp' => current_time('mysql'),
      'transaction_id' => !empty($data->id) ? $data->id : 'unknown',
      'rsvp_tx' => !empty($data->rsvp_tx) ? $data->rsvp_tx : 'unknown',
      'status' => !empty($data->status) ? $data->status : 'no_status',
      'amount' => !empty($data->purchase_units[0]->amount->value) ? $data->purchase_units[0]->amount->value : 0,
      'payer_email' => !empty($data->payer->email_address) ? $data->payer->email_address : 'unknown'
    );
    error_log('PayPal Payment Attempt: ' . json_encode($log_entry));
    
    if(empty($data)) {
      error_log('PayPal: ERROR - Empty request body received');
      return array('status' => 'Invalid PayPal payload - empty body');
    }
    if(empty($data->rsvp_tx) || empty($data->id) || empty($data->status)) {
      error_log('PayPal: ERROR - Missing required fields. rsvp_tx: ' . (!empty($data->rsvp_tx) ? 'set' : 'MISSING') 
        . ', id: ' . (!empty($data->id) ? 'set' : 'MISSING') 
        . ', status: ' . (!empty($data->status) ? $data->status : 'MISSING'));
      return array('status' => 'Invalid PayPal payload - missing fields');
    }
    
    $saved = get_transient('rsvpmaker_paypal_payment_'.$data->rsvp_tx);
    if(empty($saved)) {
      error_log('PayPal: ERROR - Transient not found for rsvp_tx: ' . $data->rsvp_tx . '. Transaction ID: ' . $data->id);
      // Try to recover from database if transient expired
      $saved = rsvpmaker_paypal_recover_from_database($data);
      if(empty($saved)) {
        return array('status' => 'Pending transaction not found - transient expired or invalid');
      }
      error_log('PayPal: Recovered transaction from database for rsvp_tx: ' . $data->rsvp_tx);
    }
    delete_transient('rsvpmaker_paypal_payment_'.$data->rsvp_tx);//one time use
        if(empty($data->purchase_units[0]->amount->value)) {
          error_log('PayPal: ERROR - Missing amount in purchase_units. Transaction ID: ' . $data->id);
          return array('status' => 'Payment amount missing from capture');
        }
        $received_amount = floatval($data->purchase_units[0]->amount->value);
        $expected_amount = floatval($saved['amount']);
        if(abs($received_amount - $expected_amount) > 0.01) {
          error_log('PayPal: ERROR - Amount mismatch. Expected: ' . $expected_amount . ', Received: ' . $received_amount . ', Transaction ID: ' . $data->id);
          return array('status' => 'Payment amount mismatch. Expected ' . $expected_amount . ', got ' . $received_amount);
        }
        if(!empty($rsvp_options['paypal_currency']) && !empty($data->purchase_units[0]->amount->currency_code)) {
          $received_currency = strtoupper($data->purchase_units[0]->amount->currency_code);
          $expected_currency = strtoupper($rsvp_options['paypal_currency']);
          if($received_currency !== $expected_currency) {
            error_log('PayPal: ERROR - Currency mismatch. Expected: ' . $expected_currency . ', Received: ' . $received_currency . ', Transaction ID: ' . $data->id);
            return array('status' => 'Payment currency mismatch');
          }
        }
        if('COMPLETED' !== strtoupper($data->status)) {
          error_log('PayPal: ERROR - Payment status not COMPLETED. Status: ' . $data->status . ', Transaction ID: ' . $data->id);
          return array('status' => 'Payment not completed - status: ' . $data->status);
        }
        
        // Add secondary duplicate check via money transaction table
        $money_table = rsvpmaker_money_table();
        $existing_money_tx = $wpdb->get_row($wpdb->prepare(
          "SELECT * FROM %i WHERE transaction_id=%s",
          $money_table,
          $data->id
        ));
        
        $rsvp_id = (empty($saved['rsvp'])) ? 0 : $saved['rsvp'];
        $rsvp_to = $rsvp_options['rsvp_to'];
        $paidnow = floatval($saved['amount']);
        $status = $data->status.' payment of '.number_format( $paidnow, 2, $rsvp_options['currency_decimal'], $rsvp_options['currency_thousands'] ) . ' ' . $rsvp_options['paypal_currency'].' from '.$data->payer->name->given_name.' '.$data->payer->name->surname;
        $event_id = 0;
        $already_processed = false;
        if($rsvp_id) {
          $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM %i WHERE id=%d",$rsvptable,$rsvp_id));
          if(empty($row)) {
            error_log('PayPal: ERROR - RSVP record not found for ID ' . $rsvp_id . ', Transaction ID: ' . $data->id);
            return array('status' => 'RSVP record not found - ID ' . $rsvp_id);
          }
          error_log('PayPal: Found RSVP record ID ' . $rsvp_id . ', Current paid: ' . $row->amountpaid . ', Owed: ' . $row->owed);
          
          $details = maybe_unserialize($row->details);
          if(!is_array($details))
            $details = array();
          $paidbefore = (empty($row->amountpaid)) ? 0 : floatval($row->amountpaid);
          $fee_total = floatval($row->fee_total);
          $event_id = $row->event;
          
          // Check for duplicate via both RSVP details and money transaction table
          if(!empty($details['paypal_transaction_id']) && $details['paypal_transaction_id'] === $data->id) {
            $already_processed = true;
            $paid = $paidbefore;
            $owed = $fee_total - $paidbefore;
            $status = 'Payment already recorded: '.number_format( $paidbefore, 2, $rsvp_options['currency_decimal'], $rsvp_options['currency_thousands'] ) . ' ' . $rsvp_options['paypal_currency'];
            error_log('PayPal: Duplicate transaction detected (RSVP details) - ID ' . $data->id . ' already processed for RSVP ' . $rsvp_id);
          }
          elseif(!empty($existing_money_tx)) {
            // Transaction was already recorded in money table even if RSVP details don't show it
            $already_processed = true;
            $paid = $paidbefore;
            $owed = $fee_total - $paidbefore;
            $status = 'Payment already recorded: '.number_format( $paidbefore, 2, $rsvp_options['currency_decimal'], $rsvp_options['currency_thousands'] ) . ' ' . $rsvp_options['paypal_currency'];
            error_log('PayPal: Duplicate transaction detected (money table) - ID ' . $data->id . ' already in transaction log');
          }
          else {
            $paid = ($paidbefore) ? $paidbefore + $paidnow : $paidnow;
            $owed = $fee_total - $paid;
            $details['paypal_transaction_id'] = $data->id;
            $details['paypal_capture_status'] = $data->status;
            $details['paypal_payment_amount'] = $paidnow;
            $details['paypal_payment_currency'] = $data->purchase_units[0]->amount->currency_code;
            $details['paypal_payment_processed_at'] = current_time('mysql');
            error_log('PayPal: Preparing to update RSVP - Old paid: ' . $paidbefore . ', New paid: ' . $paid . ', Old owed: ' . ($fee_total - $paidbefore) . ', New owed: ' . $owed);
          }
          if(!empty($details['gift_certificate'])) {
            $gift = get_option($details['gift_certificate']);
            $balance_was = $fee_total - $paidbefore;
          }
          if(!$already_processed) {
            $updatesql = $wpdb->prepare("UPDATE %i SET amountpaid=%s, owed=%s, details=%s WHERE id=%d",$rsvptable,$paid,$owed,maybe_serialize($details),$rsvp_id);
            error_log('PayPal: Executing update query for RSVP ' . $rsvp_id . '. SQL: ' . $updatesql);
            $result = $wpdb->query($updatesql);
            if($result === false) {
              error_log('PayPal: Database update FAILED for RSVP ID ' . $rsvp_id . ', Query Error: ' . $wpdb->last_error . ', Transaction ID: ' . $data->id);
              return array('status' => 'Database update failed: ' . $wpdb->last_error);
            }
            error_log('PayPal: RSVP record successfully updated - ID ' . $rsvp_id . ', New Amount Paid: ' . $paid . ', New Owed: ' . $owed . ', Transaction ID: ' . $data->id);
          }
        } else {
          error_log('PayPal: No RSVP ID provided - Payment for non-RSVP transaction. Transaction ID: ' . $data->id);
        }
        $gift_certificate = '';
        $atts['name'] = $data->payer->name->given_name.' '.$data->payer->name->surname;
        $atts['email'] = $data->payer->email_address;
        $atts['transaction_id'] = $data->id;
        $atts['user_id'] = $data->user_id;
        $atts['amount'] = $data->purchase_units[0]->amount->value;
        $atts['description'] = $data->purchase_units[0]->description;
        $atts['status'] = 'PayPal';
        if(!empty($saved['is_gift_certificate'])) {
          $gift_certificate = rsvpmaker_gift_certificate_create($atts['amount'],$atts);
        }
        // Always log payment to transaction table (for audit trail)
        $existing = $already_processed ? null : rsvpmaker_money_tx($atts);
        if(!$already_processed && $existing) {
          error_log('PayPal: Money transaction recorded - ID ' . $existing->id . ', Amount: ' . $atts['amount']);
        }
        elseif(!$already_processed && !$existing) {
          error_log('PayPal: Failed to record money transaction for amount ' . $atts['amount']);
        }
        
        if(!$already_processed) {
          do_action('rsvpmaker_paypal_confirmation_tracking',$data,$saved);
          $postdata = null;
          if(isset($saved['rsvpmulti'])) {
            $postdata = get_transient($saved['rsvpmulti']);
            $postdata['yesno'] = 1;
            if($postdata && is_array($postdata)) {
              $status .= '<p>'.__('Event registrations saved.','rsvpmaker').'</p>';
              $status .= '<p>'.__('Use the links below to update any of the individual events.','rsvpmaker').'</p>';
              foreach($postdata['rsvpmultievent'] as $event_id) {
                $event_id = intval($event_id);
                if($event_id) {
                $postdata['event'] = $event_id;
                $status .= save_rsvp($postdata,false);
                }
              }
              $confirmation = rsvp_get_confirm(0);
              $mail['html'] = $status.$confirmation.'<p>'.$postdata['description'].'</p>';
              $mail['subject'] = 'CONFIRMING RSVP for '.intval($postdata['events_count']).' events';
              $mail['to'] = $postdata['profile']['email'];
              $mail['from'] = $rsvp_to;
              rsvpmailer($mail);
              $mail['subject'] = 'RSVP for '.intval($postdata['events_count']).' events';
              $mail['to'] = $rsvp_options['rsvp_to'];
              $mail['from'] = $postdata['profile']['email'];
              $mail['fromname'] = $postdata['profile']['first'].' '.$postdata['profile']['last'];
              rsvpmailer($mail);
            }
            else {
              error_log('PayPal: Multi-event data not found or invalid for transient key: ' . $saved['rsvpmulti']);
              $status .= " ERROR processing multi-event registration";
            }
          }
          // Single-event registrations skip the multi-event logic above - no error needed
          
          // Always send receipt email after successful payment
          if($rsvp_id) {
            $receipt_sent = rsvpmaker_confirm_payment($rsvp_id,$rsvp_to);
            if($receipt_sent) {
              error_log('PayPal: Receipt email sent successfully for RSVP ' . $rsvp_id);
            } else {
              error_log('PayPal: Receipt email send failed for RSVP ' . $rsvp_id);
            }
          } else if(!empty($atts['email'])) {
            // For non-RSVP payments (gift certificates), also send receipt
            do_action('rsvpmaker_send_payment_receipt', $atts);
            error_log('PayPal: Payment receipt action triggered for email ' . $atts['email']);
          }
        }
        $rsvp_receipt_link = '';
        if($rsvp_id) {
          $receipt_code = get_post_meta($event_id,'rsvpmaker_receipt_'.$rsvp_id,true);
          if(!$receipt_code) {
            $receipt_code = wp_generate_password(20,false,false);
            if($event_id) {
              update_post_meta($event_id,'rsvpmaker_receipt_'.$rsvp_id,$receipt_code);
            }
            elseif(!empty($saved['post_id'])) {
              update_post_meta($saved['post_id'],'rsvpmaker_receipt_'.$rsvp_id,$receipt_code);
            }
          }
          
          // Generate receipt link with fallback for non-event payments
          $receipt_page = $event_id ?: (!empty($saved['post_id']) ? $saved['post_id'] : 0);
          if($receipt_page) {
            $rsvp_receipt_link = add_query_arg(
              array('rsvp_receipt'=>$rsvp_id,'receipt'=>$receipt_code,'t'=>time()),
              get_permalink($receipt_page)
            );
          } else {
            // Fallback: use home page for payment-only (no event) receipts
            $rsvp_receipt_link = add_query_arg(
              array('rsvp_receipt'=>$rsvp_id,'receipt'=>$receipt_code,'t'=>time()),
              home_url()
            );
          }
          error_log('PayPal: Receipt link generated - ' . $rsvp_receipt_link);
        }
  return array('status'=>$status,'receipt_link'=>$rsvp_receipt_link,'saved'=>$saved,'totalpaid'=>$paid,'paidnow'=>$paidnow,'owed'=>$owed,'fee_total'=>$fee_total,'previously_paid'=>$paidbefore,'existing'=>$existing,'gift_certificate'=>$gift_certificate);
}

add_shortcode('rsvpmaker_paypal_button_invoice','rsvpmaker_paypal_button_invoice');
function rsvpmaker_paypal_button_invoice($atts) {
  $vars['invoice_id'] = 'dues-123';
  return rsvpmaker_paypal_button (40, 'USD', 'test transaction with invoice', $vars);
}

add_shortcode('rsvpmaker_paypal_choice','rsvpmaker_paypal_choice');
function rsvpmaker_paypal_choice($atts = []) {
  $output = '';
  $permalink = get_permalink();
  $ch = isset($atts['choices']) ? explode(';',$atts['choices']) : ['description:test,amount:40.00','description:test2,amount:50.00'];
  $choices = [];
  foreach($ch as $item) {
    $vars = [];
    $itemparts = explode(',',$item);
    foreach($itemparts as $ip) {
      $kv = explode(':',$ip);
      $vars[$kv[0]] = $kv[1];
    }
    $choices[] = $vars;
  }
  if(isset($_GET['paypal_choice']) && !empty($choices)) {
    $index = intval($_GET['paypal_choice']);
    if(!empty($choices[$index])) {
      $vars = $choices[$index];
      $vars['showdescription'] = 'yes';
      $currency = (empty($vars['currency'])) ? 'USD' : sanitize_text_field($vars['currency']);
      $output .= rsvpmaker_paypal_button ($vars['amount'], $currency, $vars['description'], $vars);
      $currency_symbol = '';
      if ( $currency == 'USD' ) {
        $currency_symbol = '$';
      } elseif ( $currency == 'EUR' ) {
        $currency_symbol = 'â‚¬';
      }
      $output .= sprintf( '<p>%s %s<br />%s</p>',  esc_html( $currency_symbol.$vars['amount'] ),  esc_html( $currency ),  esc_html( $vars['description'] ) );
    }
  } else {
    $output .= sprintf('<p>%s:</p>',__('Choices','rsvpmaker'));
    foreach($choices as $index => $choice) {
      $currency = (empty($choice['currency'])) ? 'USD' : sanitize_text_field($choice['currency']);
      $currency_symbol = '';
      if ( $currency == 'USD' ) {
        $currency_symbol = '$';
      } elseif ( $currency == 'EUR' ) {
        $currency_symbol = 'â‚¬';
      }
      $output .= sprintf('<p><a href="%s">%s - %s%s %s</a></p>',add_query_arg('paypal_choice',$index,$permalink),$choice['description'],$currency_symbol,$choice['amount'],$currency);
    }
  }

  return $output; 
}

//rsvpmaker_paypal_button( $charge, $rsvp_options['paypal_currency'], $post->post_title, array('rsvp'=>$rsvp_id,'event' => $post->ID) )
function rsvpmaker_paypal_button ($amount, $currency_code = 'USD', $description='', $vars = array()) {
  global $paypal_rest_keys, $post, $current_user;
  
  if(empty($paypal_rest_keys['client_id']) && empty($paypal_rest_keys['sandbox_client_id'])) {
    error_log('PayPal Button: PayPal keys not configured. client_id=' . (isset($paypal_rest_keys['client_id']) ? '**set**' : 'empty') . ', sandbox_client_id=' . (isset($paypal_rest_keys['sandbox_client_id']) ? '**set**' : 'empty'));
    return '';
  }

  if(strlen($description) > 50)
    $description = substr($description,0,50) . ' ...';

  $rsvp_id = (empty($vars['rsvp'])) ? 0 : $vars['rsvp'];
  $invoice_id = (empty($vars['invoice_id'])) ? '' : $vars['invoice_id'];
  
  // Get event ID from vars or global post
  $event = 0;
  if(!empty($vars['event'])) {
    $event = $vars['event'];
  } elseif($post && isset($post->ID)) {
    $event = $post->ID;
  }
  
  if($paypal_rest_keys['sandbox']) {
      $paypal_client_id = $paypal_rest_keys['sandbox_client_id'];
      if(empty($paypal_client_id)) {
        error_log('PayPal Button: Sandbox mode enabled but sandbox_client_id is empty');
        return '';
      }
  } else {
      $paypal_client_id = $paypal_rest_keys['client_id'];
      if(empty($paypal_client_id)) {
        error_log('PayPal Button: Live mode but client_id is empty');
        return '';
      }
  }
  $verify = add_query_arg($vars, rest_url('rsvpmaker/v1/paypal_paid'));
  $vars['amount'] = $amount;
  $vars['description'] = $description;
  if($post && isset($post->ID)) {
    $vars['post_id'] = $post->ID;
  }
  $details = [];
  $purchase_code = (empty($vars['purchase_code'])) ? '' : $vars['purchase_code'];
  
  // Only add post meta if we have a valid post ID
  $tracking = false;
  if($post && isset($post->ID)) {
    $tracking = add_post_meta($post->ID,'rsvpmaker_paypal_tracking',$vars);
  }
  
  $transaction_code = wp_generate_password(20,false,false);
  if(!set_transient('rsvpmaker_paypal_payment_'.$transaction_code,$vars,(15 * MINUTE_IN_SECONDS))) {
    error_log('PayPal Button: WARNING - Failed to set transient for transaction code ' . $transaction_code);
  }
  $enable_funding = (empty($paypal_rest_keys['funding_sources'])) ? '' : $paypal_rest_keys['funding_sources'];
  if($enable_funding)
    $enable_funding = '&enable-funding='.$enable_funding;
  $disable_funding = (empty($paypal_rest_keys['excluded_funding_sources'])) ? '' : $paypal_rest_keys['excluded_funding_sources'];
  if($disable_funding)
    $disable_funding = '&disable-funding='.$disable_funding;
  ob_start();
  ?>
  <script
      src="<?php echo esc_url('https://www.paypal.com/sdk/js?client-id='.$paypal_client_id.'&currency='.$currency_code.$enable_funding.$disable_funding);?>">
  </script>
  <script>
    var purchase = {
          custom_id: '<?php echo intval($rsvp_id); ?>',
          <?php if($invoice_id) {
            echo "invoice_id: '".esc_js($invoice_id)."',\n";
          }
          ?>
          description: '<?php echo esc_js($description); ?>',
            amount: {
              value: '<?php echo esc_js($amount); ?>',
              currency_code: '<?php echo esc_js($currency_code); ?>',
            },
          };
    paypal.Buttons({
      createOrder: function(data, actions) {
        return actions.order.create({
          purchase_units: [purchase]
        });
      },
      onApprove: function(data, actions) {
        return actions.order.capture().then(function(details) {
          details.rsvp_tx = '<?php echo esc_js($transaction_code); ?>';
          details.user_id = '<?php if(isset($current_user->ID)) echo esc_js($current_user->ID); ?>';
          result = 'Recording transaction by ' + details.payer.name.given_name+'... ';
          document.getElementById("paypal-button-container").innerHTML = result;
          
          // Helper function for retry logic with exponential backoff
          function sendPaymentWithRetry(paymentData, retryCount = 0, maxRetries = 3) {
            const delay = Math.pow(2, retryCount) * 1000; // 1s, 2s, 4s
            
            return new Promise((resolve) => {
              setTimeout(() => {
                fetch('<?php echo esc_url($verify); ?>', {
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
                  // Success - server accepted the payment
                  if (result.status >= 200 && result.status < 300) {
                    console.log('PayPal payment verified successfully:', result.body);
                    resolve(result.body);
                  } else {
                    // Server error, but might be retryable
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
          
          // Send payment with retry logic
          return sendPaymentWithRetry(details).then(function(myJson) {
            if(myJson.status) {
              result = '<p>'+myJson.status+'</p>';
              if(myJson.gift_certificate) {
                console.log('gift certificate');
                console.log(myJson);
                result += '<h1>'+myJson.gift_certificate+'</h1><p>Give this code to the recipient of your gift. To redeem, enter into the coupon field of the registration form for any event on this site.</p>';
              }
              if(myJson.receipt_link)
                result += '<p><a href="'+myJson.receipt_link+'">Print Receipt</a></p>';
            } else {
              result = 'Transaction error';
            }
            const confirmblock = document.getElementById("rsvpconfirm");
            if(confirmblock && confirmblock.innerHTML) // removes the language saying money is still owed
              confirmblock.innerHTML = '<div class="rsvpmakerpaypalresult"><p><strong>PayPal Result</strong></p>'+result+'</div>';
            else
              document.getElementById("paypal-button-container").innerHTML  = '<div class="rsvpmakerpaypalresult"><p><strong>PayPal Result</strong></p>'+result+'</div>';
          });
        });
      },
      onError: function (err) {
        document.getElementById("paypal-error-container").innerHTML = 'Error connecting to PayPal service. Please Try again';
    // Show an error page here, when an error occurs
      }
    }).render('#paypal-button-container');

    let interval = 30*60000;
    setTimeout(() => {
      document.getElementById("paypal-button-container").innerHTML = '<h1>Time to pay expired</h1>';
    }, interval);//30 minutes

  </script>
  <div id="paypal-error-container" style="color: red; font-weight: bold;"></div>
  <div id="paypal-button-container"></div>
  <?php
    $button_html = ob_get_clean();
    if(empty($button_html)) {
      error_log('PayPal Button: ob_get_clean() returned empty/false. Transaction code: ' . (isset($transaction_code) ? $transaction_code : 'not set'));
    }
    return $button_html;
  }

function rsvpmaker_paypal_button_embed($atts) {
global $rsvp_options;
global $paypal_rest_keys, $post;
if(!$paypal_rest_keys)
  $paypal_rest_keys = rsvpmaker_get_rspmaker_paypal_rest_keys();

$currency = 'USD';
if(isset($atts['currencyCode']))
  $currency = sanitize_text_field($atts['currencyCode']);

elseif(isset($rsvp_options['paypal_currency']))
  $currency = $rsvp_options['paypal_currency'];

if ( isset( $atts['paymentType'] ) && ( $atts['paymentType'] == 'donation' ) ) {
  if(isset($_GET['amount']))
    {
        $atts['amount'] = sanitize_text_field($_GET['amount']);
    }
  else
    return sprintf( '<form action="%s" method="get">%s (%s): <input type="text" name="amount" value=""><br><button class="stripebutton">%s</button>%s</form>', get_permalink(), esc_html__( 'Amount', 'rsvpmaker' ), esc_attr( strtoupper( $currency ) ), esc_html__( 'Pay with PayPal' ), wp_nonce_field( RSVPMAKER_NONCE_ACTION, RSVPMAKER_NONCE_FIELD, true, false ) );
}
if(empty($paypal_rest_keys['client_id']) && empty($paypal_rest_keys['sandbox_client_id']))
  return 'client ID not set';

if(isset($atts['paymentType']) && 'schedule' == $atts['paymentType']) {
  $month_index = strtolower(date('F'));
  $atts['amount'] = $atts[$month_index];
}

if(empty($atts['amount']) || !is_numeric($atts['amount']))
  return 'amount not set';

$explanation = (empty($atts['paypal'])) ? '' : '<p>'.__('Or pay with PayPal','rsvpmaker').'</p>';

$currency_symbol = '';

if ( $currency == 'USD' ) {

  $currency_symbol = '$';

} elseif ( $currency == 'EUR' ) {

  $currency_symbol = 'â‚¬';
}

$charge = $atts['amount'];
$description = (empty($atts['description'])) ? 'charge from '.$_SERVER['SERVER_NAME'] : sanitize_text_field($atts['description']);
$output = $explanation . rsvpmaker_paypal_button( $charge, $currency, $description, $atts );

$show = ( ! empty( $atts['showdescription'] ) && ( $atts['showdescription'] == 'yes' ) ) ? true : false;
if ( $show && empty($atts['paypal']) ) {
  $output .= sprintf( '<p>%s %s<br />%s</p>',  esc_html( $currency_symbol.$atts['amount'] ),  esc_html( $currency ),  esc_html( $atts['description'] ) );
}

return $output;
}

add_shortcode('rsvpmaker_paypay_button','rsvpmaker_paypay_button_embed');
?>
