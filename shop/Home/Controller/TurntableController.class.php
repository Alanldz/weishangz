<?php

namespace Home\ Controller;

use Common\Model\UcoinsModel;
use Home\Model\DealModel;

class TurntableController extends CommonController
{
    public function index()
    {
        $uid = session('userid');
        //查询当前币对应价格
        $coindets = array();
        for ($i = 1; $i < 12; $i++) {
            $coindets[] = D('coindets')->where("cid=" . $i)->order('coin_addtime desc')->find();
        }

        //当前我的资产
        $moneyinfo = M('store')->where(array('uid' => $uid))->field('cangku_num,fengmi_num')->find();

        //我的钱包地址 没有则自动生成
        $waadd = M('user')->where(array('userid' => $uid))->getField('wallet_add');
        if (empty($waadd) || $waadd == '') {
            $waadd = build_wallet_add();
            M('user')->where(array('userid' => $uid))->setField('wallet_add', $waadd);
        }

        //资产
        $user_ucoins = M('ucoins')->where(array('c_uid' => $uid, 'cid' => 1))->getField('c_nums');
        $ABS_value = M('ucoins')->where(array('c_uid' => $uid, 'cid' => 9))->getField('c_nums');

        $this->assign('coindets', $coindets);
        $this->assign('moneyinfo', $moneyinfo);
        $this->assign('user_ucoins', $user_ucoins);
        $this->assign('waadd', $waadd);
        $this->assign('uid', $uid);
        $this->assign('ABS_value', $ABS_value);

        $this->display();
    }

    //转账的对象
    public function Checkuser()
    {
        $paynums = I('paynums', 'float', 0);
        $getu = trim(I('moneyadd'));
        $type = intval(I('type', 1));
        $uid = session('userid');
        //判断用户是否是空单
        $empty_order = M('user')->where(['userid' => $uid])->getField('empty_order');
        if ($empty_order) {
            ajaxReturn('对不起，您不能转出，请留言~', 0);
        }

        $mwenums = M('ucoins')->where(array('c_uid' => $uid, 'cid' => $type))->getField('c_nums');
        $arrType = UcoinsModel::$TYPE_TABLE;
        if ($paynums > $mwenums) {
            ajaxReturn('您当前暂无这么多' . $arrType[$type] . '币哦~', 0);
        }

        $where['userid|mobile|wallet_add'] = $getu;
        $uinfo = M('user')->where($where)->Field('userid,username')->find();
        if ($uinfo['userid'] == $uid) {
            ajaxReturn('您不能给自己转账哦~', 0);
        }

        if (empty($uinfo) || $uinfo == '') {
            ajaxReturn('您输入的转出地址有误哦~', 0);
        }
        $getmsg = array('uname' => $uinfo['username'], 'getuid' => $uinfo['userid']);
        ajaxReturn($getmsg, 1);
    }


    //BAT
    public function Wbaobei()
    {
        $uid = session('userid');
        $step = I('step');
        if ($step < 1) {
            $step = 1;
        }
        $times = strtotime(date("Y-m-d"));
        $timee = $times + 86400;
        //今日收益
        $lastsy = M('wbao_detail')->where(array('uid' => $uid, 'type' => array("in", "3,4"), 'create_time' => array("between", $times . "," . $timee)))->sum('num');

        if ($step == 1) {//转入记录
            $list = M('wbao_detail')->where("(type=3) and (num > 0) and uid=" . $uid)->order("create_time desc")->select();
//             $list=M('wbao_detail')->where("(type=3 or type=4) and uid=".$uid)->order("create_time desc")->select(); //转入记录
        } elseif ($step == 2) {//转入记录
            $list = M('wbao_detail')->where("type=1 and uid=" . $uid)->order("create_time desc")->select();
        } elseif ($step == 3) { //转入记录
            $list = M('wbao_detail')->where("type=2 and uid=" . $uid)->order("create_time desc")->select();
        }

        $wbd = M('store')->where(array('uid' => $uid))->getField('huafei_total');
        $wbc = M('store')->where(array('uid' => $uid))->getField('plant_num');
        $wbtotal = number_format($wbd + $wbc, 4, ".", "");
        $grade = M('store')->where(array('uid' => $uid))->getField('vip_grade');

        $this->assign('step', $step);
        $this->assign('lastsy', $lastsy);
        $this->assign('wbc', $wbc);
        $this->assign('grade', $grade);
        $this->assign('wbtotal', $wbd);
        $this->assign('list', $list);
        $this->display();
    }

    //BAT转入页面
    public function WbaoIn()
    {
        $uid = session('userid');
        $mwenums = M('ucoins')->where(array('c_uid' => $uid, 'cid' => 1))->getField('c_nums');
        $this->assign('mwenums', $mwenums);
        $this->display();
    }

    //BAT冻结资产页面
    public function WBDongjie()
    {
        $uid = session('userid');
        $mwenums = M('store')->where(array('uid' => $uid))->getField('plant_num');
        $this->assign('mwenums', $mwenums);
        $this->display();
    }

    //BAT转入核对
    public function WBCheckuser()
    {
        $paynums = I('paynums', 'float', 0);
        $paynums = (float)$paynums;

        $uid = session('userid');
        $mwenums = M('ucoins')->where(array('c_uid' => $uid, 'cid' => 1))->getField('c_nums');
        if ($paynums > $mwenums) {
            ajaxReturn('您当前暂无这么多SP哦~', 0);
        }


        $getmsg = array('uname' => $uinfo['username'], 'getuid' => $uinfo['userid']);
        ajaxReturn($getmsg, 1);
    }


    //BAT转出核对
    public function WBCheckuser1()
    {
        $paynums = I('paynums', 'float', 0);
        $paynums = (float)$paynums;

        $uid = session('userid');
        $mwenums = M('store')->where(array('uid' => $uid))->getField('plant_num');
        if ($paynums > $mwenums) {
            ajaxReturn('您当前暂无这么多SP哦~', 0);
        }

        $getmsg = array('uname' => $uinfo['username'], 'getuid' => $uinfo['userid']);
        ajaxReturn($getmsg, 1);
    }

    //BAT转出页面
    public function WbaoOut()
    {
        $uid = session('userid');
        $mwenums = M('store')->where(array('uid' => $uid))->getField('plant_num');
        $this->assign('mwenums', $mwenums);
        $this->display();
    }

