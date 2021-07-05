<?php
require_once 'vendor/autoload.php';
$paypal_rest_keys = get_rspmaker_paypal_rest_keys();

function paypal_verify_rest() {
	if ( isset( $_REQUEST['paypal_verify'] ) ) {
		require_once 'rsvpmaker/GetOrder.php';
		$request_body = file_get_contents( 'php://input' );
		$data         = json_decode( $request_body );
		$order_id     = empty( $data->orderID ) ? 0 : $data->orderID;
		$pporder      = new RSVPMakerGetOrder();
		$pporder->getOrder( $order_id );
		die();
	}
}

add_action( 'init', 'paypal_verify_rest' );

function rsvpmaker_paypal_button( $amount, $currency_code = 'USD', $description = '', $rsvp_id = 0, $key='', $value='' ) {

	global $paypal_rest_keys, $post;
	if ( $paypal_rest_keys['sandbox'] ) {
		$paypal_client_id = $paypal_rest_keys['sandbox_client_id'];
	} else {
		$paypal_client_id = $paypal_rest_keys['client_id'];
	}
	$verify = ( $rsvp_id ) ? '/?paypal_verify=1&rsvp=' . $rsvp_id . '&event=' . intval( $post->ID ) : '/?paypal_verify=1';
	if(!empty($key) && !empty($value))
	{
		$key = sanitize_text_field($key);
		$value = sanitize_text_field($value);
		$verify .= '&key='.$key.'&value='.$value;
	}
	ob_start();
	?>
  <script
	  src="https://www.paypal.com/sdk/js?client-id=<?php echo esc_attr($paypal_client_id); ?>">
  </script>
  <script>
	paypal.Buttons({
	  createOrder: function(data, actions) {
		return actions.order.create({
		  purchase_units: [{
		  custom_id: '<?php echo esc_html( $rsvp_id ); ?>',
		  description: '<?php echo esc_html( $description ); ?>',
			amount: {
			  value: '<?php echo esc_html( $amount ); ?>',
			  currency_code: '<?php echo esc_html( $currency_code ); ?>'
			}
		  }]
		});
	  },
	  onApprove: function(data, actions) {
		return actions.order.capture().then(function(details) {
		  result = 'Verifying transaction by ' + details.payer.name.given_name+'... ';
		  document.getElementById("paypal-button-container").innerHTML = result;
		  // Call your server to save the transaction
		  return fetch('<?php echo esc_attr( $verify ); ?>', {
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
		  document.getElementById("paypal-button-container").innerHTML = '<div class="rsvpmakerpaypalresult"><h2>PayPal</h2><p>'+result+'</p></div>';
		  //document.getElementById("paypal-button-container").innerHTML = '<div class="rsvpmakerpaypalresult"><h2>PayPal</h2>'+myJSon.result.payment_confirmation_message+'</div>';
		  if(myJson.statusCode == 200) {
			console.log('Now, check for confirmation message');
			fetch(rsvpmaker_json_url+'paypalsuccess/<?php echo esc_attr($post->ID); ?>/<?php echo esc_attr( $rsvp_id ); ?>')
			.then((response) => {
			  return response.json();
			})
			.then((myJson) => {
			  console.log(myJson);
			  if(myJson.payment_confirmation_message) {
				console.log('preparing confirmation message');
				var withconfirmation = document.getElementById("paypal-button-container").innerHTML + myJson.payment_confirmation_message;
				document.getElementById("paypal-button-container").innerHTML = withconfirmation;
			  }
			  else 
				console.log('confirmation message not found');
			});
		  }//end check for confirmation
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

?>
