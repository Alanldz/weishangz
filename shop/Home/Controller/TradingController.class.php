<?php

namespace Home\Controller;

class TradingController extends CommonController
{

    /**
     * 上传收款码
     */
    public function uplodeimg()
    {
        $data = img_uploading();
        echo json_encode($data);
    }

    /**
     * 卖出
     * @time 2018-7-26 21:22:07
     */
    public function SellCentr()
    {
        //是否有设置默认银行卡
        $uid = session('userid');
        $moneyinfo = M('store')->where(array('uid' => $uid))->field('cangku_num,fengmi_num')->find();
        $morecars = M('ubanks as u')->join('RIGHT JOIN ysk_bank_name as banks ON u.card_id = banks.pid')
            ->where(array('u.user_id' => $uid, 'delete_time' => ''))->order('u.id desc')
            ->field('u.hold_name,u.id,u.card_number,u.user_id,banks.banq_genre,banks.banq_img')->select();

        //生成出售订单
        if (IS_AJAX) {
            $pwd = trim(I('pwd'));
            $sellnums = trim(I('sellnums'));            //出售数量
            $cardid = trim(I('cardid'));//银行卡id

            if (!preg_match("/^[1-9][0-9]*$/", $sellnums) || ($sellnums % 100 !== 0) || $sellnums == 0) {
                ajaxReturn('挂卖金额必须是100的整数倍', 0);
            }

            $minepwd = M('user')->where(array('userid' => $uid))->Field('account,service_center,empty_order')->find();
            if ($minepwd['empty_order']) {
                ajaxReturn('您暂时不能卖出，有疑问请留言！', 0);
            }
            if ($minepwd['service_center']) {
                ajaxReturn('服务中心EP不能挂售EP', 0);
            }

            //用户需要支出的总数量
            $allMoney = $sellnums;

            //自己是否有足够余额
            $is_enough = M('store')->where(array('uid' => $uid))->getField('cangku_num');
            if ($allMoney > $is_enough) {
                ajaxReturn('您当前账户暂无这么多余额~', 0);
            }
            //验证银行卡是否是自己
            $ubanks_info = M('ubanks')->where(array('id' => $cardid))->field('user_id,hold_name')->find();
            if ($ubanks_info['user_id'] != $uid) {
                ajaxReturn('对不起,该张银行卡不是您的哦~', 0);
            }
            if ($ubanks_info['hold_name'] == '') {
                ajaxReturn('对不起,银行卡姓名不能为空~', 0);
            }

            //验证今天是否已卖出过
            $startime = date('Y-m-d');
            $endtime = date("Y-m-d", strtotime("+1 day"));
            $todaystime = strtotime($startime);
            $endtime = strtotime($endtime);
            $today_where['payout_id'] = $uid;
            $today_where['trans_type'] = 0;
            $today_where['pay_time'] = array(array('egt', $todaystime), array('lt', $endtime), 'and');
            $today_order = M('trans')->where($today_where)->count();
            if ($today_order > 0) {
                ajaxReturn('您尚有一笔交易未完成，请勿重复操作~', 0);
            }

            //判断过去卖出过的且未确认收款的订单
            $formerly_where['payout_id'] = $uid;
            $formerly_where['pay_time'] = array('lt', $startime);
            $formerly_where['pay_state'] = array('neq', 3);
            $formerly_where['trans_type'] = 0;
            $formerly_order = M('trans')->where($formerly_where)->count();
            if ($formerly_order > 0) {
                ajaxReturn('对不起,您还有订单未完成~', 0);
            }

            //验证交易密码
            $user_object = D('Home/User');
            $user_object->Trans($minepwd['account'], $pwd);

            //开启事物
            M()->startTrans();

            //生成订单
            $data['pay_no'] = build_order_no();
            $data['payout_id'] = $uid;
            $data['card_id'] = $cardid;
            $data['pay_nums'] = $sellnums;
            $data['fee_nums'] = 0;
            $data['pay_time'] = time();
            $res_Add = M('trans')->add($data);

            if (!$res_Add) {
                M()->rollback();
                ajaxReturn('生成订单失败', 0);
            }

            //给自己减少这么多余额
            $doDec = M('store')->where(array('uid' => $uid))->setDec('cangku_num', $allMoney);
            if (!$doDec) {
                M()->rollback();
                ajaxReturn('扣除用户EP值失败', 0);
            }

            $pay_n = M('store')->where(array('uid' => $uid))->getfield('cangku_num');
            $jifen_dochange['now_nums'] = $pay_n;
            $jifen_dochange['now_nums_get'] = $pay_n;
            $jifen_dochange['is_release'] = 1;
            $jifen_dochange['pay_id'] = $uid;
            $jifen_dochange['get_id'] = 0;
            $jifen_dochange['get_nums'] = $allMoney;
            $jifen_dochange['get_time'] = time();
            $jifen_dochange['get_type'] = 9;
            $res_addres = M('tranmoney')->add($jifen_dochange);

            if (!$res_addres) {
                M()->rollback();
                ajaxReturn('添加记录失败', 0);
            }

            $config = D('config')->where("name='sellmobile'")->getField("value");
            $arrMobile = explode(';', $config);
            foreach ($arrMobile as $mobile) {
                sellSendMsg($mobile, '133013');
            }

            M()->commit();
            ajaxReturn('订单创建成功', 1);
        }

        //是否有卖出的权限
        $userInfo = M('user')->where(['userid' => $uid])->field('quanxian')->find();
        $quanxian = explode("-", $userInfo['quanxian']);

        //挂卖列表
        $traInfo = M('trans');
        $where['payout_id'] = $uid;
        //分页
        $p = Fgetpage($traInfo, $where, 5);
        $page = $p->show();
        $orders = $traInfo->where($where)->order('id desc')->select();
        $banks = M('ubanks');
        foreach ($orders as $k => $v) {
            if ($v['payin_id'] != '') {
                //银行卡号.开户支行.开户银行
                $bankinfos = $banks->where(array('id' => $v['card_id'], 'delete_time' => ''))->field('hold_name,card_number,card_id,open_card')->find();
                $uinfomsg = M('user')->where(array('userid' => $v['payin_id']))->Field('username,mobile')->find();
                $orders[$k]['cardnum'] = $bankinfos['card_number'];
                $orders[$k]['bname'] = M('bank_name')->where(array('q_id' => $bankinfos['card_id']))->getfield('banq_genre');
                $orders[$k]['openrds'] = $bankinfos['open_card'];
                $orders[$k]['uname'] = $uinfomsg['username'];
                $orders[$k]['umobile'] = $uinfomsg['mobile'];
            }
        }

        $this->assign('orders', $orders);
        $this->assign('page', $page);
        $this->assign('quanxian', $quanxian);
        $this->assign('morecars', $morecars);
        $this->assign('moneyinfo', $moneyinfo);
        $this->display();
    }

