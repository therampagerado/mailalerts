<?php
/**
 * 2007-2016 PrestaShop
 * 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@thirtybees.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    thirty bees <info@thirtybees.com>
 * @copyright 2007-2016 PrestaShop SA
 * @copyright 2017-2024 thirty bees
 * @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class AdminOosProductNotificationsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('deletemailalert_customer_oos')) {
            $id = (int)Tools::getValue('id_mailalert_customer_oos');
            $productId = (int)Tools::getValue('id_product');
            if ($id) {
                Db::getInstance()->delete('mailalert_customer_oos', 'id_mailalert_customer_oos = ' . $id);
                $this->confirmations[] = $this->l('The notification has been successfully deleted.');
            }
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminOosProductNotifications', true, [], [
                'id_product' => $productId,
            ]));
        }

        parent::postProcess();
    }

    public function initContent()
    {
        $this->content = $this->renderList();
        $this->context->smarty->assign('content', $this->content);
        parent::initContent();
    }

    protected function renderList()
    {
        $productId = (int)Tools::getValue('id_product');

        if ($productId) {
            $product = new Product($productId, false, $this->context->language->id);
            $productName = Validate::isLoadedObject($product) ? $product->name : $this->l('Deleted product');

            $listFields = [
                'combination_name' => [
                    'title' => $this->l('Combination'),
                    'type'  => 'text',
                ],
                'customer_name' => [
                    'title' => $this->l('Customer'),
                    'type'  => 'text',
                    'callback' => 'renderCustomer',
                ],
                'customer_email' => [
                    'title' => $this->l('Email'),
                    'type'  => 'text',
                ],
                'date_add' => [
                    'title' => $this->l('Date'),
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
            $helper->title = sprintf($this->l('Notification for "%s". [1]Show all[/1]'), $productName);
            $helper->table = 'mailalert_customer_oos';
            $helper->token = Tools::getAdminTokenLite('AdminOosProductNotifications');
            $helper->currentIndex = $this->context->link->getAdminLink('AdminOosProductNotifications');
            $content = $this->getProductListSubscribers($productId);
            $helper->listTotal = count($content);
            return $helper->generateList($content, $listFields);
        } else {
            $listFields = [
                'id_product' => [
                    'title' => $this->l('Product ID'),
                    'type'  => 'text',
                ],
                'reference' => [
                    'title' => $this->l('Reference'),
                    'type'  => 'text',
                    'callback' => 'renderProduct',
                ],
                'product_name' => [
                    'title' => $this->l('Product Name'),
                    'type'  => 'text',
                    'callback' => 'renderProduct',
                ],
                'combination_name' => [
                    'title' => $this->l('Combination'),
                    'type'  => 'text',
                ],
                'cnt' => [
                    'title' => $this->l('Number of subscribers'),
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
            $helper->title = $this->l('Products with notifications');
            $helper->table = 'mailalert_customer_oos';
            $helper->token = Tools::getAdminTokenLite('AdminOosProductNotifications');
            $helper->currentIndex = $this->context->link->getAdminLink('AdminOosProductNotifications');
            $content = $this->getProductsSubscribers();
            $helper->listTotal = count($content);
            return $helper->generateList($content, $listFields);
        }
    }

    public function renderCustomer($value, $row)
    {
        $customerId = (int)$row['id_customer'];
        $url = $this->context->link->getAdminLink('AdminCustomers', true, [
            'id_customer' => $customerId,
            'viewcustomer' => 1,
        ]);
        return '<a href="' . $url . '">' . Tools::safeOutput($value) . '</a>';
    }

    public function renderProduct($value, $row)
    {
        $productId = (int)$row['id_product'];
        $url = $this->context->link->getAdminLink('AdminProducts', true, [
            'id_product' => $productId,
            'updateproduct' => 1,
        ]);
        return '<a href="' . $url . '">' . Tools::safeOutput($value) . '</a>';
    }

    public function renderCnt($value, $row)
    {
        $productId = (int)$row['id_product'];
        $url = $this->context->link->getAdminLink('AdminOosProductNotifications', true, [], [
            'id_product' => $productId,
        ]);
        return '<a href="' . $url . '">' . Tools::safeOutput($value) . '</a>';
    }

    protected function getProductsSubscribers()
    {
        $langId = (int)$this->context->language->id;
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
        $langId = (int)$this->context->language->id;
        $conn = Db::getInstance();
        $sql = (new DbQuery())
            ->select('oos.id_mailalert_customer_oos')
            ->select('oos.id_customer')
            ->select('IF(oos.id_product_attribute > 0, oos.id_product_attribute, NULL)')
            ->select('oos.customer_email')
            ->select('oos.date_add')
            ->select("COALESCE((
                            SELECT GROUP_CONCAT(al.name ORDER BY agl.id_attribute_group SEPARATOR ', ')
                             FROM " . _DB_PREFIX_ . "product_attribute_combination pac
                             LEFT JOIN " . _DB_PREFIX_ . "attribute a ON a.id_attribute = pac.id_attribute
                             LEFT JOIN " . _DB_PREFIX_ . "attribute_group ag ON ag.id_attribute_group = a.id_attribute_group
                             LEFT JOIN " . _DB_PREFIX_ . "attribute_lang al ON (a.id_attribute = al.id_attribute AND al.id_lang = " . $langId . ")
                             LEFT JOIN " . _DB_PREFIX_ . "attribute_group_lang agl ON (ag.id_attribute_group = agl.id_attribute_group AND agl.id_lang = " . $langId . ")
                             WHERE pac.id_product_attribute  = oos.id_product_attribute
                             GROUP BY pac.id_product_attribute
                    ), '-') AS combination_name")
            ->select('IF(cust.id_customer, CONCAT(cust.firstname, " ", cust.lastname), NULL) AS customer_name')
            ->from('mailalert_customer_oos', 'oos')
            ->leftJoin('product_lang', 'pl', 'pl.id_lang = ' . $langId . ' AND pl.id_product = oos.id_product AND pl.id_shop = oos.id_shop')
            ->leftJoin('customer', 'cust', 'oos.id_customer = cust.id_customer')
            ->where('oos.id_product = ' . $productId . Shop::addSqlRestriction(false, 'oos'))
            ->orderBy('oos.id_product')
            ->orderBy('oos.id_product_attribute')
            ->orderBy('oos.date_add');
        return $conn->executeS($sql);
    }
}
