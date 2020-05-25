<?php
/**
 * Author: ldz
 * Created at: 2019-5-16 14:33:57
 */

namespace Common\Util;

use Think\Exception;

class Constants
{

    const total_release = 150000; // 总业绩（用户伞下总业绩：消费通证购买的总和）
    const bao_dan_buy_amount = 50000; // 每个用户消费通证购物只能购买5万
    const sign_in_min_amount = 1000; //签到账户最小释放金额
    const open_power_sell_amount = 1000;//开通卖出权限所需金额
    const total_amount = 2000;//自营商城,淘宝天猫,实体商家购买累计达标释放值


    const YesNo_Yes = 1;
    const YesNo_No = 0;

    /**
     * 是 否
     * @param null $key
     * @return mixed
     * @throws Exception
     */
    public static function getYesNoItems($key = null)
    {
        $items = [
            self::YesNo_Yes => '是',
            self::YesNo_No => '否',
        ];
        return self::getItems($items, $key);
    }

    const USER_LEVEL_NOT_ACTIVATE = 0;
    const USER_LEVEL_ACTIVATE = 1;
    const USER_LEVEL_A_ONE = 2;
    const USER_LEVEL_A_TWO = 3;
    const USER_LEVEL_A_THREE = 4;
    const USER_LEVEL_A_FOUR = 5;

    /**
     * 用户等级
     * @param null $key
     * @return mixed
     * @throws Exception
     */
    public static function getUserLevelItems($key = null)
    {
        $items = array(
            self::USER_LEVEL_NOT_ACTIVATE => '未激活',
            self::USER_LEVEL_ACTIVATE => '服务商',
            self::USER_LEVEL_A_ONE => '社区站',
            self::USER_LEVEL_A_TWO => '运营中心',
            self::USER_LEVEL_A_THREE => '分公司',
            self::USER_LEVEL_A_FOUR => '股东',
        );
        return self::getItems($items, $key);
    }

    const SHOP_TYPE_BAO_DAN = 1;
    const SHOP_TYPE_REGENERATE = 2;
    const SHOP_TYPE_SELF_SUPPORT = 3;
    const SHOP_TYPE_THIRD_PARTY = 4;
    const SHOP_TYPE_ENTITY = 5;
    const SHOP_TYPE_LOVE = 6;

    /**
     * 商城分类
     * @param null $key
     * @return mixed
     * @throws Exception
     */
    public static function getShopTypeItems($key = null)
    {
        $items = array(
            self::SHOP_TYPE_BAO_DAN => '注册',
            self::SHOP_TYPE_REGENERATE => '我要进货',
            self::SHOP_TYPE_SELF_SUPPORT => '申请邮寄',
            self::SHOP_TYPE_THIRD_PARTY => '',
            self::SHOP_TYPE_ENTITY => '',
            self::SHOP_TYPE_LOVE => '',
        );
        return self::getItems($items, $key);
    }

    const PAY_TYPE_ZERO = 0;
    const PAY_TYPE_ONE = 1;
    const PAY_TYPE_TWO = 2;
    const PAY_TYPE_THREE = 3;
    const PAY_TYPE_FOUR = 4;
    const PAY_TYPE_FIVE = 5;

    /**
     * 支付方式
     * @param null $key
     * @return mixed
     * @throws Exception
     */
    public static function getPayWayItems($key = null)
    {
        $items = array(
            self::PAY_TYPE_ZERO => '未选择',
            self::PAY_TYPE_ONE => '线下支付',
            self::PAY_TYPE_TWO => '余额支付',
            self::PAY_TYPE_THREE => '',
            self::PAY_TYPE_FOUR => '',
            self::PAY_TYPE_FIVE => '',
        );
        return self::getItems($items, $key);
    }

    const ORDER_STATUS_NOT_PAY = 0;
    const ORDER_STATUS_PAY = 1;
    const ORDER_STATUS_SHIPPED = 2;
    const ORDER_STATUS_COMPLETED = 3;

    /**
     * 订单状态
     * @param null $key
     * @return mixed
     * @throws Exception
     */
    public static function getOrderStatusItems($key = null)
    {
        $items = array(
            self::ORDER_STATUS_NOT_PAY => '待支付',
            self::ORDER_STATUS_PAY => '待发货',
            self::ORDER_STATUS_SHIPPED => '已发货',
            self::ORDER_STATUS_COMPLETED => '交易完成',
        );
        return self::getItems($items, $key);
    }