    //未完成订单
    public function Nofinsh()
    {
        $state = trim(I('state'));
        $uid = session('userid');
        $traInfo = M('trans');
        if ($state > 0) {
            $where['pay_state'] = array('between', '1,2');
        } else {
            $where['pay_state'] = 0;
        }
        $where['payout_id'] = $uid;

        //分页
        $p = getpage($traInfo, $where, 20);
        $page = $p->show();
        $orders = $traInfo->where($where)->order('id desc')->select();
        $banks = M('ubanks');
        foreach ($orders as $k => $v) {
            if ($v['payin_id'] != '') {
                //银行卡号.开户支行.开户银行
                $bankinfos = $banks->where(array('id' => $v['card_id']))->field('hold_name,card_number,card_id,open_card')->find();
                $uinfomsg = M('user')->where(array('userid' => $v['payin_id']))->Field('username,mobile')->find();
                $orders[$k]['cardnum'] = $bankinfos['card_number'];
                $orders[$k]['bname'] = M('bank_name')->where(array('q_id' => $bankinfos['card_id']))->getfield('banq_genre');
                $orders[$k]['openrds'] = $bankinfos['open_card'];
                $orders[$k]['uname'] = $uinfomsg['username'];
                $orders[$k]['umobile'] = $uinfomsg['mobile'];

            }
        }
        $this->assign('state', $state);
        $this->assign('orders', $orders);
        $this->assign('page', $page);
        $this->display();
    }

