<?php

namespace Shop\Controller;

use Admin\Model\InvestmentGradeModel;
use Common\Model\UcoinsModel;
use Common\Util\Constants;
use Shop\Model\OrderModel;
use Shop\Model\StoreModel;
use Shop\Model\UserModel;
use Shop\Model\VerifyListModel;

class MemberController extends CommonController
{

    //所有订单
    public function allorder()
    {
        $uid = session('userid');
        $s = I("s");
        $where['uid'] = $uid;
        $where['is_show'] = 1;
        if ($s >= 0 && $s < 4) {
            $where['status'] = $s;
        }
        $orderList = M("order")->where($where)->order('order_id desc')->select();

        foreach ($orderList as $key => $order) {
            $deid = $order["order_id"];
            $orderList[$key]["detail"] = M("order_detail")->where(array("order_id" => $deid))->select();
        }

        $this->assign("s", $s);
        $this->assign("orderList", $orderList);
        $this->display();
    }

    public function collection()
    {
        $uid = session('user_auth.uid');
        if (!$uid) $this->error("请登录后进行操作。", "/");
        $collectList = M("collect")->where(array('uid' => $uid))->select();
        foreach ($collectList as $key => $value) {
            $collectList[$key]["name"] = M("product_detail")->where(array("id" => $value['proid']))->getField("name");
            $collectList[$key]["price"] = M("product_detail")->where(array("id" => $value['proid']))->getField("price");
            $collectList[$key]["pic"] = M("product_detail")->where(array("id" => $value['proid']))->getField("pic");
        }
        $this->assign("collectList", $collectList);
        $this->display("Dianpu/shangjia");
    }

    public function myAward()
    {
        $uid = session('user_auth.uid');
        $store = M("user")->where(array("member_id" => $uid))->field("dl_money,tg_money,gl_money")->find();
        $this->assign("store", $store);
        $this->display();
    }

    public function collectionbj()
    {
        $this->display();
    }

    /**
     * 商城-我的
     */
    public function mine()
    {
        $uid = session('userid');
        $userInfo = M('user')->where(array('userid' => $uid))->field('userid,account,reg_date,is_e_verify,level')->find();

        $verifyInfo = M('verify_list')->where(['uid' => $uid])->field('status')->find();
        $verifyResult = '';
        $verifyUrl = 'JavaScript:void (0)';
        if (empty($verifyInfo) || $verifyInfo['status'] == Constants::VERIFY_STATUS_NOT_PASS) {
            $verifyUrl = U('Member/verify');
        }

        $verify_is_show = 1;
        if ($verifyInfo) {
            if ($verifyInfo['status'] == Constants::VERIFY_STATUS_PASS) {
                $verifyResult = '（已通过审核）';
                $verify_is_show = 0;
            } elseif ($verifyInfo['status'] == Constants::VERIFY_STATUS_NOT_PASS) {
                $verifyResult = '（审核失败,请重新修改提交）';
            } else {
                $verifyResult = '（审核中,请耐心等待）';
            }
        }

        if($userInfo['level'] < Constants::USER_LEVEL_A_THREE){
            $verify_is_show = 0;
        }

        $verifyInfo['url'] = $verifyUrl;
        $verifyInfo['result'] = $verifyResult;

        $this->assign('userInfo', $userInfo);
        $this->assign("verifyInfo", $verifyInfo);
        $this->assign("verify_is_show", $verify_is_show);
        $this->assign('methods', 'shopMine');
        $this->assign('wait_send_num', OrderModel::getOrderNumByWhere(['status' => 1]));
        $this->assign('wait_take_num', OrderModel::getOrderNumByWhere(['status' => 2]));
        $this->display();
    }

    /**
     * 物流详情
     */
    public function stics()
    {
        $orderID = I('order_id');
        $orderInfo = M('order')->where(array('order_id' => $orderID))->select();
        $this->assign('detls', $orderInfo);
        $this->display();
    }

    //商品评价
    public function Docontent()
    {
        $content = I('content');
        $id = I('id');
        if ($content != '') {
            $res = M('product_detail')->where(array('id' => $id))->setField('producs_pingjia', $content);
            if ($res) {
                $mes = array('status' => 1, 'info' => '评价成功');
                $this->ajaxReturn($mes);
            } else {
                $mes = array('status' => 2, 'info' => '评价失败');
                $this->ajaxReturn($mes);
            }
        }
    }