    // BAT冻结资产
    public function WBDong()
    {
        $paynums = I('paynums', 'float', 0);
        $pwd = trim(I('pwd'));
        $uid = session('userid');
        $mwenums = M('store')->where(array('uid' => $uid))->getField('plant_num');
        if ($paynums > $mwenums) {
            ajaxReturn('您当前暂无这么多SP哦~', 0);
        }
        //验证交易密码
        $minepwd = M('user')->where(array('userid' => $uid))->Field('account,mobile,safety_pwd,safety_salt')->find();
        $user_object = D('Home/User');
        $user_info = $user_object->Trans($minepwd['account'], $pwd);

        //冻结减BAT可用资产 加锁定资产

        $locknumy = M('store')->where(array('uid' => $uid))->getField('huafei_total');
        $locknum = $locknumy + $paynums;
        $grade = 0;
        if ($locknum >= 10000) {
            $grade = 3;
        } elseif ($locknum >= 5000) {
            $grade = 2;
        } elseif ($locknum >= 1000) {
            $grade = 1;
        }

        $datapay['vip_grade'] = $grade;
        $datapay['plant_num'] = array('exp', 'plant_num - ' . $paynums);
        $datapay['huafei_total'] = array('exp', 'huafei_total + ' . $paynums);
        $res_pay = M('store')->where(array('uid' => $uid))->save($datapay);

        if ($res_pay) {

            //添加BAT交易记录
            $wbaoss["crowds_id"] = 0;
            $wbaoss["create_time"] = time();
            $wbaoss["num"] = $paynums;
            $wbaoss["uid"] = $uid;
            $wbaoss["dprice"] = 0;
            $wbaoss["tprice"] = 0;
            $wbaoss["type"] = 5;//锁定资产
            $wbao_ss = M('wbao_detail')->add($wbaoss);

            ajaxReturn('SP锁定成功', 1, "Wbaobei");
        } else {
            ajaxReturn('SP锁定失败', 0);
        }
    }

    // BAT转出
    public function WBgetout()
    {
        $paynums = I('paynums', 'float', 0);
        $pwd = trim(I('pwd'));
        $uid = session('userid');
        $mwenums = M('store')->where(array('uid' => $uid))->getField('plant_num');
        if ($paynums > $mwenums) {
            ajaxReturn('您当前暂无这么多SP哦~', 0);
        }
        if ($paynums <= 0) {
            ajaxReturn('非法操作~', 0);
        }

        //验证交易密码
        $minepwd = M('user')->where(array('userid' => $uid))->Field('account,mobile,safety_pwd,safety_salt')->find();
        $user_object = D('Home/User');
        $user_info = $user_object->Trans($minepwd['account'], $pwd);


        //一旦用户转出小于某数量的币，则等级会降
        $grade1 = 3;
        if ($paynums < 1000) {
            $grade1 = 0;
        } elseif ($paynums < 5000) {
            $grade1 = 1;
        } elseif ($paynums < 10000) {
            $grade1 = 2;
        }

        //转出减BAT可用资产 加BAT币

        $locknumd = M('store')->where(array('uid' => $uid))->getField('huafei_total');
        $oldgrade = M('store')->where(array('uid' => $uid))->getField('vip_grade');
        $locknumz = $mwenums + $locknumd;//总资产低于相应币数就会降级
        $locknum = $locknumz - $paynums;//当前总资产数
        $grade = 0;
        if ($locknum >= 10000) {
            $grade = 3;
        } elseif ($locknum >= 5000) {
            $grade = 2;
        } elseif ($locknum >= 1000) {
            $grade = 1;
        }
        $graden = $grade1 > $grade ? $grade : $grade1;//取最小的
        if ($oldgrade > $graden) $datapay['vip_grade'] = $graden;//只降级不升级

        $datapay['plant_num'] = array('exp', 'plant_num - ' . $paynums);
        $res_pay = M('store')->where(array('uid' => $uid))->save($datapay);//转出-BAT

        $payout['c_nums'] = array('exp', 'c_nums + ' . $paynums);
        $res_pay = M('ucoins')->where(array('c_uid' => $uid, 'cid' => 1))->save($payout);//转出+BAT


        if ($res_pay) {

            //添加BAT交易记录
            $wbaoss["crowds_id"] = 0;
            $wbaoss["create_time"] = time();
            $wbaoss["num"] = $paynums;
            $wbaoss["uid"] = $uid;
            $wbaoss["dprice"] = 0;
            $wbaoss["tprice"] = 0;
            $wbaoss["type"] = 1;//转出
            $wbao_ss = M('wbao_detail')->add($wbaoss);

            ajaxReturn('SP转出成功', 1, "Wbaobei");
        } else {
            ajaxReturn('SP转出失败', 0);
        }
    }

    // BAT转入
    public function WBgetin()
    {
        $paynums = I('paynums', 'float', 0);

        $pwd = trim(I('pwd'));
        $uid = session('userid');
        $mwenums = M('ucoins')->where(array('c_uid' => $uid, 'cid' => 1))->getField('c_nums');
        if ($paynums > $mwenums) {
            ajaxReturn('您当前暂无这么多SP哦~', 0);
        }

        if ($paynums <= 0) {
            ajaxReturn('非法操作~', 0);
        }

        //验证交易密码
        $minepwd = M('user')->where(array('userid' => $uid))->Field('account,mobile,safety_pwd,safety_salt')->find();
        $user_object = D('Home/User');
        $user_info = $user_object->Trans($minepwd['account'], $pwd);

        //转入加BAT可用资产 减BAT币

        $datapay['plant_num'] = array('exp', 'plant_num + ' . $paynums);
        $res_pay = M('store')->where(array('uid' => $uid))->save($datapay);//转出+BAT
        //转出的扣钱
        $payout['c_nums'] = array('exp', 'c_nums - ' . $paynums);
        $res_pay = M('ucoins')->where(array('c_uid' => $uid, 'cid' => 1))->save($payout);//转出-BAT

        if ($res_pay) {

            //添加BAT交易记录
            $wbaoss["crowds_id"] = 0;
            $wbaoss["create_time"] = time();
            $wbaoss["num"] = $paynums;
            $wbaoss["uid"] = $uid;
            $wbaoss["dprice"] = 0;
            $wbaoss["tprice"] = 0;
            $wbaoss["type"] = 2;//转入
            $wbao_ss = M('wbao_detail')->add($wbaoss);

            ajaxReturn('BAT币转入成功', 1, "Wbaobei");
        } else {
            ajaxReturn('BAT币转入失败', 0);
        }
    }

