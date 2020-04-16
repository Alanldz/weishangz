<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/24
 * Time: 13:34
 */

namespace Shop\Model;

use Common\Model\PurchaseRecordModel;
use Common\Model\StoreRecordModel;
use Common\Util\Constants;
use Think\Exception;
use Think\Model;

class OrderModel extends Model
{
    /**
     * 用户是否可以购买
     *
     * @param $user_id
     * @param $order_result
     * @return bool
     */
    public static function is_can_buy($user_id, $order_result)
    {
        $todayTotalAmount = M('config')->where(['name' => 'todayTotalAmount'])->getField('value');
        $todayStart = strtotime(date('Y-m-d' . '00:00:00', time()));
        $todayEnd = strtotime(date('Y-m-d' . '00:00:00', time() + 3600 * 24));

        $where['uid'] = $user_id;
        $where['status'] = ['not in', '0'];
        $where['pay_type'] = ['in', '1,5,6'];
        $where['pay_time'] = array('between', "$todayStart,$todayEnd");
        $userTodayBuyPrice = M('order')->where($where)->sum('buy_result');

        $totalAmount = $order_result + $userTodayBuyPrice;
        if ($totalAmount > $todayTotalAmount) {
            return false;
        }
        return true;
    }

    /**
     * 获取用户消费通证区购买订单数量
     * @param $uid
     * @return int
     */
    public static function bao_dan_order_num($uid)
    {
        $where['uid'] = $uid;
        $where['status'] = ['not in', '0'];
        $where['shop_type'] = Constants::SHOP_TYPE_BAO_DAN;

        $num = M('order')->where($where)->count();
        return $num ? $num : 0;
    }

    /**
     * 判断用户是否需要复购
     * @param $uid
     * @return bool
     * @author ldz
     * @time 2020/3/3 17:31
     */
    public static function is_again_bug($uid)
    {
        $where['uid'] = $uid;
        $where['status'] = Constants::LOCK_WAREHOUSE_RELEASE;
        $where['is_release'] = 1;
        $where['flow_amount'] = 0;
        $where['is_again_bug'] = 0;

        $again_bug_num = M('lock_warehouse_pass')->where($where)->count();
        if ($again_bug_num) {
            return true;
        }
        return false;
    }

    /**
     * 按条件查询当前用户的订单数，
     * @param array $where
     * @return int
     */
    public static function getOrderNumByWhere($where = [])
    {
        $where['uid'] = session('userid');
        $where['is_show'] = 1;
        $num = M('order')->where($where)->count();
        return $num ? $num : 0;
    }

    /**
     * 获取兑换额
     * @param $uid
     * @param $total_money
     * @return int|mixed
     * @author ldz
     * @time 2020/3/26 16:04
     */
    private function getMultipleRatio($uid, $total_money)
    {
        switch ($total_money) {
            case 2000:
                $multiple_ratio = M('user')->where(['userid' => $uid])->getField('two_thousand_multiple_ratio');
                break;
            case 5000:
                $multiple_ratio = M('user')->where(['userid' => $uid])->getField('five_thousand_multiple_ratio');
                break;
            case 10000:
                $multiple_ratio = M('user')->where(['userid' => $uid])->getField('multiple_ratio');
                break;
            default :
                $multiple_ratio = 0;
        }
        return $multiple_ratio;
    }