    /**
     * 收货地址列表
     */
    public function addresslist()
    {
        $uid = session('userid');
        $addType = I('type');
        if (!$uid) $this->error("请登录后进行操作。", "/");
        $addressList = M("address")->where(array("member_id" => $uid))->select();
        $this->assign("addressList", $addressList);
        $this->assign("addtype", $addType);
        $this->display();
    }

    /**
     * 添加收货地址页面
     */
    public function address()
    {
        $type = I('type');
        $this->assign('type', $type);
        $this->display();
    }

    /**
     * 添加收货地址
     */
    public function Addaddress()
    {
        $uid = session('userid');
        $uanme = I('uname');
        $province = I('province');
        $phone = I('phone');
        $city = I('city');
        $district = I('district');
        $address = I('detailadd');
        $detailadd = I('detailadd');
        $ismoren = I('ismoren');
        $address_id = I('address_id');
        if ($uanme == '') {
            $mes = array('status' => 2, 'info' => '请填写用户名');
            $this->ajaxReturn($mes);
        }
        if ($province == '--请选择省份--') {
            $mes = array('status' => 2, 'info' => '请选择省份');
            $this->ajaxReturn($mes);
        }

        if ($city == '') {
            $mes = array('status' => 2, 'info' => '请选择市辖区');
            $this->ajaxReturn($mes);
        }

        if ($district == '市辖区' || $district == '') {
            $mes = array('status' => 2, 'info' => '请选择市辖区');
            $this->ajaxReturn($mes);
        }

        if ($address == '') {
            $mes = array('status' => 2, 'info' => '请选择具体位置');
            $this->ajaxReturn($mes);
        }

        if ($ismoren == 1 && $address_id == '') {
            $Info = '新增默认地址成功';
            $data['zt_'] = $ismoren;
            $addid = M('address')->where(array('member_id' => $uid, 'zt_' => 1))->getField('address_id');
            M('address')->where(array('address_id' => $addid))->setField('zt_', 0);
        }

        if ($ismoren == 1 && $address_id != '') {
            $Info = '修改默认地址成功';
            $data['zt_'] = $ismoren;
            $addid = M('address')->where(array('member_id' => $uid, 'zt_' => 1))->getField('address_id');
            M('address')->where(array('address_id' => $addid))->setField('zt_', 0);
        }

        if ($ismoren == 0 && $address_id != '') {
            $Info = '修改地址成功';
            $data['zt_'] = $ismoren;
            $addid = M('address')->where(array('member_id' => $uid, 'zt_' => 0))->getField('address_id');
            M('address')->where(array('address_id' => $addid))->setField('zt_', 0);
        }

        $data['member_id'] = $uid;
        $data['name'] = $uanme;
        $data['telephone'] = $phone;
        $data['province_id'] = $province;
        $data['city_id'] = $city;
        $data['country_id'] = $district;
        $data['address'] = $address;
        if ($address_id == '') {
            $res = M('address')->add($data);
        } else {
            $res = M('address')->where(array('address_id' => $address_id))->save($data);
        }
        if ($res) {
            $mes = array('status' => 1, 'info' => $Info, 'webhref' => 'member/addresslist');
            $this->ajaxReturn($mes);
        } else {
            $mes = array('status' => 2, 'info' => '新增收货地址失败');
            $this->ajaxReturn($mes);
        }
    }

    /**
     * 修改收货地址
     */
    public function Dditadd()
    {
        $id = I('id');
        $type = I('type');
        $info = M('address')->where(array('address_id' => $id))->find();
        $this->assign('info', $info);
        $this->assign('type', $type);
        $this->display('address');
    }

    /**
     * 删除收货地址
     */
    public function deleadd()
    {
        $id = I('id');
        $res = M('address')->where(array('address_id' => $id))->delete();
        if ($res) {
            $this->redirect('member/addresslist');
        }
    }

    /**
     * 确认收货
     */
    public function Confirmad()
    {
        $order_id = I('orderid');
        $orders = M('order');
        $res = $orders->where(array('order_id' => $order_id))->save(['status' => 3]);
        if (!$res) {
            $this->ajaxReturn(array('status' => 2, 'info' => '确认收货失败'));
        } else {
            $this->ajaxReturn(array('status' => 1, 'info' => '确认收货成功'));
        }
    }

