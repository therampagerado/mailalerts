<?php
/**
 * 2007-2016 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2016 PrestaShop SA
 * @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_TB_VERSION_')) {
    exit;
}

class AdminMailAlertOosController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->module = Module::getInstanceByName('mailalerts');
        parent::__construct();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('delete' . $this->module->name)) {
            $subscriberId = (int) Tools::getValue('id_mailalert_customer_oos');
            $productId = (int) Tools::getValue('id_product');

            if ($subscriberId) {
                Db::getInstance()->delete('mailalert_customer_oos', 'id_mailalert_customer_oos = ' . $subscriberId);
                $this->confirmations[] = $this->module->l('The notification has been successfully deleted.', 'adminmailalertooscontroller');

                Tools::redirectAdmin($this->context->link->getAdminLink('AdminMailAlertOos', true, [
                    'id_product' => $productId,
                ]));
            }
        }

        parent::postProcess();
    }

    public function renderList()
    {
        $productId = (int) Tools::getValue('id_product');

        if ($productId) {
            $product = new Product($productId, false, $this->context->language->id);
            $productName = Validate::isLoadedObject($product)
                ? $product->name
                : $this->module->l('Deleted product', 'adminmailalertooscontroller');

            $listFields = [
                'combination_name' => [
                    'title' => $this->module->l('Combination', 'adminmailalertooscontroller'),
                    'type'  => 'text',
                ],
                'customer_name' => [
                    'title' => $this->module->l('Customer', 'adminmailalertooscontroller'),
                    'type'  => 'text',
                    'callback' => 'renderCustomer',
                ],
                'customer_email' => [
                    'title' => $this->module->l('Email', 'adminmailalertooscontroller'),
                    'type'  => 'text',
                ],
                'date_add' => [
                    'title' => $this->module->l('Date', 'adminmailalertooscontroller'),
                    'type'  => 'text',
                ],
            ];

            if (!$product->hasAttributes()) {
                unset($listFields['combination_name']);
            }

            $helper = new HelperList();
            $helper->shopLinkType = '';
            $helper->simple_header = true;
            $helper->identifier = 'id_mailalert_customer_oos';
            $helper->actions = ['delete'];
            $helper->no_link = true;
            $helper->show_toolbar = false;
            $url = $this->context->link->getAdminLink('AdminMailAlertOos');
            $helper->title = Translate::ppTags(
                sprintf(
                    $this->module->l('Notification for "%s". [1]Show all[/1]', 'adminmailalertooscontroller'),
                    $productName,
                    $productId
                ),
                ['<a href="' . $url . '">']
            );
            $helper->table = $this->module->name;
            $helper->token = Tools::getAdminTokenLite('AdminMailAlertOos');
            $helper->currentIndex = AdminController::$currentIndex . '&id_product=' . $productId;
            $content = $this->getProductListSubscribers($productId);
            $helper->listTotal = count($content);

            return $helper->generateList($content, $listFields);
        }

        $listFields = [
            'id_product' => [
                'title' => $this->module->l('Product ID', 'adminmailalertooscontroller'),
                'type'  => 'text',
            ],
            'reference' => [
                'title' => $this->module->l('Reference', 'adminmailalertooscontroller'),
                'type'  => 'text',
                'callback' => 'renderProduct',
            ],
            'product_name' => [
                'title' => $this->module->l('Product Name', 'adminmailalertooscontroller'),
                'type'  => 'text',
                'callback' => 'renderProduct',
            ],
            'combination_name' => [
                'title' => $this->module->l('Combination', 'adminmailalertooscontroller'),
                'type'  => 'text',
            ],
            'cnt' => [
                'title' => $this->module->l('Number of subscribers', 'adminmailalertooscontroller'),
                'type'  => 'text',
                'callback' => 'renderCnt',
            ],
        ];

        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = true;
        $helper->identifier = 'id_product';
        $helper->actions = [];
        $helper->no_link = true;
        $helper->show_toolbar = false;
        $helper->title = $this->module->l('Products with notifications', 'adminmailalertooscontroller');
        $helper->table = $this->module->name;
        $helper->token = Tools::getAdminTokenLite('AdminMailAlertOos');
        $helper->currentIndex = AdminController::$currentIndex;
        $content = $this->getProductsSubscribers();
        $helper->listTotal = count($content);

        return $helper->generateList($content, $listFields);
    }

    protected function getProductsSubscribers()
    {
        $langId = (int) $this->context->language->id;
        $conn = Db::getInstance();

        $sql = (new DbQuery())
            ->select('oos.id_product')
            ->select('NULLIF(p.reference, "") AS reference')
            ->select('NULLIF(pl.name, "") AS product_name')
            ->select('COUNT(DISTINCT oos.id_mailalert_customer_oos) as cnt')
            ->select('IF(oos.id_product_attribute > 0, GROUP_CONCAT(DISTINCT al.name ORDER BY agl.id_attribute_group SEPARATOR ", "), "") AS combination_name')
            ->from('mailalert_customer_oos', 'oos')
            ->leftJoin('product_lang', 'pl', 'pl.id_lang = ' . $langId . ' AND pl.id_product = oos.id_product AND pl.id_shop = oos.id_shop')
            ->leftJoin('product', 'p', 'p.id_product = oos.id_product')
            ->leftJoin('product_attribute_combination', 'pac', 'pac.id_product_attribute = oos.id_product_attribute')
            ->leftJoin('attribute', 'a', 'a.id_attribute = pac.id_attribute')
            ->leftJoin('attribute_lang', 'al', 'al.id_attribute = a.id_attribute AND al.id_lang = ' . $langId)
            ->leftJoin('attribute_group_lang', 'agl', 'agl.id_attribute_group = a.id_attribute_group AND agl.id_lang = ' . $langId)
            ->where('1' . Shop::addSqlRestriction(false, 'oos'))
            ->groupBy('oos.id_product')
            ->orderBy('COUNT(DISTINCT oos.id_mailalert_customer_oos) DESC');

        return $conn->executeS($sql);
    }

    protected function getProductListSubscribers($productId)
    {
        $langId = (int) $this->context->language->id;
        $conn = Db::getInstance();

        $sql = (new DbQuery())
            ->select('oos.id_mailalert_customer_oos')
            ->select('oos.id_customer')
            ->select('IF(oos.id_product_attribute > 0, oos.id_product_attribute, NULL)')
            ->select('oos.customer_email')
            ->select('oos.date_add')
            ->select('COALESCE((
                            SELECT GROUP_CONCAT(al.`name` ORDER BY agl.`id_attribute_group` SEPARATOR ", ")
                             FROM `'._DB_PREFIX_.'product_attribute_combination` pac
                             LEFT JOIN `'._DB_PREFIX_.'attribute` a ON a.`id_attribute` = pac.`id_attribute`
                             LEFT JOIN `'._DB_PREFIX_.'attribute_group` ag ON ag.`id_attribute_group` = a.`id_attribute_group`
                             LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = '.$this->context->language->id.')
                             LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = '.$this->context->language->id.')
                             WHERE pac.id_product_attribute = oos.id_product_attribute
                             GROUP BY pac.id_product_attribute
                    ), "-") AS combination_name')
            ->select('IF(cust.id_customer, CONCAT(cust.firstname, " ", cust.lastname), NULL) AS customer_name')
            ->from('mailalert_customer_oos', 'oos')
            ->leftJoin('product_lang', 'pl', 'pl.id_lang = ' . $langId . ' AND pl.id_product = oos.id_product AND pl.id_shop = oos.id_shop')
            ->leftJoin('customer', 'cust', 'oos.id_customer = cust.id_customer')
            ->where('oos.id_product = ' . (int) $productId . Shop::addSqlRestriction(false, 'oos'))
            ->orderBy('oos.id_product')
            ->orderBy('oos.id_product_attribute')
            ->orderBy('oos.date_add');

        return $conn->executeS($sql);
    }

    public function renderCustomer($value, $row)
    {
        $customerId = (int) $row['id_customer'];
        $url = $this->context->link->getAdminLink('AdminCustomers', true, [
            'id_customer' => $customerId,
            'viewcustomer' => 1,
        ]);

        return '<a href="' . $url . '">' . Tools::safeOutput($value) . '</a>';
    }

    public function renderProduct($value, $row)
    {
        $productId = (int) $row['id_product'];
        $url = $this->context->link->getAdminLink('AdminProducts', true, [
            'id_product' => $productId,
            'updateproduct' => 1,
        ]);

        return '<a href="' . $url . '">' . Tools::safeOutput($value) . '</a>';
    }

    public function renderCnt($value, $row)
    {
        $productId = (int) $row['id_product'];
        $url = $this->context->link->getAdminLink('AdminMailAlertOos', true, [
            'id_product' => $productId,
        ]);

        return '<a href="' . $url . '">' . Tools::safeOutput($value) . '</a>';
    }
}