    /**
     * 创建订单
     * @return bool|int|mixed
     */
    public function createOrder()
    {
        M()->startTrans();
        try {
            $uid = session('userid');
            $selProductList = session("selCar");
            if (!$selProductList) {
                throw new Exception('订单状态不正确');
            }
            $address_id = I('address_id');

            $addressInfo = M('address')->where(array('address_id' => $address_id))->find();
            if (empty($addressInfo)) {
                throw new Exception('地址不存在，请重新选择');
            }
            $buy_address = $addressInfo['province_id'] . $addressInfo['city_id'] . $addressInfo['country_id'] . $addressInfo['address'];

            #++生成大订单++
            $car_arr = $selProductList;
            //订单金额和数量
            $allTotalPrice = 0;
            $sellerList = array();
            foreach ($car_arr as $key => $carList) {
                foreach ($carList as $k => $v) {
                    $where = array();
                    $where['id'] = $v['pid'];
                    $com_info = M("product_detail")->where($where)->field('id,shangjia,price,name')->find();
                    if (kuncun($v['pid'], $v['num']) === false) {
                        throw new Exception('商品' . $com_info['name'] . '库存不足');
                    }

                    $sellerList[$com_info['shangjia']]['seller_id'] = $com_info['shangjia'];
                    if (!in_array($com_info['id'], $sellerList[$com_info['shangjia']]['com_ids'])) {
                        $sellerList[$com_info['shangjia']]['com_ids'][] = $com_info['id'];
                        $sellerList[$com_info['shangjia']]['com_nums'][] = $v['num'];
                        $sellerList[$com_info['shangjia']]['com_price'] = $sellerList[$com_info['shangjia']]['com_price'] + $com_info['price'] * $v['num'];
                        $sellerList[$com_info['shangjia']]['com_colors'][] = $v['color'];
                        $sellerList[$com_info['shangjia']]['com_sizes'][] = $v['size'];
                        $allTotalPrice = $allTotalPrice + $com_info['price'] * $v['num'];
                    }
                }
            }

            $orderIDs = array();
            $relation_id = '';
            $order_id = 0;
            if (count($sellerList) > 1) {
                $relation_id = "|";//关联ID
            }

            foreach ($sellerList as $key => $value) {
                //生成订单
                $order = array();
                $order_no = "M" . date("YmdHis") . rand(100000, 999999);
                $order['order_no'] = $order_no;
                $order['uid'] = $uid;
                $order['buy_name'] = $addressInfo['name'];
                $order['buy_phone'] = $addressInfo['telephone'];
                $order['buy_address'] = $buy_address;
                $order['status'] = 0;
                $order['order_sellerid'] = $value['seller_id'];
                $order['time'] = time();
                $order_id = M("order")->add($order);
                if (!$order_id) {
                    throw new Exception('创建订单失败');
                }

                $orderIDs[] = $order_id;
                $relation_id = $relation_id . $order_id . "|";
                $total_price = 0;  //总金额（默认price总金额）
                $ecological_total_assets = 0;//可用余额
                $flow_pass_card = 0;//流动通证
                $flow_amount = 0;//流动资产
                $product_integral = 0;//我的仓库
                $freight = 0;  //运费
                $is_duo_bao = 1;
                $shop_type = 0; //

                #++添加订单明细++
                foreach ($value['com_ids'] as $k => $v) {
                    $num = $value['com_nums'][$k];
                    $product = M("product_detail")->where(array("id" => $v))->find();
                    if ($product['is_duobao'] == 2) {
                        $is_duo_bao = 2;
                    }
                    $shop_type = $product['shop_type'];
                    $total_price += $product['price'] * $num;
                    $ecological_total_assets += $product['ecological_total_assets'] * $num;
                    $flow_pass_card += $product['flow_pass_card'] * $num;
                    $flow_amount += $product['flow_amount'] * $num;
                    $product_integral += $product['product_integral'] * $num;
                    //总运费
                    $freight += $product["freight"];
                    $detail["order_id"] = $order_id;
                    $detail["com_id"] = $product['id'];
                    $detail["com_name"] = $product['name'];
                    $detail["com_price"] = $product['price'];
                    $detail["com_ecological_total_assets"] = $product['ecological_total_assets'];
                    $detail["com_ecological_total_assets_one"] = $product['ecological_total_assets_one'];
                    $detail["com_ecological_total_assets_two"] = $product['ecological_total_assets_two'];
                    $detail["com_flow_pass_card"] = $product['flow_pass_card'];
                    $detail["com_flow_amount"] = $product['flow_amount'];
                    $detail["com_product_integral"] = $product['product_integral'];
                    $detail["com_num"] = $value['com_nums'][$k];
                    $detail["com_img"] = $product['pic'];
                    //商家id
                    $detail["shangjia"] = $product['shangjia'];
                    $detail["uid"] = $uid;
                    $detail["com_shoptype"] = $product['type_id'];
                    $detail["size"] = $value['com_sizes'][$k];
                    $detail["color"] = $value['com_colors'][$k];
                    $res = M("order_detail")->add($detail);
                    if (!$res) {
                        throw new Exception('创建订单失败');
                    }

                    //减少库存
                    $res = M("product_detail")->where(array("id" => $product['id']))->setDec("stock", $num);
                    if (!$res) {
                        throw new Exception('创建订单失败');
                    }
                }
                if ($shop_type == Constants::SHOP_TYPE_BAO_DAN) {
                    if (!StoreModel::is_can_bao_dan_buy($uid, $total_price)) {
                        throw new Exception('下单失败，已超5万。');
                    }
                }
                //修改订单总金额、总业绩
                $orderUpdate['buy_price'] = $total_price;
                $orderUpdate['ecological_total_assets'] = $ecological_total_assets;
                $orderUpdate['flow_pass_card'] = $flow_pass_card;
                $orderUpdate['flow_amount'] = $flow_amount;
                $orderUpdate['product_integral'] = $product_integral;
                $orderUpdate['total_yf'] = $freight;
                $orderUpdate['is_duobao'] = $is_duo_bao;
                $orderUpdate['shop_type'] = $shop_type;
                $res = M("order")->where(array("order_id" => $order_id))->save($orderUpdate);
                if ($res === false) {
                    throw new Exception('创建订单失败');
                }
            }

            if (count($sellerList) > 1) {
                $res = M("order")->where(array("order_id" => array("in", $orderIDs)))->setField("order_relation", $relation_id);
                if (!$res) {
                    throw new Exception('创建订单失败');
                }
            }

            M()->commit();
            return $order_id;
        } catch (Exception $ex) {
            $this->error = $ex->getMessage();
            M()->rollback();
            return false;
        }
    }