    //退出登录
    public function loginout()
    {
        session_unset();
        session_destroy();
        $this->redirect('Home/login/login');
    }

    /**
     * 商家订单
     */
    public function Mineorders()
    {
        $uid = user_login();
        $where['order_sellerid'] = $uid;
        $where['status'] = ['gt', 0];
        $orderList = M("order")->where($where)->order('status asc,pay_time asc')->select();
        foreach ($orderList as $key => $value) {
            $orderList[$key]["detail"] = M("order_detail")->where(array("order_id" => $value['order_id']))->select();
        }
        $this->assign('orderList', $orderList);
        $this->display();
    }

    public function havemoney()
    {
        $id = trim(I('id'));
        $uid = session('user_auth.uid');
        //该订单是否为该商家的
        $orderInfo = M("order")->where(array("order_id" => $id, "order_sellerid" => $uid))->find();
        if (empty($orderInfo)) {
            $mes = array('status' => 0, 'info' => '该订单不存在');
            $this->ajaxReturn($mes);
        }
        $isPay = M("order")->where(array("order_id" => $id, "order_sellerid" => $uid))->setField("status", 1);
        if ($isPay) {
            $mes = array('status' => 1, 'info' => '确认成功');
            $this->ajaxReturn($mes);
        } else {
            $mes = array('status' => 0, 'info' => '确认失败');
            $this->ajaxReturn($mes);
        }
    }

    /**
     * 商家发货
     */
    public function deliver()
    {
        $id = I('id');
        $uid = user_login();
        $express_order = I('express_order');
        $express_name = I('express_name');
        if ($express_order == '') {
            $mes = array('status' => 2, 'info' => '请输入快递单号');
            $this->ajaxReturn($mes);
        }

        if ($express_name == '') {
            $mes = array('status' => 2, 'info' => '请输入快递公司名称');
            $this->ajaxReturn($mes);
        }

        $res = M('order')->where(array('order_id' => $id, "order_sellerid" => $uid))->find();
        if (empty($res)) {
            $mes = array('status' => 2, 'info' => '订单不存在');
            $this->ajaxReturn($mes);
        }

        if ($res['status'] != 1) {
            $mes = array('status' => 2, 'info' => '该订单不是可发货状态');
            $this->ajaxReturn($mes);
        }

        if ($res) {
            $res_deliver = M('order')->where(array('order_id' => $id))->setfield(array('kd_name' => $express_name, 'kd_no' => $express_order, 'status' => 2));
            if ($res_deliver) {
                $mes = array('status' => 1, 'info' => '发货成功');
                $this->ajaxReturn($mes);
            } else {
                $mes = array('status' => 2, 'info' => '发货失败');
                $this->ajaxReturn($mes);
            }
        }
    }

    /**
     * 认证中心
     * @throws \Think\Exception
     */
    public function verify()
    {
        if (IS_POST) {
            $model = new VerifyListModel();
            $res = $model->addOrUpdate();
            if (!$res) {
                error_alert($model->getError());
            }
            success_alert('提交申请成功，等待后台审核。', U('member/mine'));
        }

        $user_id = session('userid');
        $user_info = M('user')->where(['userid' => $user_id])->field('username,mobile,identity_card')->find();
        $verifyInfo = M('verify_list')->where(['uid' => $user_id])->find();
        $this->assign('verifyInfo', $verifyInfo);
        $this->assign('user_info', $user_info);
        $this->display();
    }

    /**
     * 认证协议
     * @author ldz
     * @time 2020/3/4 15:23
     */
    public function verifyAgreement()
    {
        $content = M('Config')->where(['name' => 'verify_agreement'])->getField('value');
        $this->assign('content', $content);
        $this->display();
    }

    public function level()
    {
        $uid = session('userid');
        $userInfo = M("user")->where(array("member_id" => $uid))->find();
        $this->assign("userInfo", $userInfo);
        $this->display();
    }

