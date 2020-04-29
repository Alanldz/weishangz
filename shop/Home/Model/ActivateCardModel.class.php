<?php
/**
 * Created by PhpStorm.
 * Date: 2018/12/15
 * Time: 10:43
 */

namespace Home\Model;

use Common\Util\Constants;
use Think\Exception;

class ActivateCardModel extends \Common\Model\ActivateCardModel
{

    /**
     * 通过id获取激活卡信息
     * @param int $id
     * @param string $field
     * @return mixed
     * @time 2018-12-16 11:44:57
     */
    public static function getCardInfoById($id, $field = '*')
    {
        return M('activate_card')->where(['id' => $id])->field($field)->find();
    }

    /**
     * 搜索列表
     * @return array
     * @time 2018-12-15 12:39:07
     */
    public static function search($where = [])
    {
        $models = M('order');
        //分页
        $p = Fgetpage($models, $where);
        $page = $p->show();
        $list = $models->where($where)->order('order_id desc')->select();
        $data = ['list' => $list, 'page' => $page];

        return $data;
    }

    /**
     * 我要进货
     * @return bool
     * @time 2018-12-16 10:48:59
     */
    public function mark_card()
    {
        try {
            M()->startTrans();   //开启事务
            $uid = session('userid');
            list($productInfo, $addressInfo) = $this->validateAddData($uid);

            if ($productInfo['pay_type'] == Constants::PAY_TYPE_TWO) {
                StoreModel::changStore($uid, 'cangku_num', -$productInfo['total_money'], 10);
            }

            $this->createOrder($productInfo, $addressInfo, $uid);

            M()->commit();
            return true;
        } catch (\Exception $e) {
            M()->rollback();
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * 验证
     * @return array
     * @throws Exception
     */
    private function validateAddData($uid)
    {
        $purchase_quantity = intval(I('purchase_quantity'));
        if ($purchase_quantity <= 0) {
            throw new Exception('请输入进货数量');
        }

        $pay_type = intval(I('pay_type'));
        if ($pay_type != Constants::PAY_TYPE_ONE && $pay_type != Constants::PAY_TYPE_TWO) {
            throw new Exception('您选择的支付方式不存在');
        }

        $userInfo = M('user')->where(['userid' => $uid])->field('level,pid')->find();
        if (empty($userInfo['pid'])) {
            throw new Exception('您没有上级，无法进货');
        }

        if ($userInfo['level'] == Constants::USER_LEVEL_NOT_ACTIVATE) {
            throw new Exception('您还未激活，不能进货哦');
        }
        if ($userInfo['level'] == Constants::USER_LEVEL_A_FOUR) {
            $user_level = Constants::USER_LEVEL_A_THREE;
        } else {
            $user_level = $userInfo['level'];
        }

        $productInfo = M('product_detail')->where(['level' => $user_level])->field('id,level,price,activate_buy_num,name,pic')->find();
        if (empty($productInfo)) {
            throw new Exception('您的等级进货单价不存在，请联系管理员');
        }
        $productInfo['purchase_quantity'] = $purchase_quantity;
        $productInfo['pay_type'] = $pay_type;
        $productInfo['pid'] = $userInfo['pid'];

        if ($pay_type == Constants::PAY_TYPE_TWO) {
            $total_money = $productInfo['price'] * $purchase_quantity;
            $cangku_num = M('store')->where(['uid' => $uid])->getField('cangku_num');
            if ($total_money > $cangku_num) {
                throw new Exception('您的余额不足');
            }
            $productInfo['total_money'] = $total_money;
        }

        $delivery_type = I('delivery_type', 1);
        if ($delivery_type != 1 && $delivery_type != 2) {
            throw new Exception('您选择的货运方式不存在');
        }
        $productInfo['delivery_type'] = $delivery_type;
        $address = [];
        if ($delivery_type == 2) {
            $address = I('address');
            if (empty($address['name'])) {
                throw new Exception('请输入收货人姓名');
            }
            if (empty($address['telephone'])) {
                throw new Exception('请输入收货人手机号');
            }
            if (!isMobile($address['telephone'])) {
                throw new Exception('收货人手机号有误');
            }

            if ($address['province_id'] == '' || $address['province_id'] == '--请选择省份--') {
                throw new Exception('请选择省份');
            }

            if ($address['city_id'] == '' || $address['city_id'] == '--请选择市--') {
                throw new Exception('请选择市');
            }

            if ($address['country_id'] == '' || $address['country_id'] == '--请选择区--') {
                throw new Exception('请选择区');
            }
            if (empty($address['address'])) {
                throw new Exception('请输入详细地址');
            }
        }

        $safety_salt = trim(I('safety_salt'));
        if (empty($safety_salt)) {
            throw new Exception('请输入您的交易密码');
        }
        D('Home/User')->Trans('', $safety_salt);

        return [$productInfo, $address];
    }

    /**
     * 邮寄，创建订单
     * @param $productInfo
     * @param $address
     * @param $user_id
     * @throws Exception
     */
    private function createOrder($productInfo, $address, $user_id)
    {
        //创建收货地址
        $address['member_id'] = $user_id;
        $res = M('address')->add($address);
        if (!$res) {
            throw new Exception('创建收货地址失败');
        }

        //创建订单
        $order = array();
        $order_no = "M" . date("YmdHis") . rand(100000, 999999);
        $order['order_no'] = $order_no;
        $order['uid'] = $user_id;
        $order['buy_price'] = $productInfo['price'] * $productInfo['purchase_quantity'];
        $order['pay_type'] = $productInfo['pay_type'];
        $order['status'] = 0;
        $order['order_sellerid'] = $productInfo['pid'];
        $order['time'] = time();
        if ($productInfo['delivery_type'] == 1) {//货运方式
            $order['is_duobao'] = 2;
        } else {
            $order['is_duobao'] = 1;
            $order['buy_name'] = $address['name'];
            $order['buy_phone'] = $address['telephone'];
            $order['buy_address'] = $address['province_id'] . $address['city_id'] . $address['country_id'] . $address['address'];
            $order['province_id'] = $address['province_id'];
            $order['city_id'] = $address['city_id'];
            $order['country_id'] = $address['country_id'];
            $order['address'] = $address['address'];
        }
        $order_id = M("order")->add($order);
        if (!$order_id) {
            throw new Exception('订单创建失败');
        }

        //添加订单明细
        $detail = array();
        $detail["order_id"] = $order_id;
        $detail["com_id"] = $productInfo['id'];
        $detail["com_name"] = $productInfo['name'];
        $detail["com_price"] = $productInfo['price'];
        $detail["com_num"] = $productInfo['purchase_quantity'];
        $detail["com_img"] = $productInfo['pic'];
        $detail["uid"] = $user_id;
        $res = M("order_detail")->add($detail);
        if (!$res) {
            throw new Exception('订单明细创建失败');
        }
    }

    /**
     * 赠送激活卡
     * @return bool
     * @time 2018-12-16 13:42:58
     */
    public function gift_card()
    {
        M()->startTrans();   //开启事务
        try {
            $uid = session('userid');
            $card_id = intval(I('card_id', 0));
            $give_user_id = intval(trim(I('give_user_id', 0)));
            $mobile = trim(I('mobile', ''));
            $safety_salt = trim(I('safety_salt', ''));
            if (empty($card_id)) {
                throw new \Exception('参数错误');
            }
            if (empty($give_user_id)) {
                throw new \Exception('请输入会员ID');
            }
            if (empty($mobile)) {
                throw new \Exception('请输入手机后四位');
            }
            if (empty($safety_salt)) {
                throw new \Exception('请输入您的交易密码');
            }
            if ($give_user_id == $uid) {
                throw new \Exception('不能赠送给自己');
            }
            //验证手机号码后四位
            $give_user_info = M('user')->where(['userid' => $give_user_id])->field('mobile,level,service_center_account')->find();
            if (empty($give_user_info)) {
                throw new \Exception('赠送的会员ID不存在,请重新输入');
            }
            if ($give_user_info['level'] == 0) {
                throw new \Exception('赠送的会员ID未激活,不能赠送');
            }
            $tmobile = $give_user_info['mobile'];

            $tmobile = substr($tmobile, -4);
            if ($tmobile != $mobile) {
                throw new \Exception('您输入的手机号码后四位有误');
            }

            //验证交易密码
            $userModel = D('Home/User');
            $userInfo = $userModel->where(array('userid' => $uid))->Field('account,empty_order,service_center_account,service_center')->find();
            $userModel->Trans($userInfo['account'], $safety_salt);
            if ($userInfo['empty_order']) {
                throw new \Exception('不可赠送，请留言给后台');
            }

            $cardInfo = M('activate_card')->where(['id' => $card_id, 'uid' => $uid, 'delete_time' => ''])->find();
            if (empty($cardInfo)) {
                throw new \Exception('该激活卡不存在，请从新选择');
            }

            //判断赠送的人跟自己是不是同一个服务中心，或者服务中心为自己
            if ($userInfo['service_center']) {
                if ($give_user_info['service_center_account'] != $uid && $give_user_info['service_center_account'] != $userInfo['service_center_account']) {
                    throw new \Exception('赠送失败，非同一个服务中心');
                }
            } else {
                if ($give_user_info['service_center_account'] != $userInfo['service_center_account']) {
                    throw new \Exception('赠送失败，非同一个服务中心');
                }
                $countCard = M('activate_card')->where(['activation_code' => $cardInfo['activation_code']])->count();
                if ($countCard > 1) {
                    throw new \Exception('该激活卡是赠送所得，不可赠送他人');
                }
            }

            $changeData['delete_time'] = date('YmdHis');
            $res = M('activate_card')->where(['id' => $card_id, 'uid' => $uid])->save($changeData);
            if (!$res) {
                throw new \Exception('删除激活卡失败');
            }
            //赠送激活卡
            $addCard = [
                'activation_code' => $cardInfo['activation_code'],
                'uid' => $give_user_id,
                'level' => $cardInfo['level'],
                'create_time' => date('YmdHis')
            ];
            $res = M('activate_card')->add($addCard);
            if (!$res) {
                throw new \Exception('赠送失败');
            }
            //添加激活卡记录
            $cardRecard = [
                'uid' => $uid,
                'activate_card_id' => $card_id,
                'userid' => $give_user_id,
                'level' => $cardInfo['level'],
                'type' => 2,
                'create_time' => date('YmdHis')
            ];
            $res = M('activate_card_record')->add($cardRecard);
            if (!$res) {
                M()->rollback();
                ajaxReturn('添加赠送记录失败', 0);
            }
            M()->commit();
            return true;
        } catch (\Exception $e) {
            M()->rollback();
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * 生成随机字符串（数字字母大小写）
     * @param int $lenth 长度
     * @return string 字符串
     */
    function create_randomstr_s($lenth = 32)
    {
        $str = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $activation_code = "";
        for ($i = 0; $i < $lenth; $i++) {
            $activation_code .= $str{mt_rand(0, 32)};    //生成php随机数
        }
        $count = M('activate_card')->where(['activation_code' => $activation_code, 'delete_time' => ''])->count();
        if ($count > 0) {
            return $this->create_randomstr_s();
        }
        return $activation_code;
    }

    /**
     * 升级申请
     * @return bool
     * @author ldz
     * @time 2020/4/24 10:26
     */
    public function levelApply()
    {
        try {
            M()->startTrans();
            $level = intval(I('level'));
            $trade_pwd = trim(I('trade_pwd'));
            if (empty($level)) {
                throw new Exception('请选择升级级别');
            }
            if (empty($trade_pwd)) {
                throw new Exception('请输入您的交易密码');
            }
            $user_id = session('userid');
            $userModel = D('Home/User');
            $userInfo = $userModel->where(['userid' => $user_id])->field('account,level')->find();
            //验证交易密码
            $userModel->Trans($userInfo, $trade_pwd);
            if ($userInfo['level'] >= $level) {
                throw new Exception('您当前等级大于选择升级级别，请重选');
            }
            $is_apply = M('level_list')->where(['uid' => $user_id, 'status' => Constants::VERIFY_STATUS_WAIT])->find();
            if ($is_apply) {
                throw new Exception('您申请的还未处理，请耐性等待审核');
            }

            $addData = [
                'uid' => $user_id,
                'level' => $level,
                'time' => date('YmdHis', time())
            ];
            $res = M('level_list')->add($addData);
            if (!$res) {
                throw new Exception('申请失败');
            }

            M()->commit();
            return true;
        } catch (Exception $ex) {
            M()->rollback();
            $this->error = $ex->getMessage();
            return false;
        }
    }

    public function determinePayment()
    {
        try {
            M()->startTrans();
            $uid = session('userid');
            $order_id = I('order_id');
            $order = M('order')->where(['order_id' => $order_id, 'status' => 0, 'order_sellerid' => $uid])->find();
            if (empty($order)) {
                throw new Exception('该订单不存在请刷新页面重试');
            }

            $order_detail = M('order_detail')->where(['order_id' => $order_id])->field('com_num')->find();
            $storeInfo = M('store')->where(['uid' => $uid])->field('cloud_library')->find();
            if ($order_detail['com_num'] > $storeInfo['cloud_library']) {
                throw new Exception('您的云库不足');
            }

            //扣除云库
            StoreRecordModel::addRecord($uid, 'cloud_library', -$order_detail['com_num'], Constants::STORE_TYPE_CLOUD_LIBRARY, 3, $order['uid']);

            //修改订单状态
            $update['pay_money'] = $order['buy_price'];
            if ($order['is_duobao'] == 2) {
                $update['status'] = 3;
            } else {
                $update['status'] = 1;
            }
            $update['pay_time'] = time();
            $res = M('order')->where(['order_id' => $order_id])->save($update);
            if (!$res) {
                throw new Exception('修改订单状态失败');
            }

            if ($order['is_duobao'] == 2) {
                StoreRecordModel::addRecord($order['uid'], 'cloud_library', $order_detail['com_num'], Constants::STORE_TYPE_CLOUD_LIBRARY, 3);
            }

            $path = M('user')->where(['userid' => $order['uid']])->getField('path');
            $arrayPath = array_reverse(getArray($path));
            $userModel = new UserModel();
            $address['province_id'] = $order['province_id'];
            $address['city_id'] = $order['city_id'];
            $userModel->user_id = $order['uid'];
            $userModel->award($uid, $arrayPath, $order_detail['com_num'], $address);

            M()->commit();
            return true;
        } catch (Exception $ex) {
            M()->rollback();
            $this->error = $ex->getMessage();
            return false;
        }
    }

}