    /**
     * 订单支付
     * @return bool
     */
    public function orderPay()
    {
        $pay_type = trim(I("pay_type")); //支付方式
        $oid = intval(I("oid"));
        $uid = session('userid');
        $safe_pwd = trim(I('safe_pwd'));

        if (empty($oid)) {
            $this->error = '缺少订单参数';
            return false;
        }

        $order = M("order")->where(array("order_id" => $oid, "uid" => $uid, "status" => 0))->find();
        if (!$order) {
            $this->error = '该订单不存在或已支付';
            return false;
        }
        if (!empty($order['order_proof'])) {
            $this->error = '该订单已支付';
            return false;
        }

        if (empty($safe_pwd)) {
            $this->error = '交易密码不能为空';
            return false;
        }

        $res = Trans($uid, $safe_pwd);
        if (!$res['status']) {
            $this->error = $res['msg'];
            return false;
        }

        M()->startTrans();
        try {
            //不同商城购买的，支付方式不一样
            switch ($order['shop_type']) {
                case Constants::SHOP_TYPE_BAO_DAN :
                    $this->payBaoDanProduct($order, $uid);
                    break;
                case Constants::SHOP_TYPE_REGENERATE :
                    $this->payRegenerateProduct($order, $uid);
                    break;
                case Constants::SHOP_TYPE_SELF_SUPPORT :
                    $this->paySelfSupportProduct($order, $uid, $pay_type);
                    break;
                case Constants::SHOP_TYPE_LOVE :
                    $this->payLoveProduct($order, $uid);
                    break;
                default :
                    throw new Exception('未选择支付方式或支付方式不存在，请重新选择');
                    break;
            }

            $res = M('order')->where(['order_id' => $oid])->save(['status' => Constants::ORDER_STATUS_PAY, 'pay_type' => $pay_type, 'pay_time' => time()]);
            if (!$res) {
                throw new Exception('修改订单状态失败，请重试');
            }

            M()->commit();
            return true;
        } catch (Exception $ex) {
            $this->error = $ex->getMessage();
            M()->rollback();
            return false;
        }
    }

    /**
     * 消费通证区商品支付
     * @param $order
     * @param $uid
     * @throws Exception
     */
    private function payBaoDanProduct($order, $uid)
    {
        $total_price = $order['buy_price'] + $order['ecological_total_assets'];
        if (!StoreModel::is_can_bao_dan_buy($uid, $total_price)) {
            throw new Exception('下单失败，已超5万。');
        }

        $storeInfo = M('store')->where(['uid' => $uid])->field('cangku_num,fengmi_num')->find();
        if ($order['buy_price'] > $storeInfo['fengmi_num']) {
            throw new Exception('您的消费通证不足');
        }
        if ($order['ecological_total_assets'] > $storeInfo['cangku_num']) {
            throw new Exception('您的可用余额不足');
        }

        //扣除消费通证
        StoreModel::changStore($uid, 'fengmi_num', -$order['buy_price'], 7);
        //扣除可用余额
        StoreModel::changStore($uid, 'cangku_num', -$order['ecological_total_assets'], 16);
        //添加锁仓通证
        $this->addLockWarehouse($uid, $order['order_id']);
        //分享奖
        $this->shareAward($uid, $total_price, Constants::SHOP_TYPE_BAO_DAN);
        //感恩奖
        $thankful_id = trim(I('thankful_id'));//感恩ID
        if (!empty($thankful_id)) {
            $this->thanksgivingAward($uid, $thankful_id, $total_price);
            //订单添加感恩ID
            $res = M('order')->where(['order_id' => $order['order_id']])->save(['thankful_id' => $thankful_id]);
            if ($res === false) {
                throw new Exception('订单修改感恩ID失败');
            }
        }
        //直推释放
        $this->directRelease($uid, $total_price, Constants::SHOP_TYPE_BAO_DAN);
        //回馈奖
        $this->feedbackAward($uid, $total_price);
        //给自己添加业绩和上级添加业绩、绩效奖
        $this->addPidAchievement($uid, $total_price);
        //修改用户等级
        $user_level = M('user')->where(['userid' => $uid])->getField('level');
        if ($user_level == Constants::USER_LEVEL_NOT_ACTIVATE) {
            //激活用户
            $res = M('user')->where(['userid' => $uid])->save(['level' => Constants::USER_LEVEL_ACTIVATE, 'activation_time' => time()]);
            if (!$res) {
                throw new Exception('修改用户等级失败');
            }
        }
        //添加兑换额度
        $res = M('store')->where(['uid' => $uid])->setInc('exchange_amount', $total_price);
        if (!$res) {
            throw new Exception('新增兑换额度失败');
        }
    }