    public function level_up()
    {
        $uid = session('user_auth.uid');
        $level = trim(I("level"));

        $imginfo = $_FILES;
        $img = moreimg_uploads();
        if ($img['status'] == '0') {
            error_alert($img['res']);
        }

        $i = 0;
        foreach ($imginfo as $kname => $v) {
            if (!empty($v['name'])) {
                $data[$kname] = $img['res'][$i];
                $i++;
            }
        }

        if (empty($level) || !isset($level)) {
            error_alert("参数错误");
        }

        if (empty($data['proof']) || !isset($data['proof'])) {
            error_alert("付款凭证不为空");
        }

        if ($level == 1) {

        } else if ($level == 2) {

        } else if ($level == 3) {
        } else {
            error_alert("非法请求");
        }

        //是否为当前等级
        $userInfo = M("user")->where(array("member_id" => $uid))->find();
        if ($userInfo['member_grade'] == $level) {
            error_alert("您已经是该等级了,无需申请。");
        }
        $count = M("level_list")->where(array("uid" => $uid, "status" => 0))->count();
        if ($count > 0) {
            error_alert("您当前已经提交过申请了");
        }
        $level_data = array();
        $level_data['uid'] = $uid;
        $level_data['level'] = $level;
        $level_data['proof'] = $data['proof'];
        $level_data['money'] = $level == 1 ? 99 : ($level == 2 ? 1999 : 6000);
        $level_data['status'] = 0;
        $level_data['time'] = time();
        $level_data['datestr'] = date("Ymd");
        $isAdd = M("level_list")->add($level_data);
        if ($isAdd) {
            success_alert("提交成功,等待系统审核。", U('Member/level'));
        } else {
            error_alert("提交失败。");
        }
    }

    /**
     * 可用余额提现
     */
    public function withdrawal()
    {
        if (IS_POST) {
            $model = new StoreModel();
            $res = $model->withdrawal();
            if (!$res) {
                $this->ajaxReturn(['msg' => $model->getError(), 'status' => 0]);
            }
            $this->ajaxReturn(['msg' => '提交成功，请耐心等待审核', 'status' => 1]);
        }

        //是否有设置默认银行卡
        $uid = session('userid');
        $cid = trim(I('cid'));
        $bankInfo = UserModel::getUserBankInfo($uid, $cid);

        $storeInfo = M('store')->where(['uid' => $uid])->field('cangku_num')->find();
        $withdrawal_ratio = M('config')->where(['name' => 'withdrawal_ratio'])->getField('value');

        $this->assign('bankInfo', $bankInfo);
        $this->assign('storeInfo', $storeInfo);
        $this->assign('withdrawal_ratio', $withdrawal_ratio);
        $this->display();
    }

    /**
     * 营业款提现
     */
    public function withTurnover()
    {
        if (IS_POST) {
            $model = new StoreModel();
            $res = $model->withTurnover();
            if (!$res) {
                $this->ajaxReturn(['msg' => $model->getError(), 'status' => 0]);
            }
            $this->ajaxReturn(['msg' => '提交成功，请耐心等待审核', 'status' => 1]);
        }

        //是否有设置默认银行卡
        $uid = session('userid');
        $cid = trim(I('cid'));
        $bankInfo = UserModel::getUserBankInfo($uid, $cid);

        $storeInfo = M('store')->where(['uid' => $uid])->field('turnover')->find();
        $this->assign('bankInfo', $bankInfo);
        $this->assign('storeInfo', $storeInfo);
        $this->display();
    }

    /**
     * 提现记录
     */
    public function withdrawalRecord()
    {
        $uid = session('userid');
        $where['type'] = intval(I('type', Constants::WITHDRAWAL_TYPE_ECOLOGY));
        $where['uid'] = $uid;
        $where['delete_time'] = '';
        //分页
        $page = I('p', 1);
        $limit = 10;
        $Chan_info = M('withdrawal_record')->where($where)->limit(($page - 1) * $limit, $limit)
            ->field('amount,poundage,status,create_time')->order('id desc')->select();

        foreach ($Chan_info as $k => $v) {
            $Chan_info[$k]['create_time'] = toDate(strtotime($v['create_time']));
            switch ($v['status']) {
                case '0':
                    $Chan_info[$k]['type_name'] = '未发放';
                    break;
                case '1':
                    $Chan_info[$k]['type_name'] = '已发放';
                    break;
            }
        }

        if (IS_AJAX) {
            if (count($Chan_info) >= 1) {
                ajaxReturn($Chan_info, 1);
            } else {
                ajaxReturn('暂无记录', 0);
            }
        }
        $this->assign('Chan_info', $Chan_info);
        if ($where['type'] == Constants::WITHDRAWAL_TYPE_ECOLOGY) {
            $this->display();
        } else {
            $this->display('withTurnoverRecord');
        }
    }

