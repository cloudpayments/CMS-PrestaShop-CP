<?php
/**
* Controller for process cloudpayments callbacks
*/
class CloudpaymentsCallback 
{
	private $response;

	public function __construct($callbackType)
	{
		$this->response       = new stdClass;
		$this->response->code = 13;//error code
		if ($this->checkSignature()) {
			$this->init($callbackType);
		}	
	}

	/**
	*  Init callbacks
	*  @param string $callbackType 
	*  @return void
	*/
	private function init($callbackType)
	{
		switch($callbackType)
		{
			case 'check':
				$this->check();
			break;

			case 'pay':
				$this->pay();
			break;

			case 'fail':
				$this->fail();
			break;

			case 'refund':
				$this->refund();
			break;
		}
	}

	/**
	*  Pay callback
	*  @return void
	*/
	private function pay()
	{
		$invoiceId = Tools::getValue('InvoiceId');
		if($invoiceId) {
			$order = new Order(
				Order::getOrderByCartId($invoiceId)
			);
			if ($order->id != NULL) {
				$history = new OrderHistory();
				$history->id_order = $order->id;
				$history->changeIdOrderState((int)Configuration::get('PS_OS_PAYMENT'), $history->id_order);
				$history->addWithemail();
				$history->save();

				//save payment info
				$payments = $order->getOrderPaymentCollection();
				$payments[0]->transaction_id  = Tools::getValue('TransactionId');
				$payments[0]->card_number     = Tools::getValue('CardFirstSix').Tools::getValue('CardLastFour');
				//$payments[0]->card_brand      = Tools::getValue('CardType');
				$payments[0]->card_expiration = Tools::getValue('CardExpDate');
				$payments[0]->card_holder     = Tools::getValue('Name');
				$payments[0]->update();

				$this->response->code = 0;
			}
		}
	}

	/**
	*  Check callback
	*  @return void
	*/
	private function check()
	{
		$invoiceId = Tools::getValue('InvoiceId');
		$amount    = Tools::getValue('Amount');
		$currency  = Tools::getValue('Currency');
		if($invoiceId && $amount && $currency) {
			$cart = new Cart($invoiceId);
			if ($cart) {
				if ($cart->getOrderTotal(true) == $amount && Currency::getIdByIsoCode($currency) == $cart->id_currency) {
					$this->response->code = 0;
				}
			}
		}
	}


	/**
	*  Check signature
	*  @return bool
	*/
	private function checkSignature() 
	{
		$headers   = getallheaders();
		if (!isset($headers['Content-HMAC'])) {
		    return false;
        }
		$signature = base64_encode(
			hash_hmac(
				'SHA256',
				file_get_contents('php://input'), 
				Configuration::get('CLOUDPAYMENTS_APIKEY'), 
				true)
		);

		return $headers['Content-HMAC'] == $signature;
	}

	private function fail(){}

	private function refund()
	{
		$invoiceId = Tools::getValue('InvoiceId');
		if($invoiceId) {
			$order = new Order(
				Order::getOrderByCartId($invoiceId)
			);
			if ($order->id != NULL) {
				$history = new OrderHistory();
				$history->id_order = $order->id;
				$history->changeIdOrderState((int)Configuration::get('PS_OS_REFUND'), $history->id_order);
				$history->addWithemail();
				$history->save();

				$this->response->code = 0;
			}
		}
	}

	public function getResponse()
	{
		return json_encode($this->response);
	}


}