    /**
     * 再生商城商品支付
     * @param $order
     * @param $uid
     * @throws Exception
     */
    private function payRegenerateProduct($order, $uid)
    {
        if (self::bao_dan_order_num($uid) == 0) {
            throw new Exception('请先购买消费区商品');
        }

        $total_price = $order['buy_price'] + $order['ecological_total_assets'];

        $storeInfo = M('store')->where(['uid' => $uid])->field('cangku_num,fengmi_num')->find();
        if ($order['buy_price'] > $storeInfo['fengmi_num']) {
            throw new Exception('您的消费通证不足');
        }
        if ($order['ecological_total_assets'] > $storeInfo['cangku_num']) {
            throw new Exception('您的可用余额不足');
        }

        //扣除消费通证
        StoreModel::changStore($uid, 'fengmi_num', -$order['buy_price'], 7);
        //扣除可用余额
        StoreModel::changStore($uid, 'cangku_num', -$order['ecological_total_assets'], 16);
        //减少用户兑换额
        $this->reduceMultipleRatio($uid, $total_price);
        //新增锁仓仓库
        $this->regenerateAddLockWarehouse($uid, $order['order_id']);
        //分享奖
        $this->shareAward($uid, $total_price, Constants::SHOP_TYPE_REGENERATE);
        //直推释放
        $this->directRelease($uid, $total_price, Constants::SHOP_TYPE_REGENERATE);
        //给上级添加业绩和绩效奖
        $this->addPidAchievement($uid, $total_price);
    }

    /**
     * 自营商城商品支付
     * @param $order
     * @param $uid
     * @param $pay_type
     * @throws Exception
     */
    private function paySelfSupportProduct($order, $uid, $pay_type)
    {
        $orderDetail = M('order_detail')->where(['order_id' => $order['order_id']])->select();
        $payType = [Constants::PAY_TYPE_TWO, Constants::PAY_TYPE_THREE, Constants::PAY_TYPE_FOUR, Constants::PAY_TYPE_FIVE];
        if (!in_array($pay_type, $payType)) {
            throw new Exception('您选择的支付方式不存在，请重选');
        }

        $ecological_total_assets = 0;//可用余额
        $flow_pass_card = 0;//流动通证
        $flow_amount = 0;//流动资产
        $product_integral = 0;//我的仓库
        foreach ($orderDetail as $item) {
            switch ($pay_type) {
                case  Constants::PAY_TYPE_TWO :
                    $ecological_total_assets += $item['com_ecological_total_assets'] * $item['com_num'];
                    break;
                case $pay_type == Constants::PAY_TYPE_THREE :
                    $ecological_total_assets += $item['com_ecological_total_assets_one'] * $item['com_num'];
                    $flow_pass_card += $item['com_flow_pass_card'] * $item['com_num'];
                    break;
                case Constants::PAY_TYPE_FOUR :
                    $ecological_total_assets += $item['com_ecological_total_assets_two'] * $item['com_num'];
                    $flow_amount += $item['com_flow_amount'] * $item['com_num'];
                    break;
                case Constants::PAY_TYPE_FIVE :
                default :
                    $product_integral += $item['com_product_integral'] * $item['com_num'];
            }
        }

        $storeInfo = M('store')->where(['uid' => $uid])->field('cangku_num,can_flow_amount,current_assets,product_integral')->find();
        if ($pay_type == Constants::PAY_TYPE_TWO) {
            if ($ecological_total_assets > $storeInfo['cangku_num']) {
                throw new Exception('您的可用余额不足');
            }
            //扣除可用余额
            StoreModel::changStore($uid, 'cangku_num', -$ecological_total_assets, 16);
        } elseif ($pay_type == Constants::PAY_TYPE_THREE) {
            if ($ecological_total_assets > $storeInfo['cangku_num']) {
                throw new Exception('您的可用余额不足');
            }
            if ($flow_pass_card > $storeInfo['can_flow_amount']) {
                throw new Exception('您的流动通证不足');
            }

            //扣除可用余额
            StoreModel::changStore($uid, 'cangku_num', -$ecological_total_assets, 16);
            //扣除流动通证
            StoreRecordModel::addRecord($uid, 'can_flow_amount', -$flow_pass_card, Constants::STORE_TYPE_CAN_FLOW, 5);
        } elseif ($pay_type == Constants::PAY_TYPE_FOUR) {
            if ($ecological_total_assets > $storeInfo['cangku_num']) {
                throw new Exception('您的可用余额不足');
            }
            if ($flow_amount > $storeInfo['current_assets']) {
                throw new Exception('您的流动资产不足');
            }
            //扣除可用余额
            StoreModel::changStore($uid, 'cangku_num', -$ecological_total_assets, 16);
            //扣除流动资产
            StoreRecordModel::addRecord($uid, 'current_assets', -$flow_amount, Constants::STORE_TYPE_CURRENT_ASSETS, 1);
        } else {
            if ($product_integral > $storeInfo['product_integral']) {
                throw new Exception('您的我的仓库不足');
            }
            //扣除我的仓库
            StoreRecordModel::addRecord($uid, 'product_integral', -$product_integral, Constants::STORE_TYPE_PRODUCT_INTEGRAL, 1);
        }

        //从流动资产释放到流动通证
        if ($ecological_total_assets > 0) {
            StoreModel::flowReleasePassCard($uid, $ecological_total_assets, 6, 9);
            //将增加的可用流动通证添加到最新的锁仓中
            LockWarehousePassModel::addReleaseAccount($uid, $ecological_total_assets);
        }

        $data['ecological_total_assets'] = $ecological_total_assets;
        $data['flow_pass_card'] = $flow_pass_card;
        $data['flow_amount'] = $flow_amount;
        $data['flow_amount'] = $flow_amount;
        $data['product_integral'] = $product_integral;
        $res = M('order')->where(['order_id' => $order['order_id']])->save($data);
        if ($res === false) {
            throw new Exception('修改订单金额失败');
        }
    }

