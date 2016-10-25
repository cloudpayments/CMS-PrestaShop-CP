<?php   
if (!defined('_PS_VERSION_'))
  exit;

class Cloudpayments extends PaymentModule
{
	public function __construct()
	{
		$this->name = 'cloudpayments';
		$this->tab = 'payments_gateways';
		$this->version = '1.0.0';
		$this->author = 'ipnino.ru';
		$this->need_instance = 0;
		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
		$this->bootstrap = true;

		parent::__construct();

		$this->displayName = $this->l('Cloudpayments');
		$this->description = $this->l('Make online payment in your shop');

		$this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
		if (!Configuration::get('CLOUDPAYMENTS_PUBLICID'))      
      		$this->warning = $this->l('Please, do module configuration');
    }

    public function install()
	{
		if (
			!parent::install() || 
			!$this->registerHook('payment')	
		) {
			return false;
		}
		return true;
	}

	public function uninstall()
	{
		return parent::uninstall();
	}

	public function getContent() 
	{
		$output = null;
		if (Tools::isSubmit('submit'.$this->name)) {
			$publicId    = Tools::getValue('CLOUDPAYMENTS_PUBLICID');
			$region      = Tools::getValue('CLOUDPAYMENTS_REGION');
			$payStage    = Tools::getValue('CLOUDPAYMENTS_PAYSTAGE');
			$apiKey      = Tools::getValue('CLOUDPAYMENTS_APIKEY');

			$errors = $this->validate(
				array(
					array(
						'type'    => 'string',
						'name'    => $this->l('Public ID'),
						'value'	  => $publicId
					),
					array(
						'type'    => 'string',
						'name'    => $this->l('Payment password'),
						'value'	  => $apiKey
					),
					array(
						'type'  => 'number',
						'name'    => $this->l('Region'),
						'value'	=> $region
					),
					array(
						'type'  => 'number',
						'name'    => $this->l('Pay type'),
						'value'	=> $payStage
					),
				)
			);

			if (count($errors)) {
				foreach ($errors as $error) {
					$output .= $this->displayError($error);
				}
			} else {
				 Configuration::updateValue('CLOUDPAYMENTS_PUBLICID', $publicId);
				 Configuration::updateValue('CLOUDPAYMENTS_REGION', $region);
				 Configuration::updateValue('CLOUDPAYMENTS_PAYSTAGE', $payStage);
				 Configuration::updateValue('CLOUDPAYMENTS_APIKEY', $apiKey);
				 $output .= $this->displayConfirmation($this->l('Settings updated'));
			}
		} 
		return $output.$this->displayForm();
	}

	private function validate($data)
	{
		$errors = array();
		foreach ($data as $item) {
			switch ($item['type']) {
				case 'string':
					if (!$item['value']) {
						$errors[] = $this->l('Field "'.$item['name'].'" must be non empty');
					}
				break;
				case 'number':
					if (!is_numeric($item['value'])) {
						$errors[] = $this->l('Field "'.$item['name'].'" must be a number');
					}
				break;
			}
		}
		return $errors;
	}

