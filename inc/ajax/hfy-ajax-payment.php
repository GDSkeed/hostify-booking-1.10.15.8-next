<?php
if (!defined('WPINC')) die;

require_once HOSTIFYBOOKING_DIR . 'inc/lib.php';

$data = !empty($_POST['data']) ? $_POST['data'] : [];

if (
    empty($data['listing_id'])
    || empty($data['start_date'])
    || empty($data['end_date'])
    || empty($data['total'])
    || !isset($data['guests'])
    || !isset($data['discount_code'])
    || (empty($data['stripeObject']) && empty($data['payer_id']))
) {
    $out = ['success' => false];
} else {

    if (!empty($data['payer_id'])) {
        // paypal

        ob_start();

        $api = new HfyApi();
        $result = $api->paypalPayment($data);

        if ($result && $result->success == true) {
            $paymentSuccess = true;
            $reservationId = $result->reservation->id;
            $message = "Reservation created successfully!\nYour confirmation code is \"{$result->reservation->confirmation_code}\"";
        } else {
            $paymentSuccess = false;
            $reservationId = null;
            $message = '';
        }

        // save transaction
        $currency = $result->reservation->currency;
        $paymentSettings = $api->getPaymentSettings($data['listing_id'], $data['start_date'], $data['end_date']);
        $paymentIntegrationId = $paymentSettings->services->id ?? '';

        // todo processor ?
        $api->postTransaction($reservationId, $data['total'], $currency, $processor, $reservationId, $paymentIntegrationId, $reservationId, 1);


        $response = (object) [
            'success' => $paymentSuccess,
            'message' => $message,
            'paymentSuccess' => $paymentSuccess,
            'paymentData' => $data,
            'reserveInfo' => $data,
            'reservation' => $result->reservation ?? null,
        ];

        include hfy_tpl('payment/response');

        $out = ob_get_contents();
        ob_end_clean();

    } else {
        // stripe

        $api = new HfyApi();
        $data = $api->postPayment3ds($data);

        $out = [
            'success' => true,
            'state' => 'done',
            'data' => $data,
        ];


        // put the process in background

        // $pguid = time().'_'.sha1(wp_get_session_token() . json_encode($data));
        // $pkey = 'hfy_stripe_payment_'.$pguid;
        // set_transient($pkey, 1, 600);

        // as_enqueue_async_action('hfy_one_time_action_asap', [[
        //     'func' => 'hfy_stripe_send_payment',
        //     'data' => [
        //         'pguid' => $pguid,
        //         'data' => $data,
        //     ],
        // ]]);

        // // spawn_cron();

        // $out = [
        //     'success' => true,
        //     'id' => $pguid,
        //     'state' => 'processing',
        // ];
    }
}