    /**
     * 爱心商城商品支付
     * @param $order
     * @param $uid
     * @throws Exception
     */
    private function payLoveProduct($order, $uid)
    {
        $storeInfo = M('store')->where(['uid' => $uid])->field('cangku_num')->find();

        if ($order['ecological_total_assets'] > $storeInfo['cangku_num']) {
            throw new Exception('您的可用余额不足');
        }

        //扣除可用余额
        StoreModel::changStore($uid, 'cangku_num', -$order['ecological_total_assets'], 16);
    }

    /**
     * 扣除用户兑换额
     * @param $uid
     * @param $amount
     * @throws Exception
     * @author ldz
     * @time 2020/3/3 17:09
     */
    private function reduceMultipleRatio($uid, $amount)
    {
        $repeat_purchase_des_ratio = UserModel::repeat_purchase_des_ratio;//每次扣减兑换额
        $userInfo = M('user')->where(['userid' => $uid])->field('multiple_ratio,five_thousand_multiple_ratio,two_thousand_multiple_ratio')->find();
        if ($amount == 10000) {
            if ($userInfo['multiple_ratio'] > UserModel::min_multiple_ratio) {
                $res = M('user')->where(['userid' => $uid])->setDec('multiple_ratio', $repeat_purchase_des_ratio);
                if (!$res) {
                    throw new Exception('扣除用户兑换额失败');
                }
            }
        } elseif ($amount == 5000) {
            if ($userInfo['five_thousand_multiple_ratio'] > UserModel::two_five_min_multiple_ratio) {
                $res = M('user')->where(['userid' => $uid])->setDec('five_thousand_multiple_ratio', $repeat_purchase_des_ratio);
                if (!$res) {
                    throw new Exception('扣除用户兑换额失败');
                }
            }
        } elseif ($amount == 2000) {
            if ($userInfo['two_thousand_multiple_ratio'] > UserModel::two_five_min_multiple_ratio) {
                $res = M('user')->where(['userid' => $uid])->setDec('two_thousand_multiple_ratio', $repeat_purchase_des_ratio);
                if (!$res) {
                    throw new Exception('扣除用户兑换额失败');
                }
            }
        }
    }

    /**
     * 添加锁仓通证
     * @param $uid
     * @param $order_id
     * @throws Exception
     */
    private function addLockWarehouse($uid, $order_id)
    {
        $orderDetail = M('order_detail')->where(['order_id' => $order_id])->field('com_num,com_price,com_ecological_total_assets')->select();
        $user_lock_num = M('lock_warehouse_pass')->where(['uid' => $uid])->count();
        foreach ($orderDetail as $item) {
            for ($i = 0; $i < $item['com_num']; $i++) {
                $total_money = $item['com_price'] + $item['com_ecological_total_assets'];
                $release_amount = 0;
                if ($user_lock_num == 0) {
                    $release_amount += M('store')->where(['uid' => $uid])->getField('can_flow_amount');
                }
                $lock_warehouse = [
                    'uid' => $uid,
                    'amount' => $total_money,
                    'release_amount' => $release_amount,
                    'multiple_ratio' => $this->getMultipleRatio($uid, $total_money),
                    'create_time' => time()
                ];
                $res = M('lock_warehouse_pass')->add($lock_warehouse);
                if (!$res) {
                    throw new Exception('添加锁仓通证失败');
                }
            }
        }
    }