	public function displayForm()
	{
		// Get default language
    	$defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');

    	// Init Fields form array
    	$fieldsForm[0]['form'] = array(
    		'legend' => array(
            	'title' => $this->l('Settings'),
        	),
	        'input' => array(
	        	array(
	        		'type'     => 'text',
	        		'label'    => $this->l('Public ID'),
	        		'name'     => 'CLOUDPAYMENTS_PUBLICID',
	        		'size'     => 32,
	                'required' => true
	            ),
	            array(
	        		'type'     => 'text',
	        		'label'    => $this->l('API password'),
	        		'name'     => 'CLOUDPAYMENTS_APIKEY',
	        		'size'     => 32,
	                'required' => true
	            ),
	        	array(
	        		'type'    => 'radio',
	        		'label'   => $this->l('Region'),
	        		'name'    => 'CLOUDPAYMENTS_REGION',
	        		'values'  => array(
	        			array(
	        				'id' => 'active_on',
	        				'value' => 0,
	        				'label' => $this->l('Russia')
	        			),
	        			array(
	        				'id' => 'active_off',
	        				'value' => 1,
	        				'label' => $this->l('Kazakhstan')
	        			)
	        		),
	                'required' => true
	            ),
	            array(
	        		'type'    => 'radio',
	        		'label'   => $this->l('Pay type'),
	        		'name'    => 'CLOUDPAYMENTS_PAYSTAGE',
	        		'values'  => array(
	        			array(
	        				'id' => 'active_on',
	        				'value' => 0,
	        				'label' => $this->l('one-step')
	        			),
	        			array(
	        				'id' => 'active_off',
	        				'value' => 1,
	        				'label' => $this->l('two-step')
	        			)
	        		),
	                'required' => true
	            )
	        ),
	        'submit' => array(
	            'title' => $this->l('Save'),
	            'class' => 'btn btn-default pull-right'
	        )
    	);

    	$helper = new HelperForm();

    	// Module, token and currentIndex
    	$helper->module = $this;
    	$helper->name_controller = $this->name;
    	$helper->token = Tools::getAdminTokenLite('AdminModules');
    	$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

    	// Language
    	$helper->default_form_language = $defaultLang;
   		$helper->allow_employee_form_lang = $defaultLang;

   		// Title and toolbar
	    $helper->title = $this->displayName;
	    $helper->show_toolbar = true;        // false -> remove toolbar
	    $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
	    $helper->submit_action = 'submit'.$this->name;
	    $helper->toolbar_btn = array(
	        'save' =>
	        array(
	            'desc' => $this->l('Save'),
	            'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
	            '&token='.Tools::getAdminTokenLite('AdminModules'),
	        ),
	        'back' => array(
	            'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
	            'desc' => $this->l('Back to list')
	        )
	    );
	     
	    // Load current value
	    $helper->fields_value['CLOUDPAYMENTS_PUBLICID']  = Configuration::get('CLOUDPAYMENTS_PUBLICID');
	    $helper->fields_value['CLOUDPAYMENTS_REGION']    = Configuration::get('CLOUDPAYMENTS_REGION');
	    $helper->fields_value['CLOUDPAYMENTS_PAYSTAGE']  = Configuration::get('CLOUDPAYMENTS_PAYSTAGE');
	    $helper->fields_value['CLOUDPAYMENTS_APIKEY']    = Configuration::get('CLOUDPAYMENTS_APIKEY');
	    

	    $anotherHtml = '';
	    if (Configuration::get('CLOUDPAYMENTS_APIKEY')) {
	    	$callbackUrl = Tools::getHttpHost(true).__PS_BASE_URI__."modules/".$this->name."/callback.php?";
			$anotherHtml = "<div class=\"alert alert-info\">";
	    	$anotherHtml .= "<h4>".$this->l('Your callbacks:')."</h4>";
	    	$anotherHtml .= "<p><strong>Сheck</strong>: ".
	    		$callbackUrl."callback_type=check";
	    	$anotherHtml .= "<p><strong>Pay</strong>: ".
	    		$callbackUrl."callback_type=pay</p>";
	    	$anotherHtml .= "<p><strong>Refund</strong>: ".
	    		$callbackUrl."callback_type=refund</p>";
	    	$anotherHtml .= "</div>";
	    }
	    

	    return $helper->generateForm($fieldsForm).$anotherHtml;
	}

	public function hookPayment($params)
    {

    	if (!Configuration::get('CLOUDPAYMENTS_PUBLICID')) {
    		return;
    	}

		$cart = $this->context->cart;
		$totalPay = $cart->getOrderTotal(true);
		$rubCurrencyId = Currency::getIdByIsoCode('RUB');
		if ($cart->id_currency != $rubCurrencyId) {
            $fromCurrency = new Currency($cart->id_curre1ncy);
            $toCurrency = new Currency($rubCurrencyId);
            $totalPay = Tools::convertPriceFull($totalPay, $fromCurrency, $toCurrency);
        }

		$this->context->smarty->assign(array(
			'totalPay'      => $totalPay,
			'rubCurrencyId' => $rubCurrencyId,
			'publicId'      => Configuration::get('CLOUDPAYMENTS_PUBLICID'),
			'description'   => htmlspecialchars_decode($this->l('Payment order in shop "').Configuration::get('PS_SHOP_NAME').'&quot;'),
			'payType'		=> Configuration::get('CLOUDPAYMENTS_PAYSTAGE') ? 'auth' : 'charge',
			'path'          => $this->_path,
			'cartId'        => $params['cart']->id,
			'redirectUrl'   => $this->context->link->getModuleLink($this->name,'success', array()),
			'currency'      => 'RUB'
		));

		$widgetDomain = 'widget.cloudpayments.ru';

		if (Configuration::get('CLOUDPAYMENTS_REGION')) {
			$widgetDomain = 'widget.cloudpayments.kz';
		}

		$this->context->controller->addJS('https://'.$widgetDomain.'/bundles/cloudpayments');

		return $this->display(__FILE__, 'payment.tpl');	
    }

}