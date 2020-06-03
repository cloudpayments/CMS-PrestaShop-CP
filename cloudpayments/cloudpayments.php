<?php

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Cloudpayments extends PaymentModule {

    static $vat_enum = array(
        '' => '',
        0  => 0,
        10 => 10,
        20 => 20,
        110 => 110,
        120 => 120,
    );

    public function __construct() {
        $this->name                   = 'cloudpayments';
        $this->tab                    = 'payments_gateways';
        $this->version                = '1.0.2';
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
                !$this->registerHook('actionFrontControllerSetMedia') ||
                !$this->registerHook('actionOrderStatusPostUpdate')
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
            $publicId      = Tools::getValue('CLOUDPAYMENTS_PUBLICID');
            $payStage      = Tools::getValue('CLOUDPAYMENTS_PAYSTAGE');
            $apiKey        = Tools::getValue('CLOUDPAYMENTS_APIKEY');
            $kkt           = Tools::getValue('CLOUDPAYMENTS_KKT');
            $kkt_delivered = Tools::getValue('CLOUDPAYMENTS_KKT_DELIVERED');
            $taxSystem     = Tools::getValue('CLOUDPAYMENTS_TAXATION_SYSTEM');
            $skin          = Tools::getValue('CLOUDPAYMENTS_SKIN');
            $language      = Tools::getValue('CLOUDPAYMENTS_LANGUAGE');
            $currency      = Tools::getValue('CLOUDPAYMENTS_CURRENCY');
            $method        = Tools::getValue('CLOUDPAYMENTS_METHOD');
            $object        = Tools::getValue('CLOUDPAYMENTS_OBJECT');
            $inn           = Tools::getValue('CLOUDPAYMENTS_INN');

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
                        'type'  => 'string',
                        'name'  => $this->l('Skin'),
                        'value' => $skin
                    ),
                    array(
                        'type'  => 'string',
                        'name'  => $this->l('Language'),
                        'value' => $skin
                    ),
                    array(
                        'type'  => 'string',
                        'name'  => $this->l('Currency'),
                        'value' => $skin
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
                        'name'  => $this->l('Пробивать второй чек доставки'),
                        'value' => $kkt_delivered
                    ),
                    
                    array(
                        'type'  => 'number',
                        'name'  => $this->l('Taxation system'),
                        'value' => $taxSystem
                    ),
                    array(
                        'type'  => 'number',
                        'name'  => $this->l('Метод олаты'),
                        'value' => $method
                    ),
                    array(
                        'type'  => 'number',
                        'name'  => $this->l('Предмет оплаты'),
                        'value' => $object
                    ),
                )
            );

            if (count($errors)) {
                foreach ($errors as $error) {
                    $output .= $this->displayError($error);
                }
            } else {
                Configuration::updateValue('CLOUDPAYMENTS_PUBLICID', $publicId);
                Configuration::updateValue('CLOUDPAYMENTS_PAYSTAGE', $payStage);
                Configuration::updateValue('CLOUDPAYMENTS_APIKEY', $apiKey);
                Configuration::updateValue('CLOUDPAYMENTS_KKT', $kkt);
                Configuration::updateValue('CLOUDPAYMENTS_KKT_DELIVERED', $kkt_delivered);
                Configuration::updateValue('CLOUDPAYMENTS_TAXATION_SYSTEM', $taxSystem);
                Configuration::updateValue('CLOUDPAYMENTS_SKIN', $skin);
                Configuration::updateValue('CLOUDPAYMENTS_LANGUAGE', $language);
                Configuration::updateValue('CLOUDPAYMENTS_CURRENCY', $currency);
                Configuration::updateValue('CLOUDPAYMENTS_METHOD', $method);
                Configuration::updateValue('CLOUDPAYMENTS_OBJECT', $object);
                Configuration::updateValue('CLOUDPAYMENTS_INN', $inn);
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
                    'type'    => 'select',
                    'class'   => 'fixed-width-xxl',
                    'label'   => $this->l('Skin'),
                    'name'    => 'CLOUDPAYMENTS_SKIN',
                                'options' => array(
                                     'query' => array(
                                            array('id' => 'classic', 'name' => $this->l('Classic')),
                                            array('id' => 'modern', 'name' => $this->l('Modern')),
                                            array('id' => 'mini', 'name' => $this->l('Mini')),
                                     ),
                                'id'    => 'id',
                                'name'  => 'name',
                    )
                ),
                array(
                    'type'    => 'select',
                    'class'   => 'fixed-width-xxl',
                    'label'   => $this->l('Language'),
                    'name'    => 'CLOUDPAYMENTS_LANGUAGE',
                                'options' => array(
                                     'query' => array(
                                            array('id' => 'ru-RU', 'name' => $this->l('Russian')),
                                            array('id' => 'en-US', 'name' => $this->l('English')),
                                            array('id' => 'lv', 'name' => $this->l('Latvian')),
                                            array('id' => 'az', 'name' => $this->l('Azerbaijani')),
                                            array('id' => 'kk', 'name' => $this->l('Russian	ALMT')),
                                            array('id' => 'kk-KZ', 'name' => $this->l('Kazakh')),
                                            array('id' => 'uk', 'name' => $this->l('Ukrainian')),
                                            array('id' => 'pl', 'name' => $this->l('Polish')),
                                            array('id' => 'pt', 'name' => $this->l('Portuguese')),
                                            array('id' => 'cs-CZ', 'name' => $this->l('Czech')),
                                     ),
                                'id'    => 'id',
                                'name'  => 'name',
                    )
                ),
                array(
                    'type'    => 'select',
                    'class'   => 'fixed-width-xxl',
                    'label'   => $this->l('Currency'),
                    'name'    => 'CLOUDPAYMENTS_CURRENCY',
                                'options' => array(
                                     'query' => array(
                                            array('id' => 1, 'name' => $this->l('Site currency')),
                                            array('id' => 'RUB', 'name' => $this->l('Russian ruble')),
                                            array('id' => 'EUR', 'name' => $this->l('Euro')),
                                            array('id' => 'USD', 'name' => $this->l('US dollar')),
                                            array('id' => 'GBP', 'name' => $this->l('Pound sterlin')),
                                            array('id' => 'UAH', 'name' => $this->l('Ukrainian hryvnia')),
                                            array('id' => 'BYN', 'name' => $this->l('Belarusian ruble')),
                                            array('id' => 'KZT', 'name' => $this->l('Kazakh tenge')),
                                            array('id' => 'AZN', 'name' => $this->l('Azerbaijani manat')),
                                            array('id' => 'CHF', 'name' => $this->l('Swiss frank')),
                                            array('id' => 'CZK', 'name' => $this->l('Czech koruna')),
                                            array('id' => 'CAD', 'name' => $this->l('Canadian dollar')),
                                            array('id' => 'PLN', 'name' => $this->l('Polish zloty')),
                                            array('id' => 'SEK', 'name' => $this->l('Swedish crown')),
                                            array('id' => 'TRY', 'name' => $this->l('Turkish lira')),
                                            array('id' => 'CNY', 'name' => $this->l('Chinese yuan')),
                                            array('id' => 'INR', 'name' => $this->l('Indian rupee')),
                                            array('id' => 'BRL', 'name' => $this->l('Brazilian real')),
                                            array('id' => 'ZAL', 'name' => $this->l('South african rand')),
                                            array('id' => 'UZS', 'name' => $this->l('Bulgarian lev')),
                                            array('id' => 'BGL', 'name' => $this->l('Uzbek sum')),
                                            array('id' => 'GEL', 'name' => $this->l('Georgian lari')),
                                     ),
                                'id'    => 'id',
                                'name'  => 'name',
                    )
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
                    'type'   => 'switch',
                    'label'  => $this->l('Пробивать второй чек доставки'),
                    'name'   => 'CLOUDPAYMENTS_KKT_DELIVERED',
                    'values' => array(
                        array(
                            'id'    => 'kkt_delivered_active_on',
                            'value' => 1,
                        ),
                        array(
                            'id'    => 'kkt_delivered_active_off',
                            'value' => 0,
                        ),
                    )
                ),
                array(
                    'type'     => 'text',
                    'label'    => $this->l('ИНН'),
                    'name'     => 'CLOUDPAYMENTS_INN',
                    'size'     => 32,
                    'required' => false
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
                array(
                    'type'    => 'select',
                    'class'   => 'fixed-width-xxl',
                    'label'   => $this->l('Метод оплаты'),
                    'name'    => 'CLOUDPAYMENTS_METHOD',
                    'options' => array(
                        'query' => array(
                            array('id' => 0, 'name' => $this->l('Неизвестный способ расчета')),
                            array('id' => 1, 'name' => $this->l('Предоплата 100%')),
                            array('id' => 2, 'name' => $this->l('Предоплата')),
                            array('id' => 3, 'name' => $this->l('Аванс')),
                            array('id' => 4, 'name' => $this->l('Полный расчёт')),
                            array('id' => 5, 'name' => $this->l('Частичный расчёт и кредит')),
                            array('id' => 6, 'name' => $this->l('Передача в кредит')),
                            array('id' => 7, 'name' => $this->l('Оплата кредита')),
                        ),
                        'id'    => 'id',
                        'name'  => 'name',
                    )
                ),
                array(
                    'type'    => 'select',
                    'class'   => 'fixed-width-xxl',
                    'label'   => $this->l('Предмет оплаты'),
                    'name'    => 'CLOUDPAYMENTS_OBJECT',
                    'options' => array(
                        'query' => array(
                            array('id' => 0, 'name' => $this->l('Неизвестный предмет оплаты')),
                            array('id' => 1, 'name' => $this->l('Товар')),
                            array('id' => 2, 'name' => $this->l('Подакцизный товар')),
                            array('id' => 3, 'name' => $this->l('Работа')),
                            array('id' => 4, 'name' => $this->l('Услуга')),
                            array('id' => 5, 'name' => $this->l('Ставка азартной игры')),
                            array('id' => 6, 'name' => $this->l('Выигрыш азартной игры')),
                            array('id' => 7, 'name' => $this->l('Лотерейный билет')),
                            array('id' => 8, 'name' => $this->l('Выигрыш лотереи')),
                            array('id' => 9, 'name' => $this->l('Предоставление РИД')),
                            array('id' => 10, 'name' => $this->l('Платеж')),
                            array('id' => 11, 'name' => $this->l('Агентское вознаграждение')),
                            array('id' => 12, 'name' => $this->l('Составной предмет расчета')),
                            array('id' => 13, 'name' => $this->l('Иной предмет расчета')),
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
        $helper->fields_value['CLOUDPAYMENTS_PAYSTAGE']        = Configuration::get('CLOUDPAYMENTS_PAYSTAGE');
        $helper->fields_value['CLOUDPAYMENTS_APIKEY']          = Configuration::get('CLOUDPAYMENTS_APIKEY');
        $helper->fields_value['CLOUDPAYMENTS_KKT']             = Configuration::get('CLOUDPAYMENTS_KKT');
        $helper->fields_value['CLOUDPAYMENTS_KKT_DELIVERED']    = Configuration::get('CLOUDPAYMENTS_KKT_DELIVERED');
        $helper->fields_value['CLOUDPAYMENTS_TAXATION_SYSTEM'] = Configuration::get('CLOUDPAYMENTS_TAXATION_SYSTEM');
        $helper->fields_value['CLOUDPAYMENTS_SKIN']            = Configuration::get('CLOUDPAYMENTS_SKIN');
        $helper->fields_value['CLOUDPAYMENTS_LANGUAGE']        = Configuration::get('CLOUDPAYMENTS_LANGUAGE');
        $helper->fields_value['CLOUDPAYMENTS_CURRENCY']        = Configuration::get('CLOUDPAYMENTS_CURRENCY');
        $helper->fields_value['CLOUDPAYMENTS_OBJECT']          = Configuration::get('CLOUDPAYMENTS_OBJECT');
        $helper->fields_value['CLOUDPAYMENTS_METHOD']          = Configuration::get('CLOUDPAYMENTS_METHOD');
        $helper->fields_value['CLOUDPAYMENTS_INN']             = Configuration::get('CLOUDPAYMENTS_INN');

        $anotherHtml = '';
        if (Configuration::get('CLOUDPAYMENTS_APIKEY')) {
            $callbackUrl = Tools::getHttpHost(true) . __PS_BASE_URI__ . "modules/" . $this->name . "/callback.php?";
            $anotherHtml = "<div class=\"alert alert-info\">";
            $anotherHtml .= "<h4>" . $this->l('Your callbacks:') . "</h4>";
            $anotherHtml .= "<p><strong>Сheck</strong>: " .
                $callbackUrl . "callback_type=check";
            $anotherHtml .= "<p><strong>Pay</strong>: " .
                $callbackUrl . "callback_type=pay</p>";
            $anotherHtml .= "<p><strong>Fail</strong>: " .
                $callbackUrl . "callback_type=fail</p>";
            $anotherHtml .= "<p><strong>Confirm</strong>: " .
                $callbackUrl . "callback_type=confirm</p>";
            $anotherHtml .= "<p><strong>Refund</strong>: " .
                $callbackUrl . "callback_type=refund</p>";
            $anotherHtml .= "<p><strong>Cancel</strong>: " .
                $callbackUrl . "callback_type=cancel</p>";
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
        
        if (Configuration::get('CLOUDPAYMENTS_CURRENCY') == 1) {
            $rubCurrencyId = Currency::getIdByIsoCode('RUB');
            if ($cart->id_currency != $rubCurrencyId) {
                $fromCurrency = new Currency($cart->id_currency);
                $toCurrency   = new Currency($rubCurrencyId);
                $totalPay     = Tools::convertPriceFull($totalPay, $fromCurrency, $toCurrency);
            }
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

        if (Configuration::get('CLOUDPAYMENTS_CURRENCY') == 1) {
            $currency1 = 'RUB';
        }
        else $currency1 = Configuration::get('CLOUDPAYMENTS_CURRENCY');

        $this->context->smarty->assign(array(
            'totalPay'       => $totalPay,
            'publicId'       => Configuration::get('CLOUDPAYMENTS_PUBLICID'),
            'skin'           => Configuration::get('CLOUDPAYMENTS_SKIN'),
            'description'    => htmlspecialchars_decode($this->l('Payment order in shop "') . Configuration::get('PS_SHOP_NAME') . '&quot;'),
            'payType'        => Configuration::get('CLOUDPAYMENTS_PAYSTAGE') ? 'auth' : 'charge',
            'path'           => $this->_path,
            'cartId'         => $params['cart']->id,
            'redirectUrl'    => $this->context->link->getModuleLink($this->name, 'success', array()),
            'currency'       => $currency1,
            'rubCurrencyId'  => $rubCurrencyId,
            'language'       => Configuration::get('CLOUDPAYMENTS_LANGUAGE'),
            'accountId'      => $this->context->customer->email,
            'additionalData' => json_encode($additionalData)
        ));

        $this->context->controller->addJS('https://widget.cloudpayments.ru/bundles/cloudpayments?cms=PrestaShop');

        return $this->display(__FILE__, 'payment.tpl');
    }
    
    public function validateOrder(
        $id_cart,
        $id_order_state,
        $amount_paid,
        $payment_method = 'Unknown',
        $message = null,
        $extra_vars = array(),
        $currency_special = null,
        $dont_touch_amount = false,
        $secure_key = false,
        Shop $shop = null
    )
    {
        parent::validateOrder($id_cart, $id_order_state, $amount_paid,
            $payment_method, $message, $extra_vars, $currency_special,
            $dont_touch_amount, $secure_key, $shop);
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
        if (Configuration::get('CLOUDPAYMENTS_CURRENCY') == 1) {
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
        else  return true;
    }

    public function getEmbeddedPaymentOption($params) {
        $embeddedOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $embeddedOption	->setCallToActionText($this->l('Cloudpayments'))
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

        $invoiceAddress = $this->context->customer->getSimpleAddress($this->context->cart->id_address_invoice);
        $customerPhone  = isset($invoiceAddress['phone']) ? $invoiceAddress['phone'] : '';
        if (Configuration::get('CLOUDPAYMENTS_CURRENCY') == 1) {
            $currency = new Currency($cart->id_currency);
            $currency1 = $currency->iso_code; 
        }
        else $currency1 = Configuration::get('CLOUDPAYMENTS_CURRENCY');
        $widgetData     = array(
            'amount'      => $totalPay,
            'publicId'    => Configuration::get('CLOUDPAYMENTS_PUBLICID'),
            'description' => htmlspecialchars_decode($this->l('Payment order in shop "') . Configuration::get('PS_SHOP_NAME') . '&quot;'),
            'invoiceId'   => $params['cart']->id,
            'accountId'   => $this->context->customer->email,
            'currency'    => $currency1,
            'skin'        => Configuration::get('CLOUDPAYMENTS_SKIN'),
            'language'    => Configuration::get('CLOUDPAYMENTS_LANGUAGE'),
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
    
    public function hookActionOrderStatusPostUpdate($params) {
        
	$order_id = $params['id_order'];
	$order =new Order($order_id);
	$cart_id = $order-> id_cart;
	$delivered_id = Configuration::get('PS_OS_DELIVERED');
    $newOrderStatus = $params['newOrderStatus']->id;

    if (Configuration::get('CLOUDPAYMENTS_KKT') && Configuration::get('CLOUDPAYMENTS_KKT_DELIVERED') && $newOrderStatus == $delivered_id && 
        (Configuration::get('CLOUDPAYMENTS_METHOD') == 1 || Configuration::get('CLOUDPAYMENTS_METHOD') ==2 || Configuration::get('CLOUDPAYMENTS_METHOD') == 3)) {
            
            $cart = new Cart($cart_id);
            $customer = new Customer((int)$cart->id_customer);
            $email = $customer->email;
            $totalPay = $cart->getOrderTotal(true);
            $taxSystem   = Configuration::get('CLOUDPAYMENTS_TAXATION_SYSTEM');
            $receiptData = array(
                "Inn"=> Configuration::get('CLOUDPAYMENTS_INN'),
                "InvoiceId"=> $cart_id,
                "Type"=> "Income",
                "CustomerReceipt"=>array(
                    'taxationSystem'  => $taxSystem,
                    'calculationPlace'=> $_SERVER['SERVER_NAME'],
                    'email'           => $email,
                    "amounts"         => array("advancePayment" => $totalPay),
		            'Items'           => array(),
                )
            );

            $products = $cart->getProducts();
            foreach ($products as $product) {
                $receiptData['CustomerReceipt']['Items'][] = array(
                    'label'    => $product['name'] . ' ' . $product['attributes_small'],
                    'price'    => sprintf('%0.2F', $product['price_wt']),
                    'quantity' => floatval($product['quantity']),
                    'amount'   => sprintf('%0.2F', $product['total_wt']),
                    'vat'      => $this->getVatValue($product['rate']),
                    'method'   => 4,
                    'object'   => Configuration::get('CLOUDPAYMENTS_OBJECT'),
                );
            }
            $deliveryCost = $cart->getOrderTotal(true, Cart::ONLY_SHIPPING);
            if ($deliveryCost > 0) {
                $carrier                = new Carrier($cart->id_carrier);
                $address                = new Address($cart->id_address_delivery);
                $receiptData['CustomerReceipt']['Items'][] = array(
                    'label'    => $this->l('Delivery'),
                    'price'    => sprintf('%0.2F', $deliveryCost),
                    'quantity' => 1,
                    'amount'   => sprintf('%0.2F', $deliveryCost),
                    'vat'      => $this->getVatValue($carrier->getTaxesRate($address)),
                    'method'   => 4,
                    'object'   => 4,
                );
            }
            
           	$ch = curl_init("https://api.cloudpayments.ru/kkt/receipt");
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, Configuration::get('CLOUDPAYMENTS_PUBLICID') . ":" . Configuration::get('CLOUDPAYMENTS_APIKEY'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($receiptData, JSON_UNESCAPED_UNICODE));
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
        }
    }
    
    private function getReceiptData($customerPhone) {
        $cart        = $this->context->cart;
        $totalPay = $cart->getOrderTotal(true);
        $taxSystem   = Configuration::get('CLOUDPAYMENTS_TAXATION_SYSTEM');
        $receiptData = array(
            'Items'           => array(),
            'taxationSystem'  => $taxSystem,
            'calculationPlace'=> $_SERVER['SERVER_NAME'],
            'email'           => $this->context->customer->email,
            'phone'           => $customerPhone,
            "amounts"         => array("electronic" => $totalPay)
        );

        $products = $cart->getProducts();
        foreach ($products as $product) {
            $receiptData['Items'][] = array(
                'label'    => $product['name'] . ' ' . $product['attributes_small'],
                'price'    => sprintf('%0.2F', $product['price_wt']),
                'quantity' => floatval($product['quantity']),
                'amount'   => sprintf('%0.2F', $product['total_wt']),
                'vat'      => $this->getVatValue($product['rate']),
                'method'   => Configuration::get('CLOUDPAYMENTS_METHOD'),
                'object'   => Configuration::get('CLOUDPAYMENTS_OBJECT'),
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
                'vat'      => $this->getVatValue($carrier->getTaxesRate($address)),
                'method'   => Configuration::get('CLOUDPAYMENTS_METHOD'),
                'object'   => 4,
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

            $widgetDomain = 'widget.cloudpayments.ru';
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