    /**
     * 复购添加锁仓通证
     * @param $uid
     * @param $order_id
     * @throws Exception
     */
    private function regenerateAddLockWarehouse($uid, $order_id)
    {
        $day = M('config')->where(['name' => 'lock_warehouse_day_one'])->getField("value");
        $multiple = M('config')->where(['name' => 'lock_warehouse_multiple_one'])->getField("value");
        $orderDetail = M('order_detail')->where(['order_id' => $order_id])->field('com_num,com_price,com_ecological_total_assets')->select();
        $arrAddLockWarehouseIDs = [];
        $user_lock_num = M('lock_warehouse_pass')->where(['uid' => $uid])->count();
        foreach ($orderDetail as $item) {
            $total_price = $item['com_price'] + $item['com_ecological_total_assets'];
            $growth_amount = $multiple * $total_price;
            for ($i = 0; $i < $item['com_num']; $i++) {
                $release_amount = $growth_amount;
                if ($user_lock_num == 0) {
                    $release_amount += M('store')->where(['uid' => $uid])->getField('can_flow_amount');
                }
                $lock_warehouse = [
                    'uid' => $uid,
                    'amount' => $total_price,
                    'growth_amount' => $growth_amount,
                    'release_amount' => $release_amount,
                    'multiple' => $multiple,
                    'multiple_ratio' => $this->getMultipleRatio($uid, $total_price),
                    'day' => $day,
                    'status' => Constants::LOCK_WAREHOUSE_GROWTH,
                    'shop_type' => Constants::SHOP_TYPE_REGENERATE,
                    'start_time' => time(),
                    'create_time' => time(),
                ];
                $new_id = M('lock_warehouse_pass')->add($lock_warehouse);
                if (!$new_id) {
                    throw new Exception('添加锁仓通证失败');
                }
                $arrAddLockWarehouseIDs[] = $new_id;
            }
        }
        //复购修改锁仓仓库一条记录为锁仓仓库
        $this->changeLockWarehouseAgainBug($uid, $arrAddLockWarehouseIDs);
    }

    /**
     * 复购修改一条锁仓仓库为已复购
     * @param $uid
     * @param $arrAddLockWarehouseIDs
     * @throws Exception
     */
    private function changeLockWarehouseAgainBug($uid, $arrAddLockWarehouseIDs)
    {
        $where['uid'] = $uid;
        $where['is_release'] = 1;
        $where['flow_amount'] = 0;
        $where['is_again_bug'] = 0;
        $where['status'] = Constants::LOCK_WAREHOUSE_RELEASE;
        $lock_warehouse_id = M('lock_warehouse_pass')->where($where)->order('create_time asc,id asc')->getField('id');
        if (empty($lock_warehouse_id)) {
            $whereTwo['uid'] = $uid;
            $whereTwo['is_again_bug'] = 0;
            $whereTwo['status'] = ['egt', Constants::LOCK_WAREHOUSE_GROWTH];
            $whereTwo['id'] = ['not in', $arrAddLockWarehouseIDs];
            $lock_warehouse_id = M('lock_warehouse_pass')->where($whereTwo)->order('create_time asc,id asc')->getField('id');
            if (empty($lock_warehouse_id)) {
                $whereTwo['status'] = Constants::LOCK_WAREHOUSE_NOT_SELECT;
                $lock_warehouse_id = M('lock_warehouse_pass')->where($whereTwo)->order('create_time asc,id asc')->getField('id');
            }
        }

        if ($lock_warehouse_id) {
            $res = M('lock_warehouse_pass')->where(['id' => $lock_warehouse_id])->save(['is_again_bug' => 1]);
            if (!$res) {
                throw new Exception('修改锁仓仓库复购状态失败');
            }
        }
    }

    /**
     * 分享奖
     * @param $uid
     * @param $amount
     * @param $shop_type
     * @throws Exception
     */
    private function shareAward($uid, $amount, $shop_type)
    {
        if (!self::is_again_bug($uid)) {
            $where['uid'] = $uid;
            $where['status'] = ['gt', 0];
            $where['shop_type'] = $shop_type;
            $is_order = M('order')->where($where)->getField('order_id');
            $getShareID = 0;
            if ($is_order) {//第二次购买后，都是给自己
                $getShareID = $uid;
            } else { //第一次购买，则分享奖给用户推荐人所得
                $pid = M('user')->where(['userid' => $uid])->getField('pid');
                if ($pid) {
                    $pidLevel = M('user')->where(['userid' => $pid])->getField('level');
                    if ($pidLevel >= Constants::USER_LEVEL_ACTIVATE) {
                        $getShareID = $pid;
                    }
                }
            }
            if ($getShareID) {
                $is_again_bug = LockWarehousePassModel::isNeedAgainBuy($getShareID);
                $max_order_money = $this->getUserMaxOrderPrice($getShareID, $shop_type);//获取最大订单金额
                if ($max_order_money >= $amount) {
                    $userGetAmount = $amount;
                } else {
                    $userGetAmount = $max_order_money;
                }
                if ($max_order_money == 0) {
                    $userGetAmount = $amount;
                }

                if (!$is_again_bug && $userGetAmount > 0) {
                    //分享到可用流动通证
                    $share_to_can_flow_ratio = M('config')->where(['name' => 'share_to_can_flow_ratio'])->getField('value');//分享到可用流动通证比例
                    $share_can_flow = formatNum2($userGetAmount * $share_to_can_flow_ratio);
                    StoreRecordModel::addRecord($getShareID, 'can_flow_amount', $share_can_flow, Constants::STORE_TYPE_CAN_FLOW, 3, $uid);
                    //将增加的可用流动通证添加到最新的锁仓中
                    LockWarehousePassModel::addReleaseAccount($getShareID, $share_can_flow);

                    //到百宝箱分享奖
                    $share_to_flow_ratio = M('config')->where(['name' => 'share_to_flow_ratio'])->getField('value');//到百宝箱分享奖比例
                    $share_amount = formatNum2($userGetAmount * $share_to_flow_ratio);
                    StoreRecordModel::addRecord($getShareID, 'share_amount', $share_amount, Constants::STORE_TYPE_SHARE_REWARD, 1, $uid, $share_amount, 0);
                }
            }
        }
    }

