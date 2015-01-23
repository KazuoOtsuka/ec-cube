<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2014 LOCKON CO.,LTD. All Rights Reserved.
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube\Page\Admin\Products;

use Eccube\Page\Admin\AbstractAdminPage;
use Eccube\Common\FormParam;
use Eccube\Common\PageNavi;
use Eccube\Common\Product;
use Eccube\Common\Query;
use Eccube\Common\DB\MasterData;
use Eccube\Common\Helper\DbHelper;
use Eccube\Common\Util\Utils;

/**
 * 商品選択 のページクラス.
 *
 * @package Page
 * @author LOCKON CO.,LTD.
 */
class ProductSelect extends AbstractAdminPage
{
    /**
     * Page を初期化する.
     *
     * @return void
     */
    public function init()
    {
        parent::init();
        $this->tpl_mainpage = 'products/product_select.tpl';
        $this->tpl_mainno = 'products';
        $this->tpl_subno = '';
        $this->tpl_maintitle = '商品管理';
        $this->tpl_subtitle = '商品選択';

        $masterData = new MasterData();
        $this->arrPRODUCTSTATUS_COLOR = $masterData->getMasterData('mtb_product_status_color');
    }

    /**
     * Page のプロセス.
     *
     * @return void
     */
    public function process()
    {
        $this->action();
        $this->sendResponse();
    }

    /**
     * Page のアクション.
     *
     * @return void
     */
    public function action()
    {
        $objDb = new DbHelper();

        $objFormParam = new FormParam();
        $this->lfInitParam($objFormParam);
        $objFormParam->setParam($_POST);
        $objFormParam->convParam();
        $this->arrForm = $objFormParam->getHashArray();

        switch ($this->getMode()) {
            case 'search':
                $this->arrProducts = $this->lfGetProducts($objDb);
                break;
            default:
                break;
        }

        // カテゴリ取得
        $this->arrCatList = $objDb->getCategoryList();
        $this->setTemplate($this->tpl_mainpage);
    }

    /**
     * パラメーター情報の初期化を行う.
     *
     * @param  FormParam $objFormParam FormParam インスタンス
     * @return void
     */
    public function lfInitParam(&$objFormParam)
    {
        $objFormParam->addParam('カテゴリ', 'search_category_id', STEXT_LEN, 'n');
        $objFormParam->addParam('商品名', 'search_name', STEXT_LEN, 'KVa');
        $objFormParam->addParam('商品コード', 'search_product_code', STEXT_LEN, 'KVa');
    }

    /* 商品検索結果取得 */

    /**
     * @param DbHelper $objDb
     */
    public function lfGetProducts(&$objDb)
    {
        $where = 'del_flg = 0';
        $arrWhereVal = array();

        /* 入力エラーなし */
        foreach ($this->arrForm AS $key=>$val) {
            if ($val == '') continue;

            switch ($key) {
                case 'search_name':
                    $where .= ' AND name ILIKE ?';
                    $arrWhereVal[] = "%$val%";
                    break;
                case 'search_category_id':
                    list($tmp_where, $arrTmp) = $objDb->getCatWhere($val);
                    if ($tmp_where != '') {
                        $where.= ' AND product_id IN (SELECT product_id FROM dtb_product_categories WHERE ' . $tmp_where . ')';
                        $arrWhereVal = array_merge((array) $arrWhereVal, (array) $arrTmp);
                    }
                    break;
                case 'search_product_code':
                    $where .= ' AND product_id IN (SELECT product_id FROM dtb_products_class WHERE product_code LIKE ?)';
                    $arrWhereVal[] = "$val%";
                    break;
                default:
                    break;
            }
        }

        $order = 'update_date DESC, product_id DESC ';

        $objQuery = Query::getSingletonInstance();
        // 行数の取得
        $linemax = $objQuery->count('dtb_products', $where, $arrWhereVal);
        $this->tpl_linemax = $linemax;              // 何件が該当しました。表示用

        // ページ送りの処理
        $page_max = Utils::sfGetSearchPageMax($_POST['search_page_max']);

        // ページ送りの取得
        $objNavi = new PageNavi($_POST['search_pageno'], $linemax, $page_max, 'eccube.moveSearchPage', NAVI_PMAX);
        $this->tpl_strnavi = $objNavi->strnavi;     // 表示文字列
        $startno = $objNavi->start_row;

        // 取得範囲の指定(開始行番号、行数のセット)
        $objQuery->setLimitOffset($page_max, $startno);
        // 表示順序
        $objQuery->setOrder($order);

        // 検索結果の取得
        // FIXME 商品コードの表示
        $arrProducts = $objQuery->select('*', Product::alldtlSQL(), $where, $arrWhereVal);

        return $arrProducts;
    }
}