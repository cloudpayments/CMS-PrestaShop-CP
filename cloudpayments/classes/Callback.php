<?php
/**
* Controller for process cloudpayments callbacks
*/
class CloudpaymentsCallback 
{
	private $response;

	public function __construct($callbackType) {
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
	private function init($callbackType) {
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
			
			case 'confirm':
            	$this->confirm();
            break;

			case 'refund':
				$this->refund();
			break;

            case 'cancel':
            	$this->cancel();
            break;
		}
	}

	/**
	*  Check callback
	*  @return void
	*/
	private function check() {
		$invoiceId = Tools::getValue('InvoiceId');
		$amount    = Tools::getValue('Amount');
		$currency  = Tools::getValue('Currency');
		if($invoiceId && $amount && $currency) {
			$cart = new Cart($invoiceId);
			if ($cart) {
			    if (Configuration::get('CLOUDPAYMENTS_CURRENCY') == 1) {
			        if ($cart->getOrderTotal(true) == $amount && Currency::getIdByIsoCode($currency) == $cart->id_currency) {
					    $this->response->code = 0;
				    }
			    }
			    else {
				    if ($cart->getOrderTotal(true) == $amount && Configuration::get('CLOUDPAYMENTS_CURRENCY') == $currency) {
					    $this->response->code = 0;
				    }
			    }
			}
		}
	}

    /**
	*  Pay callback
	*  @return void
	*/
	private function pay() {
		$invoiceId = Tools::getValue('InvoiceId');
		$amount    = Tools::getValue('Amount');
		$currency  = Tools::getValue('Currency');
		if($invoiceId && $amount && $currency) {
			$cart = new Cart($invoiceId);
			if ($cart) {
			    if (Configuration::get('CLOUDPAYMENTS_CURRENCY') == 1) {
			        if ($cart->getOrderTotal(true) == $amount && Currency::getIdByIsoCode($currency) == $cart->id_currency) {
					    $this->response->code = 0;
				    }
			    }
			    else {
				    if ($cart->getOrderTotal(true) == $amount && Configuration::get('CLOUDPAYMENTS_CURRENCY') == $currency) {
					    $this->response->code = 0;
				    }
			    }
			}
		}
	}

    /**
	*  Fail callback
	*  @return void
	*/
	private function fail(){
        $this->response->code = 0;
	}

    /**
	*  Confirm callback
	*  @return void
	*/
	private function confirm() {
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

        		$this->response->code = 0;
        	}
        }
	}

    private function refund() {
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
	/**
	*  Cancel callback
	*  @return void
	*/
	private function cancel(){
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
	
	/**
	*  Check signature
	*  @return bool
	*/
	private function checkSignature() {
		$headers   = getallheaders();
		if (!isset($headers['Content-HMAC']) && !isset($headers['Content-Hmac'])) {
		    return false;
        }
		$signature = base64_encode(
			hash_hmac(
				'SHA256',
				file_get_contents('php://input'), 
				Configuration::get('CLOUDPAYMENTS_APIKEY'), 
				true)
		);

		if ($headers['Content-HMAC'] == $signature) {
		    return true;}
        else if($headers['Content-Hmac'] == $signature) return true;   
	}

	public function getResponse()
	{
		return json_encode($this->response);
	}

}