    //上传付款凭证
    public function Conpayd()
    {
        //查询我买入的
        $uid = session('userid');
        $traInfo = M('trans');
        $banks = M('ubanks');
        $where['payout_id'] = $uid;
        $where['pay_state'] = 2;
        //分页
        $p = getpage($traInfo, $where, 20);
        $page = $p->show();
        $orders = $traInfo->where($where)->order('id desc')->select();
        //收款人
        foreach ($orders as $k => $v) {
            //银行卡号.开户支行.开户银行
            $bankinfos = $banks->where(array('id' => $v['card_id']))->field('hold_name,card_number,card_id,open_card')->find();
            $uinfomsg = M('user')->where(array('userid' => $v['payin_id']))->Field('username,mobile')->find();
            $orders[$k]['cardnum'] = $bankinfos['card_number'];
            $orders[$k]['bname'] = M('bank_name')->where(array('q_id' => $bankinfos['card_id']))->getfield('banq_genre');
            $orders[$k]['openrds'] = $bankinfos['open_card'];
            $orders[$k]['uname'] = $uinfomsg['username'];
            $orders[$k]['umobile'] = $uinfomsg['mobile'];
        }
        if (IS_AJAX) {
            $uid = session('userid');
            $picname = $_FILES['uploadfile']['name'];
            $picsize = $_FILES['uploadfile']['size'];
            $trid = trim(I('trid'));
            if ($trid <= 0) {
                ajaxReturn('提交失败,请重新提交', 0);
            }
            if ($picname != "") {
                if ($picsize > 2014000) { //限制上传大小
                    ajaxReturn('图片大小不能超过2M', 0);
                }
                $type = strstr($picname, '.'); //限制上传格式
                if ($type != ".gif" && $type != ".jpg" && $type != ".png" && $type != ".jpeg") {
                    ajaxReturn('图片格式不对', 0);
                }
                $rand = rand(100, 999);
                $pics = uniqid() . $type; //命名图片名称
                //上传路径
                $pic_path = "./Uploads/Payvos/" . $pics;
                move_uploaded_file($_FILES['uploadfile']['tmp_name'], $pic_path);
            }
            $size = round($picsize / 1024, 2); //转换成kb
            $pic_path = trim($pic_path, '.');
            if ($size) {
                $res = M('trans')->where(array('id' => $trid))->setField(array('trans_img' => $pic_path, 'pay_state' => 2));
                if ($res) {
                    ajaxReturn('打款提交成功', 1, '/Growth/Conpay');
                } else {
                    ajaxReturn('打款提交失败', 0);
                }
            }
        }
        $this->assign('page', $page);
        $this->assign('orders', $orders);
        $this->display();
    }

