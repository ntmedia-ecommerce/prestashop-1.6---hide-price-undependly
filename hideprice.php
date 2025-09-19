<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class HidePrice extends Module
{
    public function __construct()
    {
        $this->name = 'hideprice';
        $this->tab = 'front_office_features';
        $this->version = '1.1.0';
        $this->author = 'NTMedia Ecommerce - Jarosław Fijołek | nt-media.pl';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('Ukrywanie/Pokazywanie cen (globalnie)');
        $this->description = $this->l('Pozwala ukrywać lub pokazywać ceny niezależnie od trybu katalogu. Działa na stronie produktu, listach produktów i w koszyku.');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        return parent::install()
            && Configuration::updateValue('HIDEPRICE_SHOW', 1)
            && $this->registerHook('displayProductPriceBlock')
            && $this->registerHook('displayHeader');
    }

    public function uninstall()
    {
        return parent::uninstall()
            && Configuration::deleteByName('HIDEPRICE_SHOW');
    }

    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submitHidePrice')) {
            $show = (int)Tools::getValue('HIDEPRICE_SHOW');
            Configuration::updateValue('HIDEPRICE_SHOW', $show);
            $output .= $this->displayConfirmation($this->l('Ustawienia zapisane.'));
        }

        $output .= $this->renderForm();
        return $output;
    }

    protected function renderForm()
{
    $fields_form = [
        'form' => [
            'legend' => [
                'title' => $this->l('Ustawienia'),
            ],
            'input' => [
                [
                    'type' => 'switch',
                    'label' => $this->l('Pokazuj ceny'),
                    'name' => 'HIDEPRICE_SHOW',
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Tak')
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Nie')
                        ]
                    ]
                ]
            ],
            'submit' => [
                'title' => $this->l('Zapisz'),
                'name'  => 'submitHidePrice'
            ]
        ]
    ];

    $helper = new HelperForm();
    $helper->module = $this;
    $helper->name_controller = $this->name;
    $helper->identifier = $this->identifier;
    $helper->token = Tools::getAdminTokenLite('AdminModules');
    $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
    $helper->submit_action = 'submitHidePrice'; // <--- ważne

    $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
    $helper->allow_employee_form_lang = (int)Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');

    $helper->fields_value['HIDEPRICE_SHOW'] = Configuration::get('HIDEPRICE_SHOW');

    return $helper->generateForm([$fields_form]);
}

    /**
     * Ukrywa blok ceny w miejscach, gdzie templaty używają hooka displayProductPriceBlock
     */
    public function hookDisplayProductPriceBlock($params)
    {
        // Obsługujemy różne typy: price, without_discount, unit_price, old_price, weight, etc.
        if (!Configuration::get('HIDEPRICE_SHOW')) {
            // Zwrócenie pustego ciągu wycina cenę z miejsca wywołania hooka
            return '';
        }
        // gdy pokazywanie cen jest włączone — nic nie robimy
    }

    /**
     * Wstrzykujemy CSS chowający ceny i sumy (lista, koszyk, checkout),
     * także w miejscach, gdzie szablon nie używa wspomnianego hooka.
     */
    public function hookDisplayHeader($params)
    {
        if (!Configuration::get('HIDEPRICE_SHOW')) {
            $this->context->controller->addCSS($this->_path.'views/css/hide.css', 'all');
            // Opcjonalnie możemy dodać meta noindex dla stron koszyka, ale nie jest to wymagane.
        }
    }
}
