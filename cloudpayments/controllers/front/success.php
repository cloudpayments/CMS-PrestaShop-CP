<?php
/**
* Controller for confirm order
*/
class CloudpaymentsSuccessModuleFrontController extends ModuleFrontController
{
	public function initContent()
	{
		parent::initContent();

		if ($this->context->cart->id_customer == 0 || $this->context->cart->id_address_delivery == 0 || $this->context->cart->id_address_invoice == 0) {
			Tools::redirectLink(__PS_BASE_URI__.'order.php?step=1');
		}

		// Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
		$authorized = false;
		foreach (Module::getPaymentModules() as $module)
			if ($module['name'] == 'cloudpayments')
			{
				$authorized = true;
				break;
			}
		if (!$authorized)
			die(Tools::displayError('This payment method is not available.'));

		$customer = new Customer((int)$this->context->cart->id_customer);
		$total = $this->context->cart->getOrderTotal(true, Cart::BOTH);
		$this->module->validateOrder((int)$this->context->cart->id, Configuration::get('PS_OS_PREPARATION'), $total, $this->module->displayName, null, array(), null, false, $customer->secure_key);
		Tools::redirectLink(__PS_BASE_URI__.'order-confirmation.php?key='.$customer->secure_key.'&id_cart='.(int)$this->context->cart->id.'&id_module='.(int)$this->module->id.'&id_order='.(int)$this->module->currentOrder);
	}
}
