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

namespace Eccube\Page\Mypage;

use Eccube\Common\Customer;
use Eccube\Common\CartSession;
use Eccube\Common\Query;
use Eccube\Common\Response;
use Eccube\Common\Helper\PurchaseHelper;
use Eccube\Common\Util\Utils;

/**
 * 受注履歴からカート遷移 のページクラス.
 *
 * @package Page
 * @author LOCKON CO.,LTD.
 */
class Order extends AbstractMypage
{
    /**
     * Page を初期化する.
     *
     * @return void
     */
    public function init()
    {
        $this->skip_load_page_layout = true;
        parent::init();
    }

    /**
     * Page のプロセス.
     *
     * @return void
     */
    public function process()
    {
        parent::process();
    }

    /**
     * Page のAction.
     *
     * @return void
     */
    public function action()
    {
        //決済処理中ステータスのロールバック
        $objPurchase = new PurchaseHelper();
        $objPurchase->cancelPendingOrder(PENDING_ORDER_CANCEL_FLAG);

        //受注詳細データの取得
        $arrOrderDetail = $this->lfGetOrderDetail($_POST['order_id']);

        //ログインしていない、またはDBに情報が無い場合
        if (empty($arrOrderDetail)) {
            Utils::sfDispSiteError(CUSTOMER_ERROR);
        }

        $this->lfAddCartProducts($arrOrderDetail);
        Response::sendRedirect(CART_URL);
    }

    // 受注詳細データの取得
    public function lfGetOrderDetail($order_id)
    {
        $objQuery       = Query::getSingletonInstance();

        $objCustomer    = new Customer();
        //customer_idを検証
        $customer_id    = $objCustomer->getValue('customer_id');
        $order_count    = $objQuery->count('dtb_order', 'order_id = ? and customer_id = ?', array($order_id, $customer_id));
        if ($order_count != 1) return array();

        $col    = 'dtb_order_detail.product_class_id, quantity';
        $table  = 'dtb_order_detail LEFT JOIN dtb_products_class ON dtb_order_detail.product_class_id = dtb_products_class.product_class_id';
        $where  = 'order_id = ?';
        $objQuery->setOrder('order_detail_id');
        $arrOrderDetail = $objQuery->select($col, $table, $where, array($order_id));

        return $arrOrderDetail;
    }

    // 商品をカートに追加
    public function lfAddCartProducts($arrOrderDetail)
    {
        $objCartSess = new CartSession();
        foreach ($arrOrderDetail as $order_row) {
            $objCartSess->addProduct($order_row['product_class_id'], $order_row['quantity']);
        }
    }
}