    const LOCK_WAREHOUSE_NOT_SELECT = 1;
    const LOCK_WAREHOUSE_GROWTH = 2;
    const LOCK_WAREHOUSE_RELEASE = 3;

    /**
     * 锁仓通证状态
     * @param null $key
     * @return mixed
     * @throws Exception
     */
    public static function getLockWarehouseItems($key = null)
    {
        $items = array(
            self::LOCK_WAREHOUSE_NOT_SELECT => '未选择时间 ',
            self::LOCK_WAREHOUSE_GROWTH => '增值中',
            self::LOCK_WAREHOUSE_RELEASE => '已释放',
        );
        return self::getItems($items, $key);
    }

    const STORE_TYPE_SHARE_REWARD = 1;
    const STORE_TYPE_MERITS_REWARD = 2;
    const STORE_TYPE_THANKFUL_REWARD = 3;
    const STORE_TYPE_FLOW = 4;
    const STORE_TYPE_FEEDBACK = 5;
    const STORE_TYPE_CAN_FLOW = 6;
    const STORE_TYPE_CURRENT_ASSETS = 7;
    const STORE_TYPE_PRODUCT_INTEGRAL = 8;
    const STORE_TYPE_TURNOVER = 9;
    const STORE_TYPE_CLOUD_LIBRARY = 10;

    /**
     * 钱包类型
     * @param null $key
     * @return mixed
     * @throws Exception
     */
    public static function getStoreTypeItems($key = null)
    {
        $items = array(
            self::STORE_TYPE_SHARE_REWARD => '分享奖',
            self::STORE_TYPE_MERITS_REWARD => '绩效奖',
            self::STORE_TYPE_THANKFUL_REWARD => '感恩奖',
            self::STORE_TYPE_FLOW => '流动通证',
            self::STORE_TYPE_FEEDBACK => '回馈',
            self::STORE_TYPE_CAN_FLOW => '可用流动通证',
            self::STORE_TYPE_CURRENT_ASSETS => '流动资产',
            self::STORE_TYPE_PRODUCT_INTEGRAL => '产品积分',
            self::STORE_TYPE_TURNOVER => '营业款账户',
            self::STORE_TYPE_CLOUD_LIBRARY => '我的仓库',
        );
        return self::getItems($items, $key);
    }

    const VERIFY_PERSON = 1;
    const VERIFY_BUSINESS = 2;

    /**
     * 认证类型
     * @param null $key
     * @return mixed
     * @throws Exception
     */
    public static function getVerifyTypeItems($key = null)
    {
        $items = array(
            self::VERIFY_PERSON => '实体商家',
            self::VERIFY_BUSINESS => '线上商家',
        );
        return self::getItems($items, $key);
    }

    const VERIFY_STATUS_WAIT = 0;
    const VERIFY_STATUS_PASS = 1;
    const VERIFY_STATUS_NOT_PASS = 2;

    /**
     * 认证状态
     * @param null $key
     * @return mixed
     * @throws Exception
     */
    public static function getVerifyStatusItems($key = null)
    {
        $items = array(
            self::VERIFY_STATUS_WAIT => '待审核',
            self::VERIFY_STATUS_PASS => '已通过',
            self::VERIFY_STATUS_NOT_PASS => '未通过',
        );
        return self::getItems($items, $key);
    }

    const WITHDRAWAL_TYPE_ECOLOGY = 1;
    const WITHDRAWAL_TYPE_TURNOVER = 2;

    /**
     * 提现类型
     * @param null $key
     * @return mixed
     * @throws Exception
     */
    public static function getWithdrawalTypeItems($key = null)
    {
        $items = array(
            self::WITHDRAWAL_TYPE_ECOLOGY => '可用余额提现',
            self::WITHDRAWAL_TYPE_TURNOVER => '营业款提现',
        );
        return self::getItems($items, $key);
    }

    /**
     * @param $items
     * @param null $key
     * @return mixed
     * @throws Exception
     */
    private static function getItems($items, $key = null)
    {
        if ($key !== null) {
            if (key_exists($key, $items)) {
                return $items[$key];
            }
            throw new Exception('Unknown key:' . $key);
        }
        return $items;
    }

    /**
     * 用户登录类型
     */
    const BACKEDND = 1; //总后台
    const ANOTHER_BACKEND = 2; //仓库后台

    public static function getLoginTypeItems($key = null)
    {
        $items = [
            self::BACKEDND => '总后台',
            self::ANOTHER_BACKEND => '仓库',
        ];
        return self::getItems($items, $key);
    }
}