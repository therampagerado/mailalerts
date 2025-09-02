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

if (!defined('_TB_VERSION_')) {
    exit;
}

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
                $this->confirmations[] = $this->module->l('The notification has been successfully deleted.');
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminOosProductNotifications', true, ['id_product' => $productId]));
            }
        }

        parent::initContent();

        if ($this->module->customer_qty) {
            $this->content .= $this->module->renderList();
        }

        $this->context->smarty->assign([
            'content' => $this->content,
        ]);
    }
}