    //取消订单
    public function quxiao_order()
    {
        $id = (int)I('id', 'intval', 0);
        $uid = session('userid');
        $mydeal = M('trans')->where(array("id" => $id, "payin_id|payout_id" => $uid, "pay_state" => array("lt", 2)))->find();
        if (!$mydeal) ajaxReturn('订单不存在~', 0);
        //开启事物
        M()->startTrans();
        $type = $mydeal["trans_type"];
        $copy_order = M('trans_quxiao')->add($mydeal);//把记录复制到另一个表
        if (!$copy_order) {
            M()->rollback();
            ajaxReturn('复制订单失败~', 0);
        }
        if ($type == 0) {//卖出单，删除订单
            $res1 = M('trans')->delete($id);
            if (!$res1) {
                M()->rollback();
                ajaxReturn('复制订单失败~', 0);
            }

            $sellnums = $mydeal["pay_nums"];
            $doDec = M('store')->where(array('uid' => $uid))->setInc('cangku_num', $sellnums);
            if (!$doDec) {
                M()->rollback();
                ajaxReturn('修改EP值失败~', 0);
            }
            //增加自己的余额记录
            $pay_n = M('store')->where(array('uid' => $uid))->getfield('cangku_num');
            $jifen_dochange['now_nums'] = $pay_n;
            $jifen_dochange['now_nums_get'] = $pay_n;
            $jifen_dochange['is_release'] = 1;
            $jifen_dochange['pay_id'] = 0;
            $jifen_dochange['get_id'] = $uid;
            $jifen_dochange['get_nums'] = $sellnums;
            $jifen_dochange['get_time'] = time();
            $jifen_dochange['get_type'] = 10;
            $res_addres = M('tranmoney')->add($jifen_dochange);
            if (!$res_addres) {
                M()->rollback();
                ajaxReturn('添加EP记录失败~', 0);
            }
        } elseif ($type == 1) {//为购买单，自己是卖出方，清空payout_id和改变pay_state为0并返回全部余额
            $sellnums = $mydeal["pay_nums"];
            $doDec = M('store')->where(array('uid' => $uid))->setInc('cangku_num', $sellnums);
            if (!$doDec) {
                M()->rollback();
                ajaxReturn('修改EP值失败~', 0);
            }
            //增加自己的余额记录
            $pay_n = M('store')->where(array('uid' => $uid))->getfield('cangku_num');
            $jifen_dochange['now_nums'] = $pay_n;
            $jifen_dochange['now_nums_get'] = $pay_n;
            $jifen_dochange['is_release'] = 1;
            $jifen_dochange['pay_id'] = 0;
            $jifen_dochange['get_id'] = $uid;
            $jifen_dochange['get_nums'] = $sellnums;
            $jifen_dochange['get_time'] = time();
            $jifen_dochange['get_type'] = 10;
            $res_addres = M('tranmoney')->add($jifen_dochange);
            if (!$res_addres) {
                M()->rollback();
                ajaxReturn('添加EP记录失败~', 0);
            }

            $payout['payout_id'] = 0;
            $payout['pay_state'] = 0;
            $res1 = M('trans')->where(array('id' => $id))->save($payout);
            if (!$res1) {
                M()->rollback();
                ajaxReturn('取消订单失败~', 0);
            }
        }
        M()->commit();
        ajaxReturn('取消成功', 1);
    }

    //已完成订单
    public function Dofinsh()
    {
        //查询我买入的
        $uid = session('userid');
        $traInfo = M('trans');
        $banks = M('ubanks');
        $where['payout_id'] = $uid;
        $where['pay_state'] = 3;
        //分页
        $p = getpage($traInfo, $where, 20);
        $page = $p->show();
        $orders = $traInfo->where($where)->order('id desc')->select();
        //收款人
        foreach ($orders as $k => $v) {
            //银行卡号.开户支行.开户银行
            $bankinfos = $banks->where(array('id' => $v['card_id']))->field('hold_name,card_number,card_id,open_card')->find();
            $uinfomsg = M('user')->where(array('userid' => $v['payin_id']))->Field('username,mobile')->find();
            $orders[$k]['cardnum'] = $bankinfos['card_number'];
            $orders[$k]['bname'] = M('bank_name')->where(array('q_id' => $bankinfos['card_id']))->getfield('banq_genre');
            $orders[$k]['openrds'] = $bankinfos['open_card'];
            $orders[$k]['uname'] = $uinfomsg['username'];
            $orders[$k]['umobile'] = $uinfomsg['mobile'];
        }
        $this->assign('page', $page);
        $this->assign('orders', $orders);
        $this->display();
    }

    public function Buyrecords()
    {
        $traInfo = M('trans');
        $uid = session('userid');
        $where['payin_id'] = $uid;
        //分页
        $p = getpage($traInfo, $where, 20);
        $page = $p->show();
        $Chan_info = $traInfo->where($where)->order('id desc')->select();
        foreach ($Chan_info as $k => $v) {
            $Chan_info[$k]['username'] = M('user')->where(array('userid' => $v['payout_id']))->getField('username');
            $Chan_info[$k]['get_timeymd'] = date('Y-m-d', $v['pay_time']);
            $Chan_info[$k]['get_timedate'] = date('H:i:s', $v['pay_time']);
        }
        if (IS_AJAX) {
            if (count($Chan_info) >= 1) {
                ajaxReturn($Chan_info, 1);
            } else {
                ajaxReturn('暂无记录', 0);
            }
        }
        $this->assign('page', $page);
        $this->assign('Chan_info', $Chan_info);
        $this->assign('uid', $uid);
        $this->display();
    }


