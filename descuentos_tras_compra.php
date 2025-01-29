<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class DescuentosTrasCompra extends Module
{
    public function __construct()
    {
        $this->name = 'micodigodescuento';
        $this->tab = 'pricing_promotion';
        $this->version = '1.0.0';
        $this->author = 'Tu Nombre';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('Generador de Código de Descuento tras compra');
        $this->description = $this->l('Crea un código de descuento tras la compra.');

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install() && $this->registerHook('actionValidateOrder');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }
    public function getContent()
    {
        return $this->display(__FILE__, 'views/templates/admin/config.tpl');
    }


    public function hookActionValidateOrder($params)
    {
        $order = $params['order'];
        $customer = new Customer((int) $order->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            return;
        }

        // Generar código aleatorio
        $discountCode = 'DESC' . strtoupper(Tools::passwdGen(8));

        // Crear el vale de descuento
        $cartRule = new CartRule();
        $cartRule->code = $discountCode;
        $cartRule->name = ['1' => 'Descuento especial'];
        $cartRule->id_customer = (int) $customer->id;
        $cartRule->date_from = date('Y-m-d H:i:s');
        $cartRule->date_to = date('Y-m-d H:i:s', strtotime('+30 days'));
        $cartRule->quantity = 1;
        $cartRule->quantity_per_user = 1;
        $cartRule->reduction_percent = 5; // Descuento del 5%
        $cartRule->active = 1;
        $cartRule->add();

        // Enviar email al cliente con el código
        Mail::Send(
            (int) $order->id_lang, // Idioma del pedido
            'discount_email', // Nombre del archivo sin extensión
            $this->l('¡Tu código de descuento!'), // Asunto del correo
            [
                '{firstname}' => $customer->firstname,
                '{discount_code}' => $discountCode
            ],
            $customer->email, // Email del cliente
            $customer->firstname . ' ' . $customer->lastname,
            null, // Email del remitente (dejar null para usar el de la tienda)
            null, // Nombre del remitente (dejar null para usar el de la tienda)
            null, // Adjuntos
            null, // Dirección de respuesta
            _PS_MODULE_DIR_ . 'descuentos_tras_compras/mails/' . $this->context->language->iso_code . '/discount_email.txt' // Archivo de texto
        );
    }
}