    /**
     * 感恩奖
     * @param int $uid 操作人ID
     * @param int $thankful_id 感恩ID
     * @param double $amount 感恩金额
     * @throws Exception
     */
    private function thanksgivingAward($uid, $thankful_id, $amount)
    {
        if (empty($thankful_id)) {
            throw new Exception('请输入感恩ID');
        }

        if ($uid == $thankful_id) {
            throw new Exception('感恩ID不能为自己的ID,请重新输入');
        }

        $userInfo = M('user')->where(['userid' => $thankful_id])->field('userid,level')->find();
        if (empty($userInfo)) {
            throw new Exception('您输入的感恩ID不存在，请重新输入');
        }

        if ($userInfo['level'] == Constants::USER_LEVEL_NOT_ACTIVATE) {
            throw new Exception('您输入的感恩ID未激活，请重新输入');
        }

        $storeInfo = M('store')->where(['uid' => $thankful_id])->field('total_release')->find();
        if ($storeInfo['total_release'] >= Constants::total_release) {
            throw new Exception('该感恩ID不能获得感恩奖');
        }
        $thankful_award_ratio = M('config')->where(['name' => 'thankful_award_ratio'])->getField('value');
        $thankful_amount = formatNum2($amount * $thankful_award_ratio);
        StoreRecordModel::addRecord($thankful_id, 'thankful_amount', $thankful_amount, Constants::STORE_TYPE_THANKFUL_REWARD, 0, $uid);
    }

    /**
     * 直推释放
     * @param $uid
     * @param $amount
     * @param $shop_type
     * @throws Exception
     */
    private function directRelease($uid, $amount, $shop_type)
    {
        $where['uid'] = $uid;
        $where['status'] = ['gt', 0];
        $where['shop_type'] = $shop_type;
        $is_order = M('order')->where($where)->getField('order_id');
        $getDirectID = 0;
        if ($is_order) {//第二次购买后，都是给自己
            $getDirectID = $uid;
        } else {
            $pid = M('user')->where(['userid' => $uid])->getField('pid');
            if ($pid) {
                $pidLevel = M('user')->where(['userid' => $pid])->getField('level');
                if ($pidLevel >= Constants::USER_LEVEL_ACTIVATE) {
                    $getDirectID = $pid;
                }
            }
        }
        if ($getDirectID) {
            $direct_release_ratio = M('config')->where(['name' => 'direct_release_ratio'])->getField('value');
            $release_num = formatNum2($direct_release_ratio * $amount);
            $storeInfo = M('store')->where(['uid' => $getDirectID])->field('purchase_integral,current_assets')->find();
            if ($storeInfo['current_assets'] > $release_num) {
                $current_assets = $release_num;
            } else {
                $current_assets = $storeInfo['current_assets'];
            }
            if ($current_assets > 0) {
                //扣除流动资产
                StoreRecordModel::addRecord($getDirectID, 'current_assets', -$current_assets, Constants::STORE_TYPE_CURRENT_ASSETS, 0, $uid);

                $remain_amount = $release_num - $current_assets;
                //扣除签到账户
                if (($remain_amount > 0) && ($storeInfo['purchase_integral'] >= Constants::sign_in_min_amount)) {
                    if ($storeInfo['purchase_integral'] > $remain_amount) {
                        $purchase_integral = $remain_amount;
                    } else {
                        $purchase_integral = $storeInfo['purchase_integral'];
                    }

                    //扣除签到账户
                    PurchaseRecordModel::addRecord($getDirectID, -$purchase_integral, 4);
                }
            }
        }
    }

    /**
     * 回馈
     * @param $uid
     * @param $amount
     * @throws Exception
     */
    private function feedbackAward($uid, $amount)
    {
        $path = M('user')->where(['userid' => $uid])->getField('path');
        $arrPath = getArray($path);
        foreach ($arrPath as $pid) {
            $feedback_ratio = M('user')->where(['userid' => $pid])->getField('feedback_ratio');
            if ($feedback_ratio == 0) {
                continue;
            }
            $feedback_amount = formatNum2($amount * $feedback_ratio);
            StoreRecordModel::addRecord($pid, 'feedback_amount', $feedback_amount, Constants::STORE_TYPE_FEEDBACK, 0, $uid);
        }
    }

