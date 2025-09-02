<?php
/**
 * 2007-2016 PrestaShop
 * 2017 - thirty bees
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
 * @author      thirty bees <info@thirtybees.com>
 * @copyright 2007-2016 PrestaShop SA
 * @copyright 2017-2024 thirty bees
 * @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 * U.S.A. Trademark of thirty bees
 */

class AdminOosProductNotificationsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function initContent()
    {
        if (Tools::isSubmit('delete' . $this->module->name)) {
            $subscriberId = (int)Tools::getValue('id_mailalert_customer_oos');
            $productId = (int)Tools::getValue('id_product');
            if ($subscriberId) {
                Db::getInstance()->delete('mailalert_customer_oos', 'id_mailalert_customer_oos = ' . $subscriberId);
                Tools::redirectAdmin(self::$currentIndex . '&token=' . $this->token . ($productId ? '&id_product=' . $productId : ''));
            }
        }

        $this->content .= $this->renderList();
        parent::initContent();
    }

    protected function renderList()
    {
        $productId = (int)Tools::getValue('id_product');

        if ($productId) {
            $product = new Product($productId, false, $this->context->language->id);
            if (Validate::isLoadedObject($product)) {
                $productName = $product->name;
            } else {
                $productName = $this->module->l('Deleted product');
            }

            $listFields = [
                'combination_name' => [
                    'title' => $this->module->l('Combination'),
                    'type' => 'text',
                ],
                'customer_name' => [
                    'title' => $this->module->l('Customer'),
                    'type' => 'text',
                    'callback_object' => $this,
                    'callback' => 'renderCustomer',
                ],
                'customer_email' => [
                    'title' => $this->module->l('Email'),
                    'type' => 'text',
                ],
                'date_add' => [
                    'title' => $this->module->l('Date'),
                    'type' => 'text',
                ],
            ];

            if (! $product->hasAttributes()) {
                unset($listFields['combination_name']);
            }

            $helper = new HelperList();
            $helper->shopLinkType = '';
            $helper->simple_header = true;
            $helper->identifier = 'id_mailalert_customer_oos';
            $helper->actions = ['delete'];
            $helper->no_link = true;
            $helper->show_toolbar = false;
            $url = Context::getContext()->link->getAdminLink('AdminOosProductNotifications');
            $helper->title = Translate::ppTags(sprintf($this->module->l('Notification for "%s". [1]Show all[/1]'), $productName, $productId), ['<a href="' . $url . '">']);
            $helper->table = $this->module->name;
            $helper->token = Tools::getAdminTokenLite('AdminOosProductNotifications');
            $helper->currentIndex = self::$currentIndex . '&id_product=' . $productId;
            $method = new ReflectionMethod($this->module, 'getProductListSubscribers');
            $method->setAccessible(true);
            $content = $method->invoke($this->module, $productId);
            $helper->listTotal = count($content);
            return $helper->generateList($content, $listFields);
        } else {
            $listFields = [
                'id_product' => [
                    'title' => $this->module->l('Product ID'),
                    'type' => 'text',
                ],
                'reference' => [
                    'title' => $this->module->l('Reference'),
                    'type' => 'text',
                    'callback_object' => $this,
                    'callback' => 'renderProduct',
                ],
                'product_name' => [
                    'title' => $this->module->l('Product Name'),
                    'type' => 'text',
                    'callback_object' => $this,
                    'callback' => 'renderProduct',
                ],
                'combination_name' => [
                    'title' => $this->module->l('Combination'),
                    'type' => 'text',
                ],
                'cnt' => [
                    'title' => $this->module->l('Number of subscribers'),
                    'type' => 'text',
                    'callback_object' => $this,
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
            $helper->title = $this->module->l('Products with notifications');
            $helper->table = $this->module->name;
            $helper->token = Tools::getAdminTokenLite('AdminOosProductNotifications');
            $helper->currentIndex = self::$currentIndex;
            $method = new ReflectionMethod($this->module, 'getProductsSubscribers');
            $method->setAccessible(true);
            $content = $method->invoke($this->module);
            $helper->listTotal = count($content);
            return $helper->generateList($content, $listFields);
        }
    }

    protected function renderCustomer($value, $row)
    {
        $customerId = (int)$row['id_customer'];
        $url = Context::getContext()->link->getAdminLink('AdminCustomers', true, [
            'id_customer' => $customerId,
            'viewcustomer' => 1,
        ]);
        return '<a href="' . $url . '">' . Tools::safeOutput($value) . '</a>';
    }

    protected function renderProduct($value, $row)
    {
        $productId = (int)$row['id_product'];
        $url = Context::getContext()->link->getAdminLink('AdminProducts', true, [
            'id_product' => $productId,
            'updateproduct' => 1,
        ]);
        return '<a href="' . $url . '">' . Tools::safeOutput($value) . '</a>';
    }

    protected function renderCnt($value, $row)
    {
        $productId = (int)$row['id_product'];
        $url = Context::getContext()->link->getAdminLink('AdminOosProductNotifications', true, [
            'id_product' => $productId,
        ]);
        return '<a href="' . $url . '">' . Tools::safeOutput($value) . '</a>';
    }

}

