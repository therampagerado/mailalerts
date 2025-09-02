<?php
/**
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @copyright 2017-2024 thirty bees
 * @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

require_once __DIR__.'/../../classes/autoload.php';

use MailAlertModule\MailAlert;

if (!defined('_TB_VERSION_')) {
    exit;
}

class AdminMailalertsOosController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = MailAlert::$definition['table'];
        $this->className = MailAlert::class;
        $this->identifier = MailAlert::$definition['primary'];
        $this->lang = false;
        parent::__construct();

        $idLang = (int)$this->context->language->id;
        $this->_select = 'pl.name AS product_name, '
            . 'IF(a.id_product_attribute > 0, GROUP_CONCAT(DISTINCT al.name ORDER BY agl.id_attribute_group SEPARATOR ", "), "") AS combination';
        $this->_join = 'LEFT JOIN '._DB_PREFIX_.'product_lang pl ON (pl.id_product = a.id_product AND pl.id_lang = '.$idLang.' AND pl.id_shop = a.id_shop) '
            . 'LEFT JOIN '._DB_PREFIX_.'product_attribute_combination pac ON (pac.id_product_attribute = a.id_product_attribute) '
            . 'LEFT JOIN '._DB_PREFIX_.'attribute attr ON (attr.id_attribute = pac.id_attribute) '
            . 'LEFT JOIN '._DB_PREFIX_.'attribute_lang al ON (al.id_attribute = pac.id_attribute AND al.id_lang = '.$idLang.') '
            . 'LEFT JOIN '._DB_PREFIX_.'attribute_group_lang agl ON (agl.id_attribute_group = attr.id_attribute_group AND agl.id_lang = '.$idLang.')';
        $this->_group = 'GROUP BY a.'.$this->identifier;

        $this->fields_list = [
            $this->identifier => [
                'title' => $this->l('ID'),
                'class' => 'fixed-width-xs',
            ],
            'customer_email' => [
                'title' => $this->l('Customer email'),
            ],
            'product_name' => [
                'title' => $this->l('Product'),
            ],
            'combination' => [
                'title' => $this->l('Combination'),
                'orderby' => false,
            ],
            'date_add' => [
                'title' => $this->l('Date'),
                'type'  => 'datetime',
            ],
        ];

        $this->bulk_actions = [
            'delete' => [
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
            ],
        ];
    }

    public function renderList()
    {
        $this->addRowAction('delete');
        return parent::renderList();
    }
}