    //    BAT转入
    public function Wegetin()
    {
        $type = intval(I('type', 1));
        $paynums = I('paynums', 'float', 0);
        $getu = trim(I('moneyadd'));
        $pwd = trim(I('pwd'));
        $uid = session('userid');
        $mwenums = M('ucoins')->where(array('c_uid' => $uid, 'cid' => $type))->getField('c_nums');
        $arrType = UcoinsModel::$TYPE_TABLE;
        if ($paynums > $mwenums) {
            ajaxReturn('您当前暂无这么多' . $arrType[$type] . '币哦~', 0);
        }

        $where['userid|mobile|wallet_add'] = $getu;
        $uinfo = M('user')->where($where)->Field('userid,username')->find();
        if ($uinfo['userid'] == $uid) {
            ajaxReturn('您不能给自己转账哦~', 0);
        }

        if (empty($uinfo) || $uinfo == '') {
            ajaxReturn('您输入的转出地址有误哦~', 0);
        }

        //验证交易密码
        $minepwd = M('user')->where(array('userid' => $uid))->Field('account,mobile,safety_pwd,safety_salt')->find();
        $user_object = D('Home/User');
        $user_info = $user_object->Trans($minepwd['account'], $pwd);

        //转入的加钱
        $issetgetu = M('ucoins')->where(array('c_uid' => $uinfo['userid'], 'cid' => $type))->count(1);
        if ($issetgetu <= 0) {
            $coinone['cid'] = $type;
            $coinone['c_nums'] = 0.0000;
            $coinone['c_uid'] = $uinfo['userid'];
            M('ucoins')->add($coinone);
        }
        $getNum = $paynums * 0.85;
        $datapay['c_nums'] = array('exp', 'c_nums + ' . $getNum);
        $res_pay = M('ucoins')->where(array('c_uid' => $uinfo['userid'], 'cid' => $type))->save($datapay);//转出+BAT
        //转出的扣钱
        $payout['c_nums'] = array('exp', 'c_nums - ' . $paynums);
        $res_pay = M('ucoins')->where(array('c_uid' => $uid, 'cid' => $type))->save($payout);//转出-BAT

        //转入的人+20%积分记录EEE
        $jifen_dochange['pay_id'] = $uid;
        $jifen_dochange['get_id'] = $uinfo['userid'];
        $jifen_dochange['get_nums'] = $paynums;
        $jifen_dochange['get_time'] = time();
        $jifen_dochange['get_type'] = $type;
        $res_tran = M('wetrans')->add($jifen_dochange);
        if ($res_tran) {
            ajaxReturn($arrType[$type] . '币转出成功', 1, "index");
        } else {
            ajaxReturn($arrType[$type] . '币转出失败', 0);
        }
    }

