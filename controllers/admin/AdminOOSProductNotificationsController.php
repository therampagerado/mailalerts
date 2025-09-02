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
 * to info@thirtybees.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.prestashop.com for more information.
 */

class AdminOOSProductNotificationsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function initContent()
    {
        if (Tools::isSubmit('delete' . $this->module->name)) {
            $subscriberId = (int) Tools::getValue('id_mailalert_customer_oos');
            $productId = (int) Tools::getValue('id_product');
            if ($subscriberId) {
                Db::getInstance()->delete('mailalert_customer_oos', 'id_mailalert_customer_oos = ' . $subscriberId);
                $this->confirmations[] = $this->l('The notification has been successfully deleted.');
            }
            Tools::redirectAdmin(self::$currentIndex . '&token=' . $this->token . ($productId ? '&id_product=' . $productId : ''));
        }

        $this->content .= $this->module->renderList();
        $this->context->smarty->assign('content', $this->content);

        parent::initContent();
    }
}
