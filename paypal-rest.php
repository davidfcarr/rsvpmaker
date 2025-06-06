<?php

$paypal_rest_keys = get_rspmaker_paypal_rest_keys();

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

function paypal_verify_rest () {
    global $wpdb, $rsvp_options, $paypal_rest_keys;
    $paid = $paidnow = $owed = $fee_total = $paidbefore = 0;
    $rsvptable = $wpdb->prefix.'rsvpmaker';
        $request_body = file_get_contents('php://input');        
        $data = json_decode($request_body);
        $saved = get_transient('rsvpmaker_paypal_payment_'.$data->rsvp_tx);
        if(empty($saved))
          return array('status' => 'Pending transaction not found');
        delete_transient('rsvpmaker_paypal_payment_'.$data->rsvp_tx);//one time use
        $rsvp_id = (empty($saved['rsvp'])) ? 0 : $saved['rsvp'];
        $rsvp_to = $rsvp_options['rsvp_to'];
        $paidnow = floatval($saved['amount']);
        $status = $data->status.' payment of '.number_format( $paidnow, 2, $rsvp_options['currency_decimal'], $rsvp_options['currency_thousands'] ) . ' ' . $rsvp_options['paypal_currency'].' from '.$data->payer->name->given_name.' '.$data->payer->name->surname;
        $event_id = 0;
        if($rsvp_id) {
          $row = $wpdb->get_row("SELECT * FROM $rsvptable WHERE id=$rsvp_id");
          $details = unserialize($row->details);
          $paidbefore = (empty($row->amountpaid)) ? 0 : floatval($row->amountpaid);
          $paid = ($paidbefore) ? $paidbefore + $paidnow : $paidnow;
          $fee_total = floatval($row->fee_total);
          $calculation = "$fee_total - $paid";
          $owed = $fee_total - $paid;
          if($details['gift_certificate']) {
            $gift = get_option($details['gift_certificate']);
            $balance_was = $fee_total - $paidbefore;
          }
          $event_id = $row->event;
          $updatesql = "UPDATE $rsvptable SET amountpaid='$paid', owed='$owed' WHERE id=$rsvp_id";
          $wpdb->query($updatesql);
        }
        $gift_certificate = '';
        if(!empty($saved['purchase_code'])) {
          $purchase = get_transient($saved['purchase_code']);
          if(!empty($purchase[2]))
            $rsvp_to = sanitize_text_field($purchase[2]);
          if(!empty($saved['is_gift_certificate'])) {
            $gift_certificate = 'GIFT'.wp_generate_password(12, false, false);
            $details['gift_certficate'] = $gift_certificate;
            $sql = $wpdb->prepare("UPDATE $rsvptable set details=%s WHERE id=$rsvp_id",serialize($details));
            $wpdb->query($sql);
            add_option($gift_certificate,trim(preg_replace('/[^0-9\.]/','',$purchase[1])));
          }
        }
        $atts['name'] = $data->payer->name->given_name.' '.$data->payer->name->surname;
        $atts['email'] = $data->payer->email_address;
        $atts['transaction_id'] = $data->id;
        $atts['user_id'] = $data->user_id;
        $atts['amount'] = $data->purchase_units[0]->amount->value;
        $atts['description'] = $data->purchase_units[0]->description;
        $atts['status'] = 'PayPal';
        $existing = rsvpmaker_money_tx($atts);
        do_action('rsvpmaker_paypal_confirmation_tracking',$data,$saved);
        $postdata = null;
        if(isset($saved['rsvpmulti'])) {
          $postdata = get_transient($saved['rsvpmulti']);
          $postdata['yesno'] = 1;
          if($postdata && is_array($postdata)) {
            $status .= '<p>'.__('Event registrations saved.','rsvpmaker').'</p>';
            $status .= '<p>'.__('Use the links below to update any of the individual events.','rsvpmaker').'</p>';
            foreach($postdata['rsvpmultievent'] as $event_id) {
              $postdata['event'] = $event_id;
              $status .= save_rsvp($postdata,false);
            }
            $confirmation = rsvp_get_confirm(0);
            $mail['html'] = $status.$confirmation;
            $mail['subject'] = 'CONFIRMING RSVP for '.sizeof($postdata['rsvpmultievent']).' events';
            $mail['to'] = $postdata['profile']['email'];
            $mail['from'] = $rsvp_to;
            rsvpmailer($mail);
            $mail['subject'] = 'RSVP for '.sizeof($postdata['rsvpmultievent']).' events';
            $mail['to'] = $rsvp_options['rsvp_to'];
            $mail['from'] = $postdata['profile']['email'];
            $mail['fromname'] = $postdata['profile']['first'].' '.$postdata['profile']['last'];
            rsvpmailer($mail);
          }
          else
            $status .= " ERROR processing multi-event registration";
        }
        $rsvp_receipt_link = '';
        if($rsvp_id) {
          rsvpmaker_confirm_payment($rsvp_id,$rsvp_to);
          $receipt_code = get_post_meta($event_id,'rsvpmaker_receipt_'.$rsvp_id,true);
          if(!$receipt_code) {
            $receipt_code = wp_generate_password(20,false,false);
            if($event_id)
              update_post_meta($event_id,'rsvpmaker_receipt_'.$rsvp_id,$receipt_code);
            elseif(!empty($saved['post_id']))
              update_post_meta($saved['post_id'],'rsvpmaker_receipt_'.$rsvp_id,$receipt_code);
          }
          if($event_id)
            $rsvp_receipt_link = add_query_arg(array('rsvp_receipt'=>$rsvp_id,'receipt'=>$receipt_code,'t'=>time()),get_permalink($event_id));
          elseif(!empty($saved['post_id']))
            $rsvp_receipt_link = add_query_arg(array('rsvp_receipt'=>$rsvp_id,'receipt'=>$receipt_code,'t'=>time()),get_permalink($saved['post_id']));
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
        $currency_symbol = '€';
      }
      $output .= sprintf( '<p>%s %s<br />%s</p>', esc_html( $currency_symbol.$vars['amount'] ), esc_html( $currency ), esc_html( $vars['description'] ) );
    }
  } else {
    $output .= sprintf('<p>%s:</p>',__('Choices','rsvpmaker'));
    foreach($choices as $index => $choice) {
      $currency = (empty($choice['currency'])) ? 'USD' : sanitize_text_field($choice['currency']);
      $currency_symbol = '';
      if ( $currency == 'USD' ) {
        $currency_symbol = '$';
      } elseif ( $currency == 'EUR' ) {
        $currency_symbol = '€';
      }
      $output .= sprintf('<p><a href="%s">%s - %s%s %s</a></p>',add_query_arg('paypal_choice',$index,$permalink),$choice['description'],$currency_symbol,$choice['amount'],$currency);
    }
  }

  return $output; 
}