    public function Trans()
    {
        $type = I('type', 'intval', 0);
        $traInfo = M('wetrans');
        $uid = session('userid');
        if ($type == 1) {
            $where['pay_id'] = $uid;
        } else {
            $where['get_id'] = $uid;
        }

//        $where['get_type'] = 1;
        //分页
        $p = getpage($traInfo, $where, 15);
        $page = $p->show();
        $Chan_info = $traInfo->where($where)->order('id desc')->select();
        foreach ($Chan_info as $k => $v) {
            $Chan_info[$k]['get_timeymd'] = date('Y-m-d', $v['get_time']);
            $Chan_info[$k]['get_timedate'] = date('H:i:s', $v['get_time']);
            $Chan_info[$k]['outinfo'] = M('user')->where(array('userid' => $v['get_id']))->getField('username');
            $Chan_info[$k]['ininfo'] = M('user')->where(array('userid' => $v['pay_id']))->getField('username');
            $Chan_info[$k]['type_name'] = UcoinsModel::$TYPE_TABLE[$v['get_type']];
            //转入转出
            if ($type == 1) {
                $Chan_info[$k]['trtype'] = 1;
            } else {
                $Chan_info[$k]['trtype'] = 2;
            }
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
        $this->assign('type', $type);
        $this->display();
    }

    public function Turnout()
    {
        $this->display();
    }

    /**
     * 交易大厅
     * @author ldz
     * @time 2020/2/10 16:05
     */
    public function transaction()
    {
        $this->display();
    }

    /**
     * 股票排队列表
     */
    public function StockQueuelList()
    {
        $page = I('p', 1);
        $limit = 12;
        //买方列表
        $map['us.activation_time'] = array('neq', 0);
        $map['st.purchase_integral'] = array('gt', 0);
        $field = 'us.userid,us.username,us.activation_time,st.purchase_integral';
        $record_list = M('user')->alias('us')->where($map)
            ->join('ysk_store st on st.uid = us.userid')
            ->order('us.activation_time asc , us.userid asc')
            ->field($field)->limit(($page - 1) * $limit, $limit)->select();

        if (IS_AJAX) {
            if (count($record_list) >= 1) {
                ajaxReturn($record_list, 1);
            } else {
                ajaxReturn('暂无记录', 0);
            }
        }

        $this->assign('record_list', $record_list);
        $this->display();
    }

    //金积分购买
    public function yue_goumai()
    {
        //防重复提交
        if (session("gou_last_time")) {
            if ((int)time() - (int)session("gou_last_time") < 10) {
                ajaxReturn('对不起，当然访问人数太多，请稍后再试~', 0);
            }
        }
        $t = (int)time();
        session("gou_last_time", $t);

        $num = (float)I('num');
        $cid = (int)I('cid', 'intval', 0);
        $dealid = I('dealid', 'intval', 0);
        $dprice = trim(I('dprice'));
        $tprice = trim(I('tprice'));
        $pwd = trim(I('pwd'));
        $uid = session('userid');
        $payway = intval(I('payway', 1));

        $ss1 = M('deal')->where(array('id' => $dealid, 'type' => 1))->getField('num');
        $restn = $num - $ss1;

        if ($num < 0 || $tprice < 0 || $dprice < 0) ajaxReturn('非法输入~', 0);
        if (!$num | !$tprice) ajaxReturn('交易币的数量不能为空~', 0);
        //判断用户是否为空单
        $empty_order = M('user')->where(['userid' => $uid])->getField('empty_order');
        if ($empty_order) {
            ajaxReturn('对不起，您不能购买，请留言~', 0);
        }
        if ($restn > 0) ajaxReturn('交易币的数量超过最大限制~', 0);

        //验证交易密码
        $minepwd = M('user')->where(array('userid' => $uid))->Field('account,mobile,safety_pwd,safety_salt')->find();
        $user_object = D('Home/User');
        $user_info = $user_object->Trans($minepwd['account'], $pwd);

        if ($payway == 2) {
            $pay_way = 'conversion_release';
        } else {
            $pay_way = 'cangku_num';
        }
        //自己是否有足够金积分
        $my_yue = M('store')->where(array('uid' => $uid))->getField($pay_way);
        if ($tprice > $my_yue) {
            ajaxReturn('您当前账户暂无这么多金积分~', 0);
        }

        //挂卖单人的ID
        $sell_id = M('deal')->where(array('id' => $dealid, 'type' => 1))->getField('sell_id');
        if ($uid == $sell_id) {
            ajaxReturn('您不能和自己交易~', 0);
        }
        //检查 store表和 coindets 表是否有记录
        $ishas_store_u = M('store')->where(array('uid' => $uid))->count(1);
        if (!$ishas_store_u) M('store')->add(array('uid' => $uid, 'cangku_num' => 0.0000));
        $ishas_store_s = M('store')->where(array('uid' => $sell_id))->count(1);
        if (!$ishas_store_s) M('store')->add(array('uid' => $sell_id, 'cangku_num' => 0.0000));

        $issetgetu = M('ucoins')->where(array('c_uid' => $uid, 'cid' => $cid))->count(1);
        if ($issetgetu <= 0) {
            $coinone['cid'] = $cid;
            $coinone['c_nums'] = 0.0000;
            $coinone['c_uid'] = $uid;
            M('ucoins')->add($coinone);
        }

        $issetgets = M('ucoins')->where(array('c_uid' => $sell_id, 'cid' => $cid))->count(1);
        if ($issetgets <= 0) {
            $coinone1['cid'] = $cid;
            $coinone1['c_nums'] = 0.0000;
            $coinone1['c_uid'] = $sell_id;
            M('ucoins')->add($coinone1);
        }

        //购买的加币的数量、减金积分
        $datapay['c_nums'] = array('exp', 'c_nums + ' . $num);
        $res0 = M('ucoins')->where(array('c_uid' => $uid, 'cid' => $cid))->save($datapay);

        $datapay1[$pay_way] = array('exp', $pay_way . ' - ' . $tprice);
        $res1 = M('store')->where(array('uid' => $uid))->save($datapay1);

        //出售的扣币的数量、加金积分
        $payout['djie_nums'] = array('exp', 'djie_nums - ' . $num);
        $res2 = M('ucoins')->where(array('c_uid' => $sell_id, 'cid' => $cid))->save($payout);

        $payout1['cangku_num'] = array('exp', 'cangku_num + ' . $tprice);
        $res3 = M('store')->where(array('uid' => $sell_id))->save($payout1);

        $get_n = M('store')->where(array('uid' => $sell_id))->getField('cangku_num');
        if ($payway == 2) {
            $ExchangeData = [
                'uid' => $uid,
                'amount' => -$tprice,
                'type' => 4,
                'create_time' => date('YmdHis', time()),
            ];
            M('conversion_record')->add($ExchangeData);
        } else {
            $pay_n = M('store')->where(array('uid' => $uid))->getField('cangku_num');

            $changenums['now_nums'] = $pay_n;
            $changenums['now_nums_get'] = $get_n;
            $changenums['is_release'] = 1;
            $changenums['pay_id'] = $uid;
            $changenums['get_id'] = $sell_id;
            $changenums['get_nums'] = $tprice;
            $changenums['get_time'] = time();
            $changenums['get_type'] = 4;
            M('tranmoney')->add($changenums);
        }

        $changenums['now_nums'] = $get_n;
        $changenums['now_nums_get'] = $get_n;
        $changenums['is_release'] = 1;
        $changenums['pay_id'] = $sell_id;
        $changenums['get_id'] = $sell_id;
        $changenums['get_nums'] = $tprice;
        $changenums['get_time'] = time();
        $changenums['get_type'] = 32;
        M('tranmoney')->add($changenums);

        //剩余数量，更新订单状态1，为匹配交易
        if ($restn >= 0) $deals["status"] = 1;
        $deals["num"] = array('exp', 'num - ' . $num);
        $deal_s = M('deal')->where(array('id' => $dealid, 'type' => 1))->save($deals);

        //添加交易记录
        $buy_name = M('user')->where(array('userid' => $uid))->getField('username');

        $dealss["d_id"] = $dealid;
        $dealss["sell_id"] = $sell_id;
        $dealss["buy_id"] = $uid;
        $dealss["create_time"] = time();
        $dealss["buy_uname"] = $buy_name;
        $dealss["cid"] = $cid;
        $dealss["type"] = 1;
        $dealss["num"] = $num;
        $dealss["dprice"] = $dprice;
        $dealss["tprice"] = $tprice;
        $deal_ss = M('deals')->add($dealss);

        if ($res0 && $res3 && $deal_ss) {
            ajaxReturn('购买成功', 1, "/Turntable/Transaction");
        } else {
            ajaxReturn('购买失败', 0);
        }
    }


    //出售币
    public function yue_chushou()
    {
        if (session("chu_last_time")) {
            if ((int)time() - (int)session("chu_last_time") < 10) {
                ajaxReturn('对不起，10秒内不能频繁提交~', 0);
            }
        }
        $t = (int)time();
        session("chu_last_time", $t);

        $num = (float)I('num');
        $cid = (int)I('cid', 'intval', 0);
        $dealid = I('dealid', 'intval', 0);
        $dprice = trim(I('dprice'));
        $tprice = trim(I('tprice'));
        $pwd = trim(I('pwd'));
        $uid = session('userid');

        $ss1 = M('deal')->where(array('id' => $dealid, 'type' => 2))->getField('num');
        $restn = $num - $ss1;

        if ($num < 0 || $tprice < 0 || $dprice < 0) ajaxReturn('非法输入~', 0);
        if (!$num | !$tprice) ajaxReturn('交易币的数量不能为空~', 0);
        //判断用户是否为空单
        $empty_order = M('user')->where(['userid' => $uid])->getField('empty_order');
        if ($empty_order) {
            ajaxReturn('对不起，您不能出售，请留言~', 0);
        }
        if ($restn > 0) ajaxReturn('交易币的数量超过最大限制~', 0);
        //验证交易密码
        $minepwd = M('user')->where(array('userid' => $uid))->Field('account,mobile,safety_pwd,safety_salt')->find();
        $user_object = D('Home/User');
        $user_info = $user_object->Trans($minepwd['account'], $pwd);

        //自己是否有足够币出售
        $my_bi = M('ucoins')->where(array('c_uid' => $uid, 'cid' => $cid))->getField('c_nums');
        if ($num > $my_bi) {
            ajaxReturn('您当前账户暂无这么多币出售~', 0);
        }

        //挂买单人的ID
        $sell_id = M('deal')->where(array('id' => $dealid, 'type' => 2))->getField('sell_id');
        if ($uid == $sell_id) {
            ajaxReturn('您不能和自己交易~', 0);
        }

        //检查 store表和 coindets 表是否有记录
        $ishas_store_u = M('store')->where(array('uid' => $uid))->count(1);
        if (!$ishas_store_u) M('store')->add(array('uid' => $uid, 'cangku_num' => 0.0000));
        $ishas_store_s = M('store')->where(array('uid' => $sell_id))->count(1);
        if (!$ishas_store_s) M('store')->add(array('uid' => $sell_id, 'cangku_num' => 0.0000));

        $issetgetu = M('ucoins')->where(array('c_uid' => $uid, 'cid' => $cid))->count(1);
        if ($issetgetu <= 0) {
            $coinone['cid'] = $cid;
            $coinone['c_nums'] = 0.0000;
            $coinone['c_uid'] = $uid;
            M('ucoins')->add($coinone);
        }

        $issetgets = M('ucoins')->where(array('c_uid' => $sell_id, 'cid' => $cid))->count(1);
        if ($issetgets <= 0) {
            $coinone1['cid'] = $cid;
            $coinone1['c_nums'] = 0.0000;
            $coinone1['c_uid'] = $sell_id;
            M('ucoins')->add($coinone1);
        }

        //出售的减对应的币数、加对应的金积分
        $datapay['c_nums'] = array('exp', 'c_nums - ' . $num);
        $res_pay0 = M('ucoins')->where(array('c_uid' => $uid, 'cid' => $cid))->save($datapay);

        $datapay1['cangku_num'] = array('exp', 'cangku_num + ' . $tprice);
        $res_pay1 = M('store')->where(array('uid' => $uid))->save($datapay1);

        //购买的扣金积分、加币
        $payout['c_nums'] = array('exp', 'c_nums + ' . $num);
        $res_pay2 = M('ucoins')->where(array('c_uid' => $sell_id, 'cid' => $cid))->save($payout);
//        $payout1['cangku_num'] = array('exp', 'cangku_num - ' . $tprice);
//        $res_pay3= M('store')->where(array('uid'=>$sell_id))->save($payout1);

        //更新订单状态1，为匹配交易
        if ($restn >= 0) $deals["status"] = 1;
        $deals["num"] = array('exp', 'num - ' . $num);
        $deal_s = M('deal')->where(array('id' => $dealid, 'type' => 2))->save($deals);

        //添加交易记录
        $buy_name = M('user')->where(array('userid' => $sell_id))->getField('username');

        $dealss["d_id"] = $dealid;
        $dealss["sell_id"] = $uid;
        $dealss["buy_id"] = $sell_id;
        $dealss["create_time"] = time();
        $dealss["buy_uname"] = $buy_name;
        $dealss["cid"] = $cid;
        $dealss["type"] = 2;
        $dealss["num"] = $num;
        $dealss["dprice"] = $dprice;
        $dealss["tprice"] = $tprice;
        $deal_ss = M('deals')->add($dealss);

        $pay_n = M('store')->where(array('uid' => $sell_id))->getField('cangku_num');
        $get_n = M('store')->where(array('uid' => $uid))->getField('cangku_num');

        $changenums['now_nums'] = $pay_n;
        $changenums['now_nums_get'] = $get_n;
        $changenums['is_release'] = 1;
        $changenums['pay_id'] = $sell_id;
        $changenums['get_id'] = $uid;
        $changenums['get_nums'] = $tprice;
        $changenums['get_time'] = time();
        $changenums['get_type'] = 5;
        M('tranmoney')->add($changenums);

        if ($res_pay0 && $deal_ss) {
            ajaxReturn('售出成功', 1, "/Turntable/Transaction");

        } else {
            ajaxReturn('售出失败', 0);
        }
    }

    //交易中心
    public function Transactionsell()
    {

        $cid = (int)I('cid', 'intval', 0);
        if ($cid == 'intval') $cid = 1;
        $uid = session('userid');

        //查询当前币对应价格名称信息
        $coindets = M('coindets')->order('coin_addtime desc,cid asc')->where(array("cid" => $cid))->find();

        //当前我的资产
        $minecoins = M('ucoins')->where(array('c_uid' => $uid, 'cid' => $cid))->order('id asc')->find();
        $my_yue = M('store')->where(array('uid' => $uid))->getField('cangku_num');

        //交易列表
        $deals = M('deal a')->join('ysk_user b ON a.sell_id=b.userid')->field('b.username as u_name,a.id as d_id,b.account as u_account,b.img_head as u_img_head,a.num as d_num,a.dprice as d_dprice,a.id as d_id')->where(array("a.type" => 2, "a.status" => 0, "a.cid" => $cid))->limit($page, 5000)->order('d_dprice desc')->select();

        //类型列表
        $type_list = UcoinsModel::$TYPE_TABLE;

        $this->assign('coindets', $coindets);
        $this->assign('deals', $deals);
        $this->assign('minecoins', $minecoins);
        $this->assign('my_yue', $my_yue);
        $this->assign('cid', $cid);
        $this->assign('arr', $type_list);

        $this->display();


    }


    //取消订单
    public function quxiao_order()
    {

        $id = (int)I('id', 'intval', 0);
        $uid = session('userid');

        $mydeal = M('deal')->where(array("id" => $id, "sell_id" => $uid))->find();

        if (!$mydeal) ajaxReturn('订单不存在~', 0);
        if ($mydeal['status'] != 0) {
            ajaxReturn('订单只有在未出售才能取消~', 0);
        }

        $type = $mydeal["type"];
        $num = $mydeal["num"];
        $cid = $mydeal["cid"];
        $dprice = $mydeal["dprice"];
        if ($type == 1) {//为出售单，则返还剩余相应的币
            $payout['c_nums'] = array('exp', 'c_nums + ' . $num);
            $res1 = M('ucoins')->where(array('c_uid' => $uid, 'cid' => $cid))->save($payout);
        } elseif ($type == 2) {//为购买单，则返还剩余相应的金积分
            $tprice = $num * $dprice;

            if ($mydeal['pay_type'] == 1) {
                $payout1['cangku_num'] = array('exp', 'cangku_num + ' . $tprice);
                $res2 = M('store')->where(array('uid' => $uid))->save($payout1);

                //生成金积分记录
                $pay_n = M('store')->where(array('uid' => $uid))->getField('cangku_num');

                $changenums['now_nums'] = $pay_n;
                $changenums['now_nums_get'] = $pay_n;
                $changenums['is_release'] = 1;
                $changenums['pay_id'] = 0;
                $changenums['get_id'] = $uid;
                $changenums['get_nums'] = $tprice;
                $changenums['get_time'] = time();
                $changenums['get_type'] = 6;
                M('tranmoney')->add($changenums);
            }

            if ($mydeal['pay_type'] == 2) {
                $payout1['conversion_release'] = array('exp', 'conversion_release + ' . $tprice);
                $res2 = M('store')->where(array('uid' => $uid))->save($payout1);

                $ExchangeData = [
                    'uid' => $uid,
                    'amount' => $tprice,
                    'type' => 5,
                    'create_time' => date('YmdHis', time())
                ];
                M('conversion_record')->add($ExchangeData);
            }

        }
        //把此订单状态设置为2，即为取消
        $payout2['status'] = 2;
        $res3 = M('deal')->where(array("id" => $id, "sell_id" => $uid))->save($payout2);

        if ($res3) {
            ajaxReturn('取消成功', 0, "Orderinfos");
        } else {
            ajaxReturn('操作失败', 0, "Orderinfos");
        }
    }

    /**
     * 我的交易
     * @author ldz
     * @time 2020/2/10 11:20
     */
    public function Salesell()
    {
        if (IS_AJAX) {
            $model = new DealModel();
            $res = $model->saleSell();
            if (!$res) {
                ajaxReturn($model->getError(), 0);
            }
            ajaxReturn('出售成功', 1, '/Turntable/Transaction');

        }
        $uid = session('userid');
        $storeInfo = M('store')->where(array('uid' => $uid))->field('cangku_num,fengmi_num')->find();

        //银行卡
        $morecars = M('ubanks as u')->join('RIGHT JOIN ysk_bank_name as banks ON u.card_id = banks.pid')
            ->where(array('u.user_id' => $uid, 'delete_time' => ''))->order('u.id desc')
            ->field('u.hold_name,u.id,u.card_number,u.user_id,banks.banq_genre,banks.banq_img')->select();
        //是否存在支付宝二维码
        $path = $_SERVER['DOCUMENT_ROOT'];
        $uid = session('userid');
        if (file_exists($path . '/Uploads/alipay/' . $uid . '.png')) {
            $this->assign('alipay_img', 1);
        }
        //是否存在微信二维码
        if (file_exists($path . '/Uploads/wechat/' . $uid . '.png')) {
            $this->assign('wechat_img', 1);
        }

        $this->assign('uid', $uid);
        $this->assign('storeInfo', $storeInfo);
        $this->assign('morecars', $morecars);
        $this->display();
    }

    //发布购买订单的页面
    public function Salebuys()
    {

        $uid = session('userid');
        $cid = (int)I('cid', 'intval', 0);
        //查询当前币对应价格及名称
        $coindets = M('coindets')->order('coin_addtime desc,cid asc')->where(array("cid" => $cid))->find();


        //当前我的资产
        $minecoins = M('ucoins')->where(array('c_uid' => $uid, 'cid' => $cid))->order('id asc')->find();
        $my_yue = M('store')->where(array('uid' => $uid))->getField('cangku_num');

        //类型列表
        $type_list = UcoinsModel::$TYPE_TABLE;

        $this->assign('minecoins', $minecoins);
        $this->assign('my_yue', $my_yue);
        $this->assign('coindets', $coindets);
        $this->assign('cid', $cid);
        $this->assign('arr', $type_list);
        $this->display();
    }


    //提交发布购买订单
    public function T_Salebuys()
    {

        $num = (float)I('num');
        $cid = (int)I('cid', 'intval', 0);
        $dprice = trim(I('dprice'));
        $tprice = $num * $dprice;
        $pwd = trim(I('pwd'));
        $uid = session('userid');
        $payway = intval(trim(I('payway', 1)));

        //判断用户是否是空单
        $empty_order = M('user')->where(['userid' => $uid])->getField('empty_order');
        if ($empty_order) {
            ajaxReturn('对不起，您不能发布购买订单，请留言~', 0);
        }

        $nowprice = M('coindets')->where(array('cid' => $cid))->order('coin_addtime desc')->getField('coin_price');

        if ($num < 0 || $tprice < 0 || $dprice < 0) ajaxReturn('非法输入~', 0);
        if (!$num | !$tprice) ajaxReturn('交易币的数量不能为空~', 0);

        if ($dprice > 1.1 * $nowprice) ajaxReturn('交易币的单价不能高过当前价格10%~', 0);

        //验证交易密码
        $minepwd = M('user')->where(array('userid' => $uid))->Field('account,mobile,safety_pwd,safety_salt')->find();
        $user_object = D('Home/User');
        $user_object->Trans($minepwd['account'], $pwd);

        if ($cid == 9 && $payway == 2) {
            ajaxReturn('ABS暂不支持兑换分支付~', 0);
        }

        if ($payway == 1) {
            //自己是否有足够的EP
            $my_yue = M('store')->where(array('uid' => $uid))->getField('cangku_num');
            if ($tprice > $my_yue) {
                ajaxReturn('您当前账户暂无这么多EP~', 0);
            }

            //冻结我的金积分
            $payout1['djie_num'] = array('exp', 'djie_num + ' . $tprice);
            $payout1['cangku_num'] = array('exp', 'cangku_num - ' . $tprice);
            $res_pay3 = M('store')->where(array('uid' => $uid))->save($payout1);
            if (!$res_pay3) {
                ajaxReturn('购买失败，请重试~', 0);
            }

            $pay_n = M('store')->where(array('uid' => $uid))->getfield('cangku_num');
            //生成金积分记录
            $changenums['pay_id'] = $uid;
            $changenums['get_id'] = 0;
            $changenums['now_nums'] = $pay_n;
            $changenums['now_nums_get'] = $pay_n;
            $changenums['is_release'] = 1;
            $changenums['get_nums'] = $tprice;
            $changenums['get_time'] = time();
            $changenums['get_type'] = 3;
            M('tranmoney')->add($changenums);
        } else {
            //自己是否有足够的EP
            $my_yue = M('store')->where(array('uid' => $uid))->getField('conversion_release');
            if ($tprice > $my_yue) {
                ajaxReturn('您当前账户暂无这么多兑换分~', 0);
            }
            //扣除兑换分
            $payout1['djie_num'] = array('exp', 'djie_num + ' . $tprice);
            $payout1['conversion_release'] = array('exp', 'conversion_release - ' . $tprice);
            $res_pay3 = M('store')->where(array('uid' => $uid))->save($payout1);
            if (!$res_pay3) {
                ajaxReturn('购买失败，请重试~', 0);
            }

            $ExchangeData = [
                'uid' => $uid,
                'amount' => -$tprice,
                'type' => 4,
                'create_time' => date('YmdHis', time())
            ];
            M('conversion_record')->add($ExchangeData);
        }

        //生成交易记录
        $deal['sell_id'] = $uid;  //挂售出单人ID
        $deal['total_num'] = $num;
        $deal['num'] = $num;
        $deal['ynum'] = $num;
        $deal['create_time'] = time();
        $deal['tprice'] = $tprice;
        $deal['dprice'] = $dprice;
        $deal['cid'] = $cid;
        $deal['type'] = 2;//2为购买订单
        $deal['pay_type'] = $payway;
        $res_tran = M('deal')->add($deal);

        ajaxReturn('发布成功', 1, "/Turntable/Transactionsell/cid/" . $cid);
    }


    //订单
    public function Orderinfos()
    {
        $cid = (int)I('cid', 'intval', 0);
        $step = I('step');//
        if (!$step) $step = 1;
        $uid = session('userid');
        $where["sell_id"] = $uid;
        $where["status"] = 0;
        $where["cid"] = $cid;
        if ($step == 2) $where["status"] = 1;
        $list = M('deal')->order('id desc')->where($where)->limit(1000)->select();

        $this->assign('list', $list);
        $this->assign('step', $step);
        $this->assign('cid', $cid);
        $this->display();
    }

    //交易记录
    public function Transreocrds()
    {
        $cid = (int)I('cid', 'intval', 0);
        $uid = session('userid');
        $where["buy_id"] = $uid;
        $where["cid"] = $cid;
        $list = M('deals')->where($where)->order('id desc')->limit(1000)->select();
        $this->assign('list', $list);
        $this->assign('cid', $cid);

        $this->display();
    }

    //卖出记录
    public function sellRecords()
    {
        $cid = (int)I('cid', 'intval', 0);
        $uid = session('userid');
        $where["sell_id"] = $uid;
        $where["cid"] = $cid;
        $list = M('deal')->where($where)->order('id desc')->limit(1000)->select();
        $this->assign('list', $list);
        $this->assign('cid', $cid);

        $this->display();
    }


    //众筹
    public function Crowds()
    {
        $step = I('step');
        $html = 'Crowds' . $step;
        $time_n = time();

        if ($step >= 1) {

            if ($step == 1) {
                $list = M('crowds')->where("open_time<=" . $time_n . " and status<>2")->order("create_time desc")->find();
            } else {
                $list = M('crowds')->where("open_time<=" . $time_n . " and status=2")->limit(5)->order("create_time desc")->select();
            }

            $this->assign('list', $list);
            $this->display('Turntable/' . $html);
        } else {

            $list = M('crowds')->where("status=0 and open_time>" . $time_n)->order('id desc')->find();
            $this->assign('list', $list);
            $this->display();
        }
    }


    //金积分购买
    public function Crowds_goumai()
    {

        //防重复提交
        if (session("gou_last_time1")) {
            if ((int)time() - (int)session("gou_last_time1") < 10) {
                ajaxReturn('对不起，当然访问人数太多，请稍等再试~', 0);
            }
        }


        $t = (int)time();
        session("gou_last_time1", $t);


        $num = (float)I('num');
        $cid = (int)I('cid', 'intval', 0);
        $dealid = I('dealid', 'intval', 0);
        $dprice = trim(I('dprice'));
        $tprice = trim(I('tprice'));
        $pwd = trim(I('pwd'));
        $uid = session('userid');


        $ss1 = 20000;
        $restn = $num - $ss1;

        if ($num < 0 || $tprice < 0 || $dprice < 0) ajaxReturn('非法输入~', 0);
        if (!$num | !$tprice) ajaxReturn('交易币的数量不能为空~', 0);
        if ($restn > 0) ajaxReturn('交易币的数量超过最大限制~', 0);


        //验证交易密码
        $minepwd = M('user')->where(array('userid' => $uid))->Field('account,mobile,safety_pwd,safety_salt,empty_order')->find();
        if ($minepwd['empty_order']) {
            ajaxReturn('该用户不能参与众筹，请留言~', 0);
        }
        $user_object = D('Home/User');
        $user_info = $user_object->Trans($minepwd['account'], $pwd);

        //自己是否有足够金积分
        $my_yue = M('store')->where(array('uid' => $uid))->getField('cangku_num');
        if ($tprice > $my_yue) {
            ajaxReturn('您当前账户暂无这么多EP~', 0);
        }

        //判断用户是否是有效用户   大于1500为有效用户
        $motivity = M('store')->where(['uid' => $uid])->getField('fengmi_total');
        if ($motivity < 1500) {
            ajaxReturn('您不是有效用户，请升级（有效用户是原动力要1500）~', 0);
        }

        //查询该会员本期已经买了多少BAT
        $bnums = 0;
        $benqi = M('crowds_detail')->where(array('crowds_id' => $dealid, 'uid' => $uid))->Field('sum(num) as nums')->find();
        if ($benqi) $bnums = $benqi["nums"];

        if ($bnums >= $ss1) {
            ajaxReturn('本期众筹您已经购买了' . $ss1 . '枚，无法继续购买~', 0);
        }

        //检查 store表和 coindets 表是否有记录

        $ishas_store_u = M('store')->where(array('uid' => $uid))->count(1);
        if (!$ishas_store_u) M('store')->add(array('uid' => $uid, 'cangku_num' => 0.0000));


        $issetgetu = M('ucoins')->where(array('c_uid' => $uid, 'cid' => $cid))->count(1);
        if ($issetgetu <= 0) {
            $coinone['cid'] = $cid;
            $coinone['c_nums'] = 0.0000;
            $coinone['c_uid'] = $uid;
            M('ucoins')->add($coinone);
        }
        // else{
        //     M('ucoins')->where(array('c_uid'=>$uid,'cid'=>$cid))->setinc('c_nums',$num);
        // }


        //购买的在BAT的冻结字段里加币的数量、并减该会员金积分    huafei_total字段为冻结数

        //2018-11-03 暂时不添加到用户的SP链总资产
//        $datapay['huafei_total'] = array('exp', 'huafei_total + ' . $num);
        $datapay['cangku_num'] = array('exp', 'cangku_num - ' . $tprice);
        $res1 = M('store')->where(array('uid' => $uid))->save($datapay);

        //添加金积分记录


        $pay_n = M('store')->where(array('uid' => $uid))->getfield('cangku_num');
        $changenums['now_nums'] = $pay_n;
        $changenums['now_nums_get'] = $pay_n;
        $changenums['is_release'] = 1;
        $changenums['pay_id'] = $uid;
        $changenums['get_id'] = 0;
        $changenums['get_nums'] = $tprice;
        $changenums['get_time'] = time();
        $changenums['get_type'] = 7;
        M('tranmoney')->add($changenums);


        //添加众筹交易记录
        $dealss["crowds_id"] = $dealid;
        $dealss["uid"] = $uid;
        $dealss["create_time"] = time();
        $dealss["num"] = $num;
        $dealss["dprice"] = $dprice;
        $dealss["tprice"] = $tprice;
        $deal_ss = M('crowds_detail')->add($dealss);

        //添加BAT交易记录
        $wbaoss["crowds_id"] = $dealid;
        $wbaoss["create_time"] = time();
        $wbaoss["num"] = $num;
        $wbaoss["uid"] = $uid;
        $wbaoss["dprice"] = $dprice;
        $wbaoss["tprice"] = $tprice;
        $wbaoss["type"] = 2;//转入
        $wbao_ss = M('wbao_detail')->add($wbaoss);


        if ($res1 && $deal_ss && $wbao_ss) {
            ajaxReturn('购买成功', 1, "/Turntable/Crowds/step/1");
        } else {
            ajaxReturn('购买失败', 0);
        }

    }

    //众筹记录
    public function Crowdrecords()
    {

        $step = I('step');
        $uid = session('userid');
        if ($step == 1) {
            $list = M('wbao_detail')->where("type=3 and num > 0 and uid=" . $uid)->order("create_time desc")->select(); //释放记录
        } else {

            $list = M('crowds_detail')->where("uid=" . $uid)->order("create_time desc")->select();
        }

        $this->assign('list', $list);
        $this->assign('step', $step);
        $this->display();
    }

    /**
     * 买家确定打款
     * @author ldz
     * @time 2020/2/11 9:33
     */
    public function Conpay()
    {
        if (IS_AJAX) {
            $dealModel = new DealModel();
            $res = $dealModel->confirmReceipt();
            if (!$res) {
                ajaxReturn($dealModel->getError(), 0);
            }
            ajaxReturn('已打款，请耐心等待卖家收款', 1);
        } else {
            ajaxReturn('打款提交失败', 0);
        }
    }

    //买家打款列表
    public function ConpayList()
    {
        //查询我买入的
        $uid = session('userid');
        $banks = M('ubanks ub');
        $where['ds.buy_id'] = $uid;
        $where['ds.status'] = ['neq', 4];
        //分页
        $traInfo = M('deals ds')->join('ysk_deal d on ds.d_id = d.id');
        $p = getpage($traInfo, $where, 50);
        $page = $p->show();
        $orders = $traInfo->field('ds.*,d.card_id,pay_way')->where($where)->order('ds.create_time desc')->select();
        //收款人
        foreach ($orders as $k => $v) {
            //银行卡号.开户支行.开户银行
            $bankinfos = $banks->where(array('id' => $v['card_id']))->field('hold_name,card_number,card_id,open_card')->find();
            $uinfomsg = M('user')->where(array('userid' => $v['sell_id']))->Field('username,mobile')->find();
            $orders[$k]['cardnum'] = $bankinfos['card_number'];
            $orders[$k]['bname'] = M('bank_name')->where(array('q_id' => $bankinfos['card_id']))->getfield('banq_genre');
            $orders[$k]['openrds'] = $bankinfos['open_card'];
            $orders[$k]['uname'] = $bankinfos['hold_name'];
            $orders[$k]['umobile'] = $uinfomsg['mobile'];
            if ($v['pay_way'] == 2 || $v['pay_way'] == 3) {
                $sell_info = M('user')->where(['userid' => $v['sell_id']])->field('username')->find();
                $orders[$k]['uname'] = $sell_info['username'];
                if ($v['pay_way'] == 2) {
                    $orders[$k]['pay_img'] = '/Uploads/alipay/' . $v['sell_id'] . '.png';
                } else {
                    $orders[$k]['pay_img'] = '/Uploads/wechat/' . $v['sell_id'] . '.png';
                }
            }
        }

        $this->assign('page', $page);
        $this->assign('orders', $orders);
        $this->display();
    }

    /**
     * 卖家中心
     */
    public function Conpayd()
    {
        $uid = session('userid');
        if (IS_AJAX) { // 确认收款
            $dealModel = new DealModel();
            $deals_id = I('deals_id', 'intval', 0);
            $res = $dealModel->confirmSk($uid, $deals_id);
            if (!$res) {
                ajaxReturn($dealModel->getError(), 0);
            }
            ajaxReturn('收款成功', 1);
        }
        //查询我买入的
        $banks = M('ubanks ub');
        $where['ds.sell_id'] = $uid;
        $where['ds.status'] = ['neq', 4];

        //分页
        $traInfo = M('deals ds')->join('ysk_deal d on ds.d_id = d.id');
        $p = getpage($traInfo, $where, 50);
        $page = $p->show();
        $orders = $traInfo->field('ds.*,d.pay_way,d.card_id')->where($where)->order('ds.img_upload_time desc')->select();
        //收款人
        foreach ($orders as $k => $v) {
            //银行卡号.开户支行.开户银行
            $bankinfos = $banks->where(array('id' => $v['card_id']))->field('hold_name,card_number,card_id,open_card')->find();
            $uinfomsg = M('user')->where(array('userid' => $v['sell_id']))->Field('username,mobile')->find();
            $orders[$k]['cardnum'] = $bankinfos['card_number'];
            $orders[$k]['bname'] = M('bank_name')->where(array('q_id' => $bankinfos['card_id']))->getfield('banq_genre');
            $orders[$k]['openrds'] = $bankinfos['open_card'];
            $orders[$k]['uname'] = $bankinfos['hold_name'];
            $orders[$k]['umobile'] = $uinfomsg['mobile'];
            if ($v['pay_way'] == 2 || $v['pay_way'] == 3) {
                $sell_info = M('user')->where(['userid' => $v['sell_id']])->field('username')->find();
                $orders[$k]['uname'] = $sell_info['username'];
                if ($v['pay_way'] == 2) {
                    $orders[$k]['pay_img'] = '/Uploads/alipay/' . $v['sell_id'] . '.png';
                } else {
                    $orders[$k]['pay_img'] = '/Uploads/wechat/' . $v['sell_id'] . '.png';
                }
            }
        }

        $this->assign('page', $page);
        $this->assign('orders', $orders);
        $this->display();
    }

    /**
     * 打印凭证页
     */
    public function Paidimg()
    {
        $id = I('id');
        $imginfo = M('deals')->where(array('id' => $id))->getField('img');
        $this->assign('imginfo', $imginfo);

        $this->display('Growth/Paidimg');
    }

    /**
     * 买入QBB列表
     * @author ldz
     * @time 2020/1/14 13:54
     */
    public function buySharesList()
    {
        $sell_type = trim(I('sell_type', 1));
        $where['sell_type'] = $sell_type;
        $where['status'] = ['in', [0, 1]];
        $models = M('deal');
        $p = Fgetpage($models, $where);
        $page = $p->show();
        $order_info = $models->where($where)->order('id desc')->select();


        $this->assign('sell_type', ($sell_type == 1) ? '买入生产总资产' : '买入消费通证');
        $this->assign('page', $page);
        $this->assign('order_info', $order_info);
        $this->display();
    }

    /**
     * 购买页面
     * @author ldz
     * @time 2020/1/16 9:22
     */
    public function buyPage()
    {
        $order_id = intval(I('order_id', 0));
        $orderInfo = M('deal')->where(['id' => $order_id])->find();
        if (!$orderInfo) {
            $this->error('该订单不存在，请重新选择');
        }

        $this->assign('orderInfo', $orderInfo);
        $this->display();
    }

    /**
     * 买入
     * @author ldz
     * @time 2020/1/14 13:55
     */
    public function subBuyShares()
    {
        if (IS_AJAX) {
            $dealModel = new DealModel();
            $res = $dealModel->buyShares();
            if (!$res) {
                ajaxReturn($dealModel->getError(), 0);
            }
            ajaxReturn('买入成功，请及时打款', 1, U('Turntable/ConpayList'));
        } else {
            ajaxReturn('请求方式有误，请重试', 0);
        }
    }


}