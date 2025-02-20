<?php
/**
* 2007-2025 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2025 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Descuentos_tras_compra extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'descuentos_tras_compra';
        $this->tab = 'seo';
        $this->version = '1.0.0';
        $this->author = 'Finca Canarias - Airan';
        $this->need_instance = 1;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Descuentos tras compra');
        $this->description = $this->l('Descuentos tras compra');

        $this->confirmUninstall = $this->l('');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '8.0');
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('DESCUENTOS_TRAS_COMPRA_LIVE_MODE', false);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('actionValidateOrder') ;

    }

    public function uninstall()
    {
        Configuration::deleteByName('DESCUENTOS_TRAS_COMPRA_LIVE_MODE');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitDescuentos_tras_compraModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitDescuentos_tras_compraModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'DESCUENTOS_TRAS_COMPRA_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Enter a valid email address'),
                        'name' => 'DESCUENTOS_TRAS_COMPRA_ACCOUNT_EMAIL',
                        'label' => $this->l('Email'),
                    ),
                    array(
                        'type' => 'password',
                        'name' => 'DESCUENTOS_TRAS_COMPRA_ACCOUNT_PASSWORD',
                        'label' => $this->l('Password'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'DESCUENTOS_TRAS_COMPRA_LIVE_MODE' => Configuration::get('DESCUENTOS_TRAS_COMPRA_LIVE_MODE', true),
            'DESCUENTOS_TRAS_COMPRA_ACCOUNT_EMAIL' => Configuration::get('DESCUENTOS_TRAS_COMPRA_ACCOUNT_EMAIL', 'contact@prestashop.com'),
            'DESCUENTOS_TRAS_COMPRA_ACCOUNT_PASSWORD' => Configuration::get('DESCUENTOS_TRAS_COMPRA_ACCOUNT_PASSWORD', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }
    public function hookActionValidateOrder($params)
    {
        $order = $params['order'];
        $customer = new Customer((int) $order->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            error_log('Cliente no válido'); // 🔍 Debug

            return;
        }

        $orderState = new OrderState((int) $order->id_order_state);
        $orderStateId = $order->current_state; // ID del estado

        if ($orderStateId == Configuration::get('PS_OS_PAYMENT') || $orderState == Configuration::get('PS_OS_PREPARATION') || $orderState == 10 || $orderStateId == 10) {

            $discountCode = 'DESC' . strtoupper(Tools::passwdGen(8));
            error_log('Código generado: ' . $discountCode); // 🔍 Debug

            $cartRule = new CartRule();
            $cartRule->code = $discountCode;
            $cartRule->name = ['1' => 'Descuento especial'];
            $cartRule->id_customer = (int) $customer->id;
            $cartRule->date_from = date('Y-m-d H:i:s');
            $cartRule->date_to = date('Y-m-d H:i:s', strtotime('+30 days'));
            $cartRule->quantity = 1;
            $cartRule->quantity_per_user = 1;
            $cartRule->reduction_percent = 5; 
            $cartRule->active = 1;
            $cartRule->add();

            if (!$cartRule->add()) {
                error_log('Error al crear el código de descuento'); // 🔍 Debug
            }

            $result = Mail::Send(
                (int) $order->id_lang,
                'discount_email',
                $this->l('¡Tu código de descuento!'),
                [
                    '{firstname}' => $customer->firstname,
                    '{discount_code}' => $discountCode
                ],
                $customer->email,
                $customer->firstname . ' ' . $customer->lastname,
                null,
                null,
                null,
                null,
                _PS_MODULE_DIR_ . 'descuentos_tras_compra/mails/es/discount_email.html'
            );
            if (!$result) {
                error_log('❌ Error al enviar el email');
            } else {
                $errorMessage = error_get_last(); // Obtiene el último error PHP
                $logMessage = '❌ Error al enviar el email: ' . print_r($errorMessage, true);
                error_log($logMessage); // Guarda en logs de PHP
                die($logMessage); // Detiene la ejecución y muestra el error en pantalla
            }
            error_log('El pedido SALE EN ID ' . $orderStateId); // 🔍 Debug
            error_log('El pedido SALE COMO ID ' . $orderState); // 🔍 Debug
        }
        else
        {
            error_log('El pedido no esta pagado'); // 🔍 Debug
            error_log('El pedido SALE EN ID ' . $orderStateId); // 🔍 Debug
            error_log('El pedido SALE COMO ID ' . $orderState); // 🔍 Debug

        }
    }

    
}
