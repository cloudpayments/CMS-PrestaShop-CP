<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Cloudpayments extends PaymentModule {

    static $vat_enum = array(
        '' => '',
        0  => 0,
        10 => 10,
        18 => 18,
    );

    public function __construct() {
        $this->name                   = 'cloudpayments';
        $this->tab                    = 'payments_gateways';
        $this->version                = '1.0.0';
        $this->author                 = 'ipnino.ru';
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap              = true;
        $this->currencies             = true;
        $this->currencies_mode        = 'checkbox';

        parent::__construct();

        $this->displayName = $this->l('Cloudpayments');
        $this->description = $this->l('Make online payment in your shop');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
        if (!Configuration::get('CLOUDPAYMENTS_PUBLICID')) {
            $this->warning = $this->l('Please, do module configuration');
        }
    }

    public function install() {
        if (!parent::install()) {
            return false;
        }

        if (Tools::version_compare(_PS_VERSION_, '1.7', '>=')) {
            if (!$this->registerHook('paymentOptions') ||
                !$this->registerHook('actionFrontControllerSetMedia')
            ) {
                return false;
            }
        } else {
            if (!$this->registerHook('payment')) {
                return false;
            }
        }

        return true;
    }

    public function uninstall() {
        return parent::uninstall();
    }

    public function getContent() {
        $output = null;
        if (Tools::isSubmit('submit' . $this->name)) {
            $publicId  = Tools::getValue('CLOUDPAYMENTS_PUBLICID');
            $region    = Tools::getValue('CLOUDPAYMENTS_REGION');
            $payStage  = Tools::getValue('CLOUDPAYMENTS_PAYSTAGE');
            $apiKey    = Tools::getValue('CLOUDPAYMENTS_APIKEY');
            $kkt       = Tools::getValue('CLOUDPAYMENTS_KKT');
            $taxSystem = Tools::getValue('CLOUDPAYMENTS_TAXATION_SYSTEM');

            $errors = $this->validate(
                array(
                    array(
                        'type'  => 'string',
                        'name'  => $this->l('Public ID'),
                        'value' => $publicId
                    ),
                    array(
                        'type'  => 'string',
                        'name'  => $this->l('Payment password'),
                        'value' => $apiKey
                    ),
                    array(
                        'type'  => 'number',
                        'name'  => $this->l('Region'),
                        'value' => $region
                    ),
                    array(
                        'type'  => 'number',
                        'name'  => $this->l('Pay type'),
                        'value' => $payStage
                    ),
                    array(
                        'type'  => 'number',
                        'name'  => $this->l('Online receipt'),
                        'value' => $kkt
                    ),
                    array(
                        'type'  => 'number',
                        'name'  => $this->l('Taxation system'),
                        'value' => $taxSystem
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
                Configuration::updateValue('CLOUDPAYMENTS_KKT', $kkt);
                Configuration::updateValue('CLOUDPAYMENTS_TAXATION_SYSTEM', $taxSystem);
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }

        return $output . $this->displayForm();
    }

    private function validate($data) {
        $errors = array();
        foreach ($data as $item) {
            switch ($item['type']) {
                case 'string':
                    if (!$item['value']) {
                        $errors[] = sprintf($this->l('Field %s must be non empty'), $item['name']);
                    }
                    break;
                case 'number':
                    if (!is_numeric($item['value'])) {
                        $errors[] = sprintf($this->l('Field %s must be a number'), $item['name']);
                    }
                    break;
            }
        }

        return $errors;
    }

    public function displayForm() {
        // Get default language
        $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');

        // Init Fields form array
        $fieldsForm[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings'),
            ),
            'input'  => array(
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
                    'type'     => 'radio',
                    'label'    => $this->l('Region'),
                    'name'     => 'CLOUDPAYMENTS_REGION',
                    'values'   => array(
                        array(
                            'id'    => 'region_active_on',
                            'value' => 0,
                            'label' => $this->l('Russia')
                        ),
                        array(
                            'id'    => 'region_active_off',
                            'value' => 1,
                            'label' => $this->l('Kazakhstan')
                        )
                    ),
                    'required' => true
                ),
                array(
                    'type'     => 'radio',
                    'label'    => $this->l('Pay type'),
                    'name'     => 'CLOUDPAYMENTS_PAYSTAGE',
                    'values'   => array(
                        array(
                            'id'    => 'pay_type_active_on',
                            'value' => 0,
                            'label' => $this->l('one-step')
                        ),
                        array(
                            'id'    => 'pay_type_active_off',
                            'value' => 1,
                            'label' => $this->l('two-step')
                        )
                    ),
                    'required' => true
                ),
                array(
                    'type'   => 'switch',
                    'label'  => $this->l('Online receipt'),
                    'name'   => 'CLOUDPAYMENTS_KKT',
                    'values' => array(
                        array(
                            'id'    => 'kkt_active_on',
                            'value' => 1,
                        ),
                        array(
                            'id'    => 'kkt_active_off',
                            'value' => 0,
                        ),
                    )
                ),
                array(
                    'type'    => 'select',
                    'class'   => 'fixed-width-xxl',
                    'label'   => $this->l('Taxation system'),
                    'name'    => 'CLOUDPAYMENTS_TAXATION_SYSTEM',
                    'options' => array(
                        'query' => array(
                            array('id' => 0, 'name' => $this->l('General taxation system')),
                            array('id' => 1, 'name' => $this->l('Simplified taxation system (Income)')),
                            array('id' => 2, 'name' => $this->l('Simplified taxation system (Income minus Expenditure)')),
                            array('id' => 3, 'name' => $this->l('A single tax on imputed income')),
                            array('id' => 4, 'name' => $this->l('Unified agricultural tax')),
                            array('id' => 5, 'name' => $this->l('Patent system of taxation')),
                        ),
                        'id'    => 'id',
                        'name'  => 'name',
                    )
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            )
        );

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module          = $this;
        $helper->name_controller = $this->name;
        $helper->token           = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex    = AdminController::$currentIndex . '&configure=' . $this->name;

        // Language
        $helper->default_form_language    = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;

        // Title and toolbar
        $helper->title          = $this->displayName;
        $helper->show_toolbar   = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action  = 'submit' . $this->name;
        $helper->toolbar_btn    = array(
            'save' =>
                array(
                    'desc' => $this->l('Save'),
                    'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name .
                        '&token=' . Tools::getAdminTokenLite('AdminModules'),
                ),
            'back' => array(
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );

        // Load current value
        $helper->fields_value['CLOUDPAYMENTS_PUBLICID']        = Configuration::get('CLOUDPAYMENTS_PUBLICID');
        $helper->fields_value['CLOUDPAYMENTS_REGION']          = Configuration::get('CLOUDPAYMENTS_REGION');
        $helper->fields_value['CLOUDPAYMENTS_PAYSTAGE']        = Configuration::get('CLOUDPAYMENTS_PAYSTAGE');
        $helper->fields_value['CLOUDPAYMENTS_APIKEY']          = Configuration::get('CLOUDPAYMENTS_APIKEY');
        $helper->fields_value['CLOUDPAYMENTS_KKT']             = Configuration::get('CLOUDPAYMENTS_KKT');
        $helper->fields_value['CLOUDPAYMENTS_TAXATION_SYSTEM'] = Configuration::get('CLOUDPAYMENTS_TAXATION_SYSTEM');

        $anotherHtml = '';
        if (Configuration::get('CLOUDPAYMENTS_APIKEY')) {
            $callbackUrl = Tools::getHttpHost(true) . __PS_BASE_URI__ . "modules/" . $this->name . "/callback.php?";
            $anotherHtml = "<div class=\"alert alert-info\">";
            $anotherHtml .= "<h4>" . $this->l('Your callbacks:') . "</h4>";
            $anotherHtml .= "<p><strong>Сheck</strong>: " .
                $callbackUrl . "callback_type=check";
            $anotherHtml .= "<p><strong>Pay</strong>: " .
                $callbackUrl . "callback_type=pay</p>";
            $anotherHtml .= "<p><strong>Refund</strong>: " .
                $callbackUrl . "callback_type=refund</p>";
            $anotherHtml .= "</div>";
        }

        return $helper->generateForm($fieldsForm) . $anotherHtml;
    }

    public function hookPayment($params) {

        if (!Configuration::get('CLOUDPAYMENTS_PUBLICID')) {
            return;
        }

        $cart          = $this->context->cart;
        $totalPay      = $cart->getOrderTotal(true);
        $rubCurrencyId = Currency::getIdByIsoCode('RUB');
        if ($cart->id_currency != $rubCurrencyId) {
            $fromCurrency = new Currency($cart->id_currency);
            $toCurrency   = new Currency($rubCurrencyId);
            $totalPay     = Tools::convertPriceFull($totalPay, $fromCurrency, $toCurrency);
        }

        $invoiceAddress = new Address($this->context->cart->id_address_invoice);
        $customerPhone  = $invoiceAddress ? $invoiceAddress->phone : '';
        $additionalData = array(
            'name'          => trim($this->context->customer->firstname . ' ' . $this->context->customer->lastname),
            'phone'         => $customerPhone,
            'cloudPayments' => array(),
        );
        if (Configuration::get('CLOUDPAYMENTS_KKT')) {
            $additionalData['cloudPayments']['customerReceipt'] = $this->getReceiptData($customerPhone);
        }

        $this->context->smarty->assign(array(
            'totalPay'       => $totalPay,
            'rubCurrencyId'  => $rubCurrencyId,
            'publicId'       => Configuration::get('CLOUDPAYMENTS_PUBLICID'),
            'description'    => htmlspecialchars_decode($this->l('Payment order in shop "') . Configuration::get('PS_SHOP_NAME') . '&quot;'),
            'payType'        => Configuration::get('CLOUDPAYMENTS_PAYSTAGE') ? 'auth' : 'charge',
            'path'           => $this->_path,
            'cartId'         => $params['cart']->id,
            'redirectUrl'    => $this->context->link->getModuleLink($this->name, 'success', array()),
            'currency'       => 'RUB',
            'accountId'      => $this->context->customer->email,
            'additionalData' => json_encode($additionalData)
        ));

        $widgetDomain = 'widget.cloudpayments.ru';

        if (Configuration::get('CLOUDPAYMENTS_REGION')) {
            $widgetDomain = 'widget.cloudpayments.kz';
        }

        $this->context->controller->addJS('https://' . $widgetDomain . '/bundles/cloudpayments');

        return $this->display(__FILE__, 'payment.tpl');
    }

    public function hookPaymentOptions($params) {
        if (!Configuration::get('CLOUDPAYMENTS_PUBLICID')) {
            return;
        }
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }
        $payment_options = array(
            $this->getEmbeddedPaymentOption($params),
        );

        return $payment_options;
    }

    public function checkCurrency($cart) {
        $currency_order    = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);
        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getEmbeddedPaymentOption($params) {
        $embeddedOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $embeddedOption->setCallToActionText($this->l('Cloudpayments'))
                       ->setForm($this->generateEmbeddedForm($params))
                       ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/logo_45x45.png'));

        return $embeddedOption;
    }

    /**
     * For PrestaShop 1.7
     *
     * @param $params
     * @return string
     */
    protected function generateEmbeddedForm($params) {
        /** @var Cart $cart */
        $cart     = $this->context->cart;
        $totalPay = $cart->getOrderTotal(true);
        $currency = new Currency($cart->id_currency);

        $invoiceAddress = $this->context->customer->getSimpleAddress($this->context->cart->id_address_invoice);
        $customerPhone  = isset($invoiceAddress['phone']) ? $invoiceAddress['phone'] : '';
        $widgetData     = array(
            'amount'      => $totalPay,
            'publicId'    => Configuration::get('CLOUDPAYMENTS_PUBLICID'),
            'description' => htmlspecialchars_decode($this->l('Payment order in shop "') . Configuration::get('PS_SHOP_NAME') . '&quot;'),
            'invoiceId'   => $params['cart']->id,
            'accountId'   => $this->context->customer->email,
            'currency'    => $currency->iso_code,
            'data'        => array(
                'name'          => trim($this->context->customer->firstname . ' ' . $this->context->customer->lastname),
                'phone'         => $customerPhone,
                'cloudPayments' => array(),
            )
        );

        if (Configuration::get('CLOUDPAYMENTS_KKT')) {
            $widgetData['data']['cloudPayments']['customerReceipt'] = $this->getReceiptData($customerPhone);
        }

        $this->context->smarty->assign(array(
            'widgetData' => json_encode($widgetData),
            'payType'    => Configuration::get('CLOUDPAYMENTS_PAYSTAGE') ? 'auth' : 'charge',
            'successUrl' => $this->context->link->getModuleLink($this->name, 'success', array()),
        ));

        return $this->context->smarty->fetch('module:cloudpayments/views/templates/front/payment_form.tpl');
    }

    private function getVatValue($vat) {
        if ($vat !== '') {
            $vat = intval($vat);
        }

        return isset(self::$vat_enum[$vat]) ? self::$vat_enum[$vat] : '';
    }

    private function getReceiptData($customerPhone) {
        $cart        = $this->context->cart;
        $taxSystem   = Configuration::get('CLOUDPAYMENTS_TAXATION_SYSTEM');
        $receiptData = array(
            'Items'          => array(),
            'taxationSystem' => $taxSystem,
            'email'          => $this->context->customer->email,
            'phone'          => $customerPhone
        );

        $products = $cart->getProducts();
        foreach ($products as $product) {
            $receiptData['Items'][] = array(
                'label'    => $product['name'] . ' ' . $product['attributes_small'],
                'price'    => sprintf('%0.2F', $product['price_wt']),
                'quantity' => floatval($product['quantity']),
                'amount'   => sprintf('%0.2F', $product['total_wt']),
                'vat'      => $this->getVatValue($product['rate'])
            );
        }
        $deliveryCost = $this->context->cart->getOrderTotal(true, Cart::ONLY_SHIPPING);
        if ($deliveryCost > 0) {
            $carrier                = new Carrier($this->context->cart->id_carrier);
            $address                = new Address($this->context->cart->id_address_delivery);
            $receiptData['Items'][] = array(
                'label'    => $this->l('Delivery'),
                'price'    => sprintf('%0.2F', $deliveryCost),
                'quantity' => 1,
                'amount'   => sprintf('%0.2F', $deliveryCost),
                'vat'      => $this->getVatValue($carrier->getTaxesRate($address))
            );
        }

        return $receiptData;
    }

    /**
     * For PrestaShop 1.7
     *
     * @param $params
     */
    public function hookActionFrontControllerSetMedia($params) {
        if ('order' === $this->context->controller->php_self) {
            //<script> in smarty template removed, so make params in forms and get values in JS
            $widgetDomain = 'widget.cloudpayments.ru';

            if (Configuration::get('CLOUDPAYMENTS_REGION')) {
                $widgetDomain = 'widget.cloudpayments.kz';
            }
            $this->context->controller->registerJavascript('cloudpayments_script',
                'https://' . $widgetDomain . '/bundles/cloudpayments', array(
                    'priority' => 200,
                    'server'   => 'remote'
                ));
            $this->context->controller->registerJavascript('cloudpayments_widget',
                'modules/' . $this->name . '/views/js/widget.js', array(
                    'priority' => 210,
                    //'inline' => true
                ));
        }
    }
}