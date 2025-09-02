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
                $this->confirmations[] = $this->module->l('The notification has been successfully deleted.');

                Tools::redirectAdmin($this->context->link->getAdminLink('AdminMailAlertOos', true, [
                    'id_product' => $productId,
                ]));
            }
        }

        parent::postProcess();
    }

    public function initContent()
    {
        $this->content = $this->module->renderList();
        parent::initContent();
    }
}
