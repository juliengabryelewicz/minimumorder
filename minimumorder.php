<?php

if (!defined('_PS_VERSION_')) {
    exit;
}


use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use Jgabryelewicz\Minimumorder\Form\Modifier\CustomerFormModifier;


class Minimumorder extends Module
{
    public function __construct()
    {
        $this->name = 'minimumorder';
        $this->tab = 'other';
        $this->version = '1.0.0';
        $this->author = 'Julien Gabryelewicz';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => '8.99.99',
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->confirmUninstall = $this->l('Do you still you want to uninstall this module?');
        $this->description = $this->l('Définit un montant minimum du panier par client');
        $this->displayName = $this->l('Minimum order');
    }

    public function install()
    {
        if (parent::install() &&
            $this->createColumns() &&
            $this->registerHook('actionCustomerFormBuilderModifier') &&
            $this->registerHook('actionAfterCreateCustomerFormHandler') &&
            $this->registerHook('actionAfterUpdateCustomerFormHandler') &&
            $this->registerHook('overrideMinimalPurchasePrice') &&
            $this->registerHook('displayAdminCustomers'))
        {
            return true;
        }else{
            $this->_errors[] = $this->l('There was an error during the installation.');
            return false;
        }
    }

    public function uninstall()
    {
        $this->dropColumns();

        return parent::uninstall();
    }

    private function createColumns()
    {
        return Db::getInstance()->execute("ALTER TABLE " . _DB_PREFIX_ . "customer "
        . "ADD COLUMN minimum_price_order DECIMAL(20,6) NULL");
    }


    private function dropColumns()
    {
        Db::getInstance()->execute("ALTER TABLE " . _DB_PREFIX_ . "customer "
        . " DROP COLUMN minimum_price_order");
    }

    public function hookActionCustomerFormBuilderModifier(array $params) : void
    {
        try{
            $customerFormModifier = $this->get(CustomerFormModifier::class);
            $customerId = (int) $params['id'];
            $minimumPriceReq = Db::getInstance()->executeS('SELECT minimum_price_order FROM `' . _DB_PREFIX_ . 'customer` WHERE id_customer = '.$params["id"]);
            if(count($minimumPriceReq) > 0){
                $minimumPrice = $minimumPriceReq[0]["minimum_price_order"];
            } else{
                $minimumPrice = 0;
            }

            $customerFormModifier->modify($customerId, $params['form_builder'], $minimumPrice);
        } catch (\Exception $e){
            echo $e->getMessage();
            exit();
        }
    }

    public function hookActionAfterCreateCustomerFormHandler(array $params)
    {
        $this->updateData($params['id'],$params['form_data']);
    }
 

    public function hookActionAfterUpdateCustomerFormHandler(array $params)
    {
        $this->updateData($params['id'],$params['form_data']);
    }


    protected function updateData(int $id_user,array $data)
    {
        Db::getInstance()->Execute("UPDATE " . _DB_PREFIX_ . "customer SET minimum_price_order=".$data["minimum_price_order"]
        ." WHERE id_customer=".$id_user);
    }

    public function hookDisplayAdminCustomers(array $params)
    {
        $price = Db::getInstance()->executeS('SELECT minimum_price_order FROM `' . _DB_PREFIX_ . 'customer` WHERE id_customer = '.$params["id_customer"]);
        return $this->get('twig')->render('@Modules/minimumorder/views/templates/hook/minimumorder.html.twig',[
            "minimum_price_order" => $price[0]["minimum_price_order"]
        ]);
    }

    public function hookOverrideMinimalPurchasePrice(array $params)
    {
        if ($this->context->customer->isLogged()) {
            $price = Db::getInstance()->executeS('SELECT minimum_price_order FROM `' . _DB_PREFIX_ . 'customer` WHERE id_customer = '.$this->context->customer->id);
            if(count($price) > 0 && $price[0]["minimum_price_order"] != null){
                $params['minimalPurchase'] = $price[0]["minimum_price_order"];
            }
        }
    }
}