    /**
     * 转账
     */
    public function transfer()
    {
        if (IS_POST) {
            $model = new StoreModel();
            $res = $model->transfer();
            if (!$res) {
                $this->ajaxReturn(['msg' => $model->getError(), 'status' => 0]);
            }
            $this->ajaxReturn(['msg' => '转账成功', 'status' => 1]);
        }
        $user_id = session('userid');
        $userInfo = M('store')->where(['uid' => $user_id])->field('cangku_num,fengmi_num')->find();
        $fee_ratio = M('config')->where(['name' => 'transfer_accounts_fee_ratio'])->getField('value');
        $this->assign('fee_ratio', $fee_ratio);
        $this->assign('userInfo', $userInfo);
        $this->display();
    }

    /**
     * 兑换(可用余额兑换消费通证)
     */
    public function exchange()
    {
        if (IS_POST) {
            $model = new StoreModel();
            $res = $model->exchange();
            if (!$res) {
                $this->ajaxReturn(['msg' => $model->getError(), 'status' => 0]);
            }
            $this->ajaxReturn(['msg' => '兑换成功', 'status' => 1]);
        }

        $user_id = session('userid');
        $userInfo = M('store')->where(['uid' => $user_id])->field('cangku_num,fengmi_num')->find();
        $fee_ratio = M('config')->where(['name' => 'ecology_to_bao_dan_fee_ratio'])->getField('value');

        $this->assign('userInfo', $userInfo);
        $this->assign('fee_ratio', $fee_ratio);
        $this->display();
    }

    /**
     * 兑换(生态通证兑换可用余额)
     */
    public function exchangeTwo()
    {
        if (IS_POST) {
            $model = new StoreModel();
            $res = $model->exchangeTwo();
            if (!$res) {
                $this->ajaxReturn(['msg' => $model->getError(), 'status' => 0]);
            }
            $this->ajaxReturn(['msg' => '兑换成功', 'status' => 1]);
        }

        $user_id = session('userid');
        $userInfo = M('store')->where(['uid' => $user_id])->field('cangku_num,exchange_amount')->find();
        $userInfo['pass_card_amount'] = UcoinsModel::getAmount($user_id); //生态通证数量
        $coin_price = UcoinsModel::getCoinPrice();

        $this->assign('userInfo', $userInfo);
        $this->assign('coin_price', $coin_price);
        $this->display();
    }

    /**
     * 充值
     */
    public function recharge()
    {
        if (IS_POST) {
            $model = new StoreModel();
            $res = $model->recharge();
            if (!$res) {
                $this->ajaxReturn(['msg' => $model->getError(), 'status' => 0]);
            }
            $this->ajaxReturn(['msg' => '充值成功', 'status' => 1]);
        }
        $collections_img = M('config')->where(['name' => 'collections_img'])->getField('value');
        $this->assign('collections_img', $collections_img);
        $this->display();
    }

    /**
     * 充值记录
     */
    public function rechargeRecord()
    {
        $uid = session('userid');
        $where['uid'] = $uid;
        $where['delete_time'] = '';
        //分页
        $page = I('p', 1);
        $limit = 10;
        $Chan_info = M('recharge')->where($where)->limit(($page - 1) * $limit, $limit)
            ->field('amount,status,create_time')->order('id desc')->select();

        foreach ($Chan_info as $k => $v) {
            $Chan_info[$k]['create_time'] = toDate(strtotime($v['create_time']));
            switch ($v['status']) {
                case '0':
                    $Chan_info[$k]['type_name'] = '待审核';
                    break;
                case '1':
                    $Chan_info[$k]['type_name'] = '审核通过';
                    break;
                case '2':
                    $Chan_info[$k]['type_name'] = '审核未通过';
                    break;
            }
        }

        if (IS_AJAX) {
            if (count($Chan_info) >= 1) {
                ajaxReturn($Chan_info, 1);
            } else {
                ajaxReturn('暂无记录', 0);
            }
        }
        $this->assign('Chan_info', $Chan_info);
        $this->display();
    }


}



