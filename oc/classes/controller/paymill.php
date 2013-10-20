<?php defined('SYSPATH') or die('No direct script access.');

/**
* Paymill class
*
* @package Open Classifieds
* @subpackage Core
* @category Helper
* @author Chema Garrido <chema@garridodiaz.com>, Slobodan Josifovic <slobodan.josifovic@gmail.com>
* @license GPL v3
*/

class Controller_Paymill extends Controller{
	

	public function after()
	{

	}
	

	/**
	 * [action_form] generates the form to pay at paypal
	 */
	public function action_pay()
	{ 
		$this->auto_render = FALSE;

        $seotitle = $this->request->param('id');

        $product = new Model_product();
        $product->where('seotitle','=',$seotitle)
            ->where('status','=',Model_Product::STATUS_ACTIVE)
            ->limit(1)->find();

        if ($product->loaded())
        {
            //Functions from https://github.com/paymill/paybutton-examples
            $privateApiKey  = Core::config('payment.paymill_private');

            if ( isset( $_POST[ 'paymillToken' ] ) ) 
            {
                $token = $_POST[ 'paymillToken' ];

                $client = Paymill::request(
                    'clients/',
                    array(),
                    $privateApiKey
                );

                $payment = Paymill::request(
                    'payments/',
                    array(
                         'token'  => $token,
                         'client' => $client[ 'id' ]
                    ),
                    $privateApiKey
                );

                $transaction = Paymill::request(
                    'transactions/',
                    array(
                         'amount'      => $Paymill::money_format($product->price),
                         'currency'    => $product->currency,
                         'client'      => $client[ 'id' ],
                         'payment'     => $payment[ 'id' ],
                         'description' => $product->title,
                    ),
                    $privateApiKey
                );

                if ( isset( $transaction[ 'status' ] ) && ( $transaction[ 'status' ] == 'closed' ) ) 
                {
                    echo '<strong>Transaction successful! ask for email address.</strong>';
                    // $this->template = View::factory('paypal', $paypal_data);
                    // $this->response->body($this->template->render());
                    die();
                } 
                else 
                {
                    $msg = __('Transaction not successful!');
                    if ( ( !$transaction[ 'status' ] == 'closed' ) ) 
                        $msg.= ' - '. $transaction[ 'data' ][ 'error' ];

                    Kohana::$log->add(Log::ERROR, 'PAymill '.$msg);

                    Alert::set(Alert::ERROR, $msg);
                    $this->request->redirect(Route::url('default'));

                }
            }
            else
            {
                Alert::set(Alert::INFO, __('Please fill your card details.'));
                $this->request->redirect(Route::url('product', array('seotitle'=>$product->seotitle)));
            }
			
		}
		else
		{
			Alert::set(Alert::INFO, __('Product could not be loaded'));
            $this->request->redirect(Route::url('product', array('seotitle'=>$product->seotitle)));
		}
	}

}