    /**
     * 用户新增业绩，和用户上级们添加业绩
     * @param $uid
     * @param $amount
     * @throws Exception
     */
    private function addPidAchievement($uid, $amount)
    {
        $userInfo = M('user')->where(['userid' => $uid])->field('path,level')->find();

        $res = M('store')->where(array('uid' => $uid))->setInc('total_release', $amount);
        if (!$res) {
            throw new Exception('给自己添加业绩失败');
        }

        $arrPath = array_reverse(getArray($userInfo['path']));
        foreach ($arrPath as $pid) {
            $level = M('user')->where(['userid' => $pid])->getField('level');
            if ($level >= Constants::USER_LEVEL_ACTIVATE) {
                $res = M('store')->where(array('uid' => $pid))->setInc('total_release', $amount);
                if (!$res) {
                    throw new Exception('给上级添加业绩失败');
                }
            }
        }
        //绩效奖
        if (!self::is_again_bug($uid)) {
            $this->meritsAward($uid, $arrPath, $amount);
        }
    }

    /**
     * 绩效奖
     * @param $uid
     * @param $arrPath
     * @param $amount
     * @throws Exception
     */
    private function meritsAward($uid, $arrPath, $amount)
    {
        $data = [];
        $userModel = new UserModel();
        foreach ($arrPath as $pid) {
            $data[] = [
                'user_id' => $pid,
                'old_level' => M('user')->where(['userid' => $pid])->getField('level'),
                'level' => $userModel->updateUserLevel($pid)
            ];
        }

        $arrLevel = [];
        foreach ($data as $item) {
            if ($item['level'] < Constants::USER_LEVEL_A_ONE) {
                continue;
            }
            $ratio = 0;
            if (in_array($item['level'], $arrLevel)) {//存在
                switch ($item['level']) {
                    case Constants::USER_LEVEL_A_SIX :
                        $ratio = 0.01;
                        break;
                    case Constants::USER_LEVEL_A_FIVE :
                        $ratio = 0.01;
                        break;
                    case Constants::USER_LEVEL_A_FOUR :
                        $ratio = 0.02;
                        break;
                    case Constants::USER_LEVEL_A_THREE :
                        $ratio = 0.02;
                        break;
                }
            } else {//不存在
                rsort($arrLevel);
                $level_ratio = $this->getRatio($item['level']);
                $big_ratio = $arrLevel ? $this->getRatio($arrLevel[0]) : 0;
                $ratio = $level_ratio - $big_ratio;
                if ($ratio > 0) {
                    $arrLevel[] = $item['level'];
                }
            }

            if ($ratio <= 0) {
                continue;
            }

            if ($item['old_level'] < Constants::USER_LEVEL_A_ONE) {
                $total_release_a_one = UserModel::total_release_a_one;
                $total_release = M('store')->where(['uid' => $item['user_id']])->getField('total_release');
                $more_release = $total_release - $total_release_a_one;
                if ($more_release >= $amount) {
                    $release_amount = formatNum2($amount * $ratio);
                } else {
                    $release_amount = formatNum2($more_release * $ratio);
                }
            } else {
                $release_amount = formatNum2($amount * $ratio);
            }

            if ($release_amount > 0) {
                //绩效奖
                StoreRecordModel::addRecord($item['user_id'], 'merits_amount', $release_amount, Constants::STORE_TYPE_MERITS_REWARD, 0, $uid);
                //可用流动通证
                StoreRecordModel::addRecord($item['user_id'], 'can_flow_amount', $release_amount, Constants::STORE_TYPE_CAN_FLOW, 4, $uid);
                //将增加的可用流动通证添加到最新的锁仓中
                LockWarehousePassModel::addReleaseAccount($item['user_id'], $release_amount);
            }
        }
    }

    private function getRatio($level)
    {
        switch ($level) {
            case Constants::USER_LEVEL_A_SIX :
                $ratio = 0.17;
                break;
            case Constants::USER_LEVEL_A_FIVE :
                $ratio = 0.16;
                break;
            case Constants::USER_LEVEL_A_FOUR :
                $ratio = 0.14;
                break;
            case Constants::USER_LEVEL_A_THREE :
                $ratio = 0.12;
                break;
            case Constants::USER_LEVEL_A_TWO :
                $ratio = 0.09;
                break;
            case Constants::USER_LEVEL_A_ONE:
                $ratio = 0.05;
                break;
            default :
                $ratio = 0;
        }
        return $ratio;
    }

    /**
     * 获取消费区，自营商城，支付成功的最大单金额
     * @param $uid
     * @param $shop_type
     * @return int
     * @author ldz
     * @time 2020/3/26 16:31
     */
    public function getUserMaxOrderPrice($uid, $shop_type)
    {
        $where['uid'] = $uid;
        $where['shop_type'] = $shop_type;
        $where['status'] = ['EGT', Constants::ORDER_STATUS_PAY];
        $order = M('order')->where($where)->field('sum(buy_price+ecological_total_assets) as total_money')->group('order_id')->order('total_money desc')->find();
        return $order ? $order['total_money'] : 0;
    }


}