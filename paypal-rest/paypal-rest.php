<?php
require_once 'vendor/autoload.php';
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use BraintreeHttp\HttpException;

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
    if(isset($_REQUEST['paypal_verify']))
    {
        require_once 'rsvpmaker/GetOrder.php';
        $request_body = file_get_contents('php://input');        
        $data = json_decode($request_body);
        $order_id = empty($data->orderID) ? 0 : $data->orderID;
        $pporder = new RSVPMakerGetOrder;
        $pporder->getOrder($order_id);
        do_action('rsvpmaker_paypal_confirmation_tracking',$data);
        die();
    }
}

add_action('init','paypal_verify_rest');

add_shortcode('rsvpmaker_paypal_button_invoice','rsvpmaker_paypal_button_invoice');
function rsvpmaker_paypal_button_invoice($atts) {
  $vars['invoice_id'] = 'dues-123';
  return rsvpmaker_paypal_button (40, 'USD', 'test transaction with invoice', $vars);
}

//rsvpmaker_paypal_button( $charge, $rsvp_options['paypal_currency'], $post->post_title, array('rsvp'=>$rsvp_id,'event' => $post->ID) )
function rsvpmaker_paypal_button ($amount, $currency_code = 'USD', $description='', $vars = array()) {
  global $paypal_rest_keys, $post;
  if(empty($paypal_rest_keys['client_id']) && empty($paypal_rest_keys['sandbox_client_id']))
    return;

  $rsvp_id = (empty($vars['rsvp'])) ? 0 : $vars['rsvp'];
  $invoice_id = (empty($vars['invoice_id'])) ? '' : $vars['invoice_id'];
  $event = (empty($vars['event'])) ? $post->ID : $vars['event'];
  if($paypal_rest_keys['sandbox'])
      $paypal_client_id = $paypal_rest_keys['sandbox_client_id'];
  else
      $paypal_client_id = $paypal_rest_keys['client_id'];
  $verify = '/?paypal_verify=1'; //($rsvp_id) ? '/?paypal_verify=1&rsvp='.$rsvp_id.'&event='.$post->ID : 
  foreach($vars as $key => $value)
    $verify .= '&'.$key.'='.$value;
  $vars['amount'] = $amount;
  $vars['description'] = $description;
  $tracking = add_post_meta($post->ID,'rsvpmaker_paypal_tracking',$vars);
  ob_start();
  ?>
  <script
      src="https://www.paypal.com/sdk/js?client-id=<?php echo $paypal_client_id.'&currency='.$currency_code;?>">
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
              currency_code: '<?php echo $currency_code; ?>'
            }
          };
    paypal.Buttons({
      createOrder: function(data, actions) {
        return actions.order.create({
          purchase_units: [purchase]
        });
      },
      onApprove: function(data, actions) {
        return actions.order.capture().then(function(details) {
          result = 'Verifying transaction by ' + details.payer.name.given_name+'... ';
          document.getElementById("paypal-button-container").innerHTML = result;
          // Call your server to save the transaction
          return fetch('<?php echo $verify; ?>', {
            method: 'post',
            headers: {
              'content-type': 'application/json'
            },
            body: JSON.stringify({
              orderID: data.orderID
            })
          }).then(function(response) {
      return response.json();
    })
    .then(function(myJson) {
      console.log(myJson.result);
      if(myJson.statusCode == 200)
          {
              result = '<p>Successful payment, #'+myJson.result.id+' '+myJson.result.purchase_units[0].amount.currency_code +' '+ myJson.result.purchase_units[0].amount.value+' recorded</p>';
          }
          else {
              result = 'Transaction error';
          }
          console.log(result);
          document.getElementById("paypal-button-container").innerHTML = '<div class="rsvpmakerpaypalresult"><h2>PayPal</h2><p>'+result+'</p></div>';
        });
        });
      },
      onError: function (err) {
        document.getElementById("paypal-error-container").innerHTML = 'Error connecting to PayPal service. Please Try again';
    // Show an error page here, when an error occurs
      }
    }).render('#paypal-button-container');

  </script>
  <div id="paypal-error-container" style="color: red; font-weight: bold;"></div>
  <div id="paypal-button-container"></div>
  <?php
        return ob_get_clean();
  }

function rsvpmaker_paypay_button_embed($atts) {
//rsvpmaker_paypal_button( $charge, $rsvp_options['paypal_currency'], $post->post_title, array('rsvp'=>$rsvp_id,'event' => $post->ID) )
global $rsvp_options;
global $paypal_rest_keys, $post;
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
  return;
if(empty($atts['amount']) || !is_numeric($atts['amount']))
  return;
 
$explanation = (empty($atts['paypal'])) ? '' : '<p>'.__('Or pay with PayPal','rsvpmaker').'</p>';

$currency_symbol = '';

if ( $currency == 'USD' ) {

  $currency_symbol = '$';

} elseif ( $currency == 'EUR' ) {

  $currency_symbol = '€';
}

$charge = $atts['amount'];
$description = (empty($atts['description'])) ? 'charge from '.$_SERVER['SERVER_NAME'] : sanitize_text_field($atts['description']);
$output = $explanation . rsvpmaker_paypal_button( $charge, $currency, $description, array('tracking'=>'rsvpmaker-paypal') );

$show = ( ! empty( $atts['showdescription'] ) && ( $atts['showdescription'] == 'yes' ) ) ? true : false;
if ( $show && empty($atts['paypal']) ) {
  $output .= sprintf( '<p>%s %s<br />%s</p>', esc_html( $currency_symbol.$atts['amount'] ), esc_html( $currency ), esc_html( $atts['description'] ) );
}

return $output;
}

add_shortcode('rsvpmaker_paypay_button','rsvpmaker_paypay_button_embed');
?>