//rsvpmaker_paypal_button( $charge, $rsvp_options['paypal_currency'], $post->post_title, array('rsvp'=>$rsvp_id,'event' => $post->ID) )
function rsvpmaker_paypal_button ($amount, $currency_code = 'USD', $description='', $vars = array()) {
  global $paypal_rest_keys, $post, $current_user;
  if(empty($paypal_rest_keys['client_id']) && empty($paypal_rest_keys['sandbox_client_id']))
    return;

  if(strlen($description) > 50)
    $description = substr($description,0,50) . ' ...';

  $rsvp_id = (empty($vars['rsvp'])) ? 0 : $vars['rsvp'];
  $invoice_id = (empty($vars['invoice_id'])) ? '' : $vars['invoice_id'];
  $event = (empty($vars['event'])) ? $post->ID : $vars['event'];
  if($paypal_rest_keys['sandbox'])
      $paypal_client_id = $paypal_rest_keys['sandbox_client_id'];
  else
      $paypal_client_id = $paypal_rest_keys['client_id'];
  $verify = '/wp-json/rsvpmaker/v1/paypal_paid?'; //($rsvp_id) ? '/?paypal_verify=1&rsvp='.$rsvp_id.'&event='.$post->ID : 
  foreach($vars as $key => $value)
    $verify .= '&'.$key.'='.$value;
  $vars['amount'] = $amount;
  $vars['description'] = $description;
  $vars['post_id'] = $post->ID;
  $purchase_code = (empty($vars['purchase_code'])) ? '' : $vars['purchase_code'];
  $tracking = add_post_meta($post->ID,'rsvpmaker_paypal_tracking',$vars);
  $transaction_code = wp_generate_password(20,false,false);
  set_transient('rsvpmaker_paypal_payment_'.$transaction_code,$vars,(15 * MINUTE_IN_SECONDS));
  $enable_funding = (empty($paypal_rest_keys['funding_sources'])) ? '' : $paypal_rest_keys['funding_sources'];
  if($enable_funding)
    $enable_funding = '&enable-funding='.$enable_funding;
  $disable_funding = (empty($paypal_rest_keys['excluded_funding_sources'])) ? '' : $paypal_rest_keys['excluded_funding_sources'];
  if($disable_funding)
    $disable_funding = '&disable-funding='.$disable_funding;
  ob_start();
  ?>
  <script
      src="https://www.paypal.com/sdk/js?client-id=<?php echo $paypal_client_id.'&currency='.$currency_code; echo $enable_funding.$disable_funding;?>">
  </script>
  <script>
    var purchase = {
          custom_id: '<?php echo $rsvp_id; ?>',
          <?php if($invoice_id) {
            echo "invoice_id: '".$invoice_id."',\n";
          }
          ?>
          description: '<?php echo $description; ?>',
            amount: {
              value: '<?php echo $amount; ?>',
              currency_code: '<?php echo $currency_code; ?>',
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
          details.rsvp_tx = '<?php echo $transaction_code; ?>';
          details.user_id = '<?php if(isset($current_user->ID)) echo $current_user->ID; ?>';
          result = 'Recording transaction by ' + details.payer.name.given_name+'... ';
          document.getElementById("paypal-button-container").innerHTML = result;
          // Call your server to save the transaction
          return fetch('<?php echo $verify; ?>', {
            method: 'post',
            headers: {
              'content-type': 'application/json'
            },
            body: JSON.stringify(details)
          }).then(function(response) {
      return response.json();
    })
    .then(function(myJson) {
      if(myJson.status)
          {
              result = '<p>'+myJson.status+'</p>';
              if(myJson.gift_certificate) {
                console.log('gift certificate');
                console.log(myJson);
                result += '<h1>'+myJson.gift_certificate+'</h1><p>Give this code to the recipient of your gift. To redeem, enter into the coupon field of the registration form for any event on this site.</p>';
              }
              if(myJson.receipt_link)
                result += '<p><a href="'+myJson.receipt_link+'">Print Receipt</a></p>';
          }
          else {
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
    return ob_get_clean();
  }

function rsvpmaker_paypay_button_embed($atts) {
global $rsvp_options;
global $paypal_rest_keys, $post;
if(!$paypal_rest_keys)
  $paypal_rest_keys = get_rspmaker_paypal_rest_keys();

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
    return sprintf( '<form action="%s" method="get">%s (%s): <input type="text" name="amount" value=""><br><button class="stripebutton">%s</button>%s</form>', get_permalink(), __( 'Amount', 'rsvpmaker' ), esc_attr( strtoupper( $currency ) ), __( 'Pay with PayPal' ), rsvpmaker_nonce('return') );
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

  $currency_symbol = '€';
}

$charge = $atts['amount'];
$description = (empty($atts['description'])) ? 'charge from '.$_SERVER['SERVER_NAME'] : sanitize_text_field($atts['description']);
$output = $explanation . rsvpmaker_paypal_button( $charge, $currency, $description, $atts );

$show = ( ! empty( $atts['showdescription'] ) && ( $atts['showdescription'] == 'yes' ) ) ? true : false;
if ( $show && empty($atts['paypal']) ) {
  $output .= sprintf( '<p>%s %s<br />%s</p>', esc_html( $currency_symbol.$atts['amount'] ), esc_html( $currency ), esc_html( $atts['description'] ) );
}

return $output;
}

add_shortcode('rsvpmaker_paypay_button','rsvpmaker_paypay_button_embed');
?>