    //卖出记录
    public function Sellerrecords()
    {
        $traInfo = M('trans');
        $uid = session('userid');
        $where['payout_id'] = $uid;
        //分页
        $p = getpage($traInfo, $where, 20);
        $page = $p->show();
        $Chan_info = $traInfo->where($where)->order('id desc')->select();
        foreach ($Chan_info as $k => $v) {
            $Chan_info[$k]['username'] = M('user')->where(array('userid' => $v['payin_id']))->getField('username');
            $Chan_info[$k]['get_timeymd'] = date('Y-m-d', $v['pay_time']);
            $Chan_info[$k]['get_timedate'] = date('H:i:s', $v['pay_time']);
        }
        if (IS_AJAX) {
            if (count($Chan_info) >= 1) {
                ajaxReturn($Chan_info, 1);
            } else {
                ajaxReturn('暂无记录', 0);
            }
        }
        $this->assign('page', $page);
        $this->assign('Chan_info', $Chan_info);
        $this->assign('uid', $uid);
        $this->display();
    }

    /**
     * 确认收到款
     */
    public function Con_Getmoney()
    {
        $trid = I('trid', 'intval', 0);
        $traninfo = M('trans');
        if ($trid < 1) {
            ajaxReturn('请选择证正确的订单', 0);
        }
        $order_info = $traninfo->where(array('id' => $trid))->find();
        if ($order_info['pay_state'] != 2) {
            ajaxReturn('该订单状态不正确', 0);
        }
        M()->startTrans();
        //给买家加EP值
        $paynums = $order_info['pay_nums'];
        $datapay['cangku_num'] = array('exp', 'cangku_num + ' . $paynums);
        $res_pay = M('store')->where(array('uid' => $order_info['payin_id']))->save($datapay);
        if (!$res_pay) {
            M()->rollback();
            ajaxReturn('增加EP值失败，请留言', 0);
        }

        //修改订单状态
        $tramsg['pay_state'] = 3;
        $tramsg['get_moneytime'] = time();
        $res_suc = $traninfo->where(array('id' => $trid))->save($tramsg);
        if (!$res_suc) {
            M()->rollback();
            ajaxReturn('订单状态修改失败', 0);
        }
        //添加买入余额记录
        $pay_n = M('store')->where(array('uid' => $order_info["payin_id"]))->getfield('cangku_num');
        $jifen_dochange['now_nums'] = $pay_n;
        $jifen_dochange['now_nums_get'] = $pay_n;
        $jifen_dochange['is_release'] = 1;
        $jifen_dochange['pay_id'] = 0;
        $jifen_dochange['get_id'] = $order_info["payin_id"];
        $jifen_dochange['get_nums'] = $paynums;
        $jifen_dochange['get_time'] = time();
        $jifen_dochange['get_type'] = 8;
        $res_addres = M('tranmoney')->add($jifen_dochange);
        if (!$res_addres) {
            M()->rollback();
            ajaxReturn('添加EP流水失败', 0);
        }
        M()->commit();
        ajaxReturn('确认收款成功', 1);
    }

    //卖出中心
    public function Selldets()
    {
        if (IS_AJAX) {
            $pricenum = I('mvalue');
            if ($pricenum == '') {
                ajaxReturn('请选择正确的订单价格', 0);
            }
            $order_info = M('trans as tr')->join('LEFT JOIN  ysk_user as us on tr.payin_id = us.userid')->where(array('tr.pay_state' => 0, 'tr.trans_type' => 1, 'tr.pay_nums' => $pricenum))->order('id desc')->select();

            foreach ($order_info as $k => $v) {
                $order_info[$k]['cardinfo'] = M('bank_name')->where(array('q_id' => $v['card_id']))->getfield('banq_genre');
                $order_info[$k]['spay'] = $v['pay_nums'] * 0.85;
            }
            if (count($order_info) <= 0) {
                ajaxReturn('没找到相关记录', 0);
            } else {
                ajaxReturn($order_info, 1);
            }
        }
        $this->display();
    }

    //执行卖出
    public function Dosells()
    {
        if (IS_AJAX) {
            $uid = session('userid');
            $trid = I('trid', 1, 'intval');
            $pwd = trim(I('pwd'));
            $sellnums = M('trans')->where(array('id' => $trid))->field('pay_nums,payin_id,pay_state')->find();

            $sellAll = array(500, 1000, 3000, 5000, 10000, 30000);
            if (!in_array($sellnums['pay_nums'], $sellAll)) {
                ajaxReturn('您选择购买的金额不正确', 0);
            }
            if ($sellnums['payin_id'] == $uid) {
                ajaxReturn('您不能自己购买哦~', 0);
            }
            if ($sellnums['pay_state'] != 0) {
                ajaxReturn('该订单存在异常,暂时无法购买哦~', 0);
            }
            //验证交易密码
            $minepwd = M('user')->where(array('userid' => $uid))->Field('account,mobile,safety_pwd,safety_salt')->find();
            $user_object = D('Home/User');
            $user_info = $user_object->Trans($minepwd['account'], $pwd);
            //自己是否有足够余额
            $is_enough = M('store')->where(array('uid' => $uid))->getField('cangku_num');
            $shouldpay = $sellnums['pay_nums'] + 100;
            if ($shouldpay > $is_enough) {
                ajaxReturn('您当前账户暂无这么多余额~', 0);
            }
            //是否绑定银行卡
            $id_setcards = M('ubanks')->where(array('user_id' => $uid, 'is_default' => 1))->count('1');
            if ($id_setcards < 1) {
                $id_setcards = M('ubanks')->where(array('user_id' => $uid))->limit(1)->find();
            }
            if (empty($id_setcards)) {
                ajaxReturn('对不起,您还没用绑定银行卡哦~', 0);
            }
            $doDec = M('store')->where(array('uid' => $uid))->setDec('cangku_num', $shouldpay);

            $pay_n = M('store')->where(array('uid' => $uid))->getfield('cangku_num');
            $jifen_dochange['now_nums'] = $pay_n;
            $jifen_dochange['now_nums_get'] = $pay_n;
            $jifen_dochange['is_release'] = 1;
            $jifen_dochange['pay_id'] = $uid;
            $jifen_dochange['get_id'] = 0;
            $jifen_dochange['get_nums'] = $shouldpay;
            $jifen_dochange['get_time'] = time();
            $jifen_dochange['get_type'] = 9;
            $res_addres = M('tranmoney')->add($jifen_dochange);

            //记录买入会员
            $res_Buy = M('trans')->where(array('id' => $trid))->setField(array('payout_id' => $uid, 'pay_state' => 1, 'card_id' => $id_setcards['id'], 'fee_nums' => 100));
            if ($res_Buy) {
                ajaxReturn('卖出成功', 1);
            }
        }
        $this->display();
    }

    public function freebuy()
    {
        if (!IS_AJAX) {
            return false;
        }
        $userid = session('userid');
        $table = D('TraingFree');
        $where['sell_id'] = array('neq', $userid);
        $where['status'] = 0;
        $p = I('p', '0', 'intval');
        $page = $p * 10;
        $info = $table->field('FROM_UNIXTIME(create_time,"%Y-%m-%d") tt,num sellnum,id,sell_account u_account,sell_username u_username,status zhuangtai')->where($where)->order('id desc')->limit($page, 10)->select();
        if (empty($info)) {
            $info = null;
        }
        $this->ajaxReturn($info);
    }

    //定向交易待收款
    public function directwait()
    {
        $table = D('Trading');
        $userid = session('userid');
        $where = '(sell_id = ' . $userid . ' AND status IN (0,1)) OR (buy_id =' . $userid . ' AND status IN (0,1))';

        $p = I('p', '0', 'intval');
        $page = $p * 10;
        $info = $table->field('id,num,sell_id s_id,sell_account s_account,sell_username s_username,buy_id b_id,buy_account b_account,buy_username b_username,FROM_UNIXTIME(create_time,"%Y-%m-%d") tt,status,img')->where($where)->order('id desc')->limit($page, 10)->select();
        if (empty($info)) {
            $info = null;
        }
        $this->ajaxReturn($info);
    }

    //挂卖记录
    public function DrawMoneyList()
    {
        $uid = session('userid');
        $where['payout_id'] = $uid;
        //挂卖列表
        $traInfo = M('trans');
        //分页
        $p = getpage($traInfo, $where, 20);
        $page = $p->show();
        $orders = $traInfo->where($where)->order('id desc')->select();

        $this->assign('orders', $orders);
        $this->assign('page', $page);
        $this->display();
    }

}