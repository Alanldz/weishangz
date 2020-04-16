<?php

namespace Home\Controller;

use Home\Model\TranmoneyModel;

class GrowthController extends CommonController
{
    //取消订单
    public function quxiao_order()
    {
        $id = (int)I('id', 'intval', 0);
        $uid = session('userid');
        $myDeal = M('trans')->where(array("id" => $id, "payin_id|payout_id" => $uid, "pay_state" => array("lt", 2)))->find();

        if (!$myDeal) ajaxReturn('订单不存在~', 0);

        $type = $myDeal["trans_type"];
        M('trans_quxiao')->add($myDeal);//把记录复制到另一个表
        if ($type == 0) {
            //卖出单，自己是购买方，只清空payin_id和改变pay_state为0
            $payout['payin_id'] = 0;
            $payout['pay_state'] = 0;
            $res = M('trans')->where(array('id' => $id))->save($payout);
        } elseif ($type == 1) {//为购买单，删除订单
            $res = M('trans')->delete($id);
        }

        if ($res) {
            ajaxReturn('取消成功', 1);
        } else {
            ajaxReturn('操作失败', 1);
        }
    }

    /**
     * 买入
     */
    public function Purchase()
    {
        $uid = session('userid');
        $cid = trim(I('cid'));
        if (empty($cid)) {
            $mapcas['user_id&is_default'] = array($uid, 1, '_multi' => true);
            $carinfo = M('ubanks')->where($mapcas)->count(1);
            if ($carinfo < 1) {
                $morecars = M('ubanks as u')->join('RIGHT JOIN ysk_bank_name as banks ON u.card_id = banks.pid')->where(array('u.user_id' => $uid))->limit(1)->field('u.hold_name,u.id,u.card_number,u.user_id,banks.banq_genre')->find();
            } else {
                $morecars = M('ubanks as u')->join('RIGHT JOIN ysk_bank_name as banks ON u.card_id = banks.pid')->where(array('u.user_id' => $uid, 'is_default' => 1))->limit(1)->field('u.hold_name,u.id,u.card_number,u.user_id,banks.banq_genre')->find();
            }
        } else {
            $morecars = M('ubanks as u')->join('RIGHT JOIN ysk_bank_name as banks ON u.card_id = banks.pid')->where(array('u.id' => $cid))->limit(1)->field('u.hold_name,u.id,u.card_number,u.user_id,banks.banq_genre')->find();
        }

        //生成买入订单
        if (IS_AJAX) {
            $pwd = trim(I('pwd'));
            $sellnums = trim(I('sellnums'));//出售数量
            $cardid = trim(I('cardid'));//银行卡id
            $messge = trim(I('messge'));//留言
            $sellAll = array(500, 1000, 3000, 5000, 10000, 30000);
            if (!in_array($sellnums, $sellAll)) {
                ajaxReturn('您选择买入的金额不正确', 0);
            }
            //验证银行卡是否是自己
            $id_Uid = M('ubanks')->where(array('id' => $cardid))->getField('user_id');
            if ($id_Uid != $uid) {
                ajaxReturn('对不起,该张银行卡不是您的哦~', 0);
            }
            //验证交易密码
            $minepwd = M('user')->where(array('userid' => $uid))->Field('account,mobile,safety_pwd,safety_salt')->find();
            $user_object = D('Home/User');
            $user_object->Trans($minepwd['account'], $pwd);
            //生成订单
            $data['pay_no'] = build_order_no();
            $data['payin_id'] = $uid;
            $data['out_card'] = $cardid;
            $data['pay_nums'] = $sellnums;
            $data['trade_notes'] = $messge;
            $data['pay_time'] = time();
            $data['trans_type'] = 1;
            $res_Add = M('trans')->add($data);
            //给自己减少这么多余额
            if ($res_Add) {
                ajaxReturn('买入订单创建成功', 1);
            }
        }

        $userInfo = M('user')->where(['userid' => $uid])->field('quanxian')->find();
        $quanxian = explode("-", $userInfo['quanxian']);

        //EP
        $store = M('store')->where(['uid' => $uid])->field('cangku_num')->find();

        //挂卖列表
        $where['payout_id'] = ['neq', $uid];
        $where['pay_state'] = 0;
        $where['trans_type'] = 0;
        $traInfo = M('trans');
        $orders = $traInfo->where($where)->order('id desc')->select();

        $this->assign('orders', $orders);
        $this->assign('quanxian', $quanxian);
        $this->assign('morecars', $morecars);
        $this->assign('store', $store);
        $this->display();
    }

    /**
     * 添加银行卡
     */
    public function Addbank()
    {
        $uid = session('userid');
        $back_url = trim(I('back_url'));
        if (IS_AJAX) {
            $crkxm = trim(I('crkxm'));  //真实姓名
            $khy = trim(I('khy'));      //开户行
            $yhk = trim(I('yhk'));      //银行卡号
            $khzy = trim(I('khzy'));    //开户行

            if (empty($crkxm)) {
                ajaxReturn('请输入真实姓名', 0);
            }
            if ($crkxm == '123') {
                ajaxReturn('姓名不能为:123，请重新输入真实姓名', 0);
            }
            if (empty($khy)) {
                ajaxReturn('请选择开户行', 0);
            }
            if (empty($yhk)) {
                ajaxReturn('请输入银行卡号', 0);
            }
            if (empty($khzy)) {
                ajaxReturn('请输入开户支行', 0);
            }

            $is_bank = M('ubanks')->where(['card_number' => $yhk, 'delete_time' => ''])->count();
            if ($is_bank) {
                ajaxReturn('卡号已存在', 0);
            }

            //开启事物
            M()->startTrans();
            $data['hold_name'] = $crkxm;
            $data['card_id'] = $khy;
            $data['card_number'] = $yhk;
            $data['open_card'] = $khzy;
            $data['add_time'] = time();
            $data['user_id'] = $uid;

            $res = M('ubanks')->add($data);
            if (!$res) {
                M()->rollback();
                ajaxReturn('银行卡添加失败，请重试', 0);
            }
            //设置用户银行卡姓名
            $bank_uname = M('user')->where(array('userid' => $uid))->getField('bank_uname');
            if (empty($bank_uname)) {
                $setUserName = M('user')->where(array('userid' => $uid))->setField('bank_uname', $crkxm);
                if (!$setUserName) {
                    M()->rollback();
                    ajaxReturn('银行卡添加失败，请重试', 0);
                }
            }
            M()->commit();
            ajaxReturn('银行卡添加成功', 1, U('Growth/Cardinfos', array('back_url' => $back_url)));
        }

        $bankInfo = M('bank_name')->order('q_id asc')->select();
        $username = M('user')->where(['userid' => $uid])->getField('username');
        $this->assign('bakinfo', $bankInfo);
        $this->assign('username', $username);
        $this->assign('back_url', $back_url);
        $this->display();
    }

    /**
     * 订单中心
     */
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
        $where['payin_id'] = $uid;

        //分页
        $p = getpage($traInfo, $where, 20);
        $page = $p->show();
        $orders = $traInfo->where($where)->order('id desc')->select();
        $banks = M('ubanks');
        foreach ($orders as $k => $v) {
            if ($v['payin_id'] != '') {
                //银行卡号.开户支行.开户银行
                $bankinfos = $banks->where(array('id' => $v['card_id']))->field('hold_name,card_number,card_id,open_card')->find();
                $uinfomsg = M('user')->where(array('userid' => $v['payout_id']))->Field('username,mobile')->find();
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

    /**
     * 现金购入
     */
    public function Conpay()
    {
        //查询我买入的
        $uid = session('userid');
        if (IS_AJAX) {
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
                $type = substr($picname, strripos($picname, ".") + 1);; //限制上传格式
                if ($type != "gif" && $type != 'GIF' && $type != "jpg" && $type != "JPG" && $type != "png" && $type != 'PNG' && $type != "jpeg" && $type != "JPEG") {
                    ajaxReturn('图片格式不对', 0);
                }
                $pics = uniqid() . $type; //命名图片名称
                //上传路径
                $pic_path = "./Uploads/Payvos/" . $pics;
                move_uploaded_file($_FILES['uploadfile']['tmp_name'], $pic_path);
            }
            $size = round($picsize / 1024, 2); //转换成kb
            $pic_path = trim($pic_path, '.');
            if ($size) {
                $data['trans_img'] = $pic_path;
                $data['pay_state'] = 2;
                $data['img_upload_time'] = time();
                $res = M('trans')->where(array('id' => $trid))->setField($data);
                if ($res) {
                    //成功发送短信通知
                    $mobile = M('trans as t')->where(['id' => $trid])->join('ysk_user as u on u.userid = t.payout_id')->getField('u.mobile');
                    sendMessage($mobile, '109600');
                    ajaxReturn('打款提交成功', 1, '/Growth/Conpay');
                } else {
                    ajaxReturn('打款提交失败', 0);
                }
            }
        }

        $traInfo = M('trans');
        $banks = M('ubanks');
        $where['payin_id'] = $uid;
        $where['pay_state'] = 1;
        //分页
        $p = getpage($traInfo, $where, 20);
        $page = $p->show();
        $orders = $traInfo->where($where)->order('id desc')->select();
        //收款人
        foreach ($orders as $k => $v) {
            //银行卡号.开户支行.开户银行
            $bankinfos = $banks->where(array('id' => $v['card_id']))->field('hold_name,card_number,card_id,open_card')->find();
            $uinfomsg = M('user')->where(array('userid' => $v['payout_id']))->Field('username,mobile')->find();
            $orders[$k]['cardnum'] = $bankinfos['card_number'];
            $orders[$k]['bname'] = M('bank_name')->where(array('q_id' => $bankinfos['card_id']))->getfield('banq_genre');
            $orders[$k]['openrds'] = $bankinfos['open_card'];
            $orders[$k]['uname'] = $bankinfos['hold_name'];
            $orders[$k]['umobile'] = $uinfomsg['mobile'];
        }

        $this->assign('page', $page);
        $this->assign('orders', $orders);
        $this->display();
    }

    /**
     * 展示图片
     */
    public function Paidimg()
    {
        $id = I('id');
        $imgInfo = M('trans')->where(array('id' => $id))->getField('trans_img');

        $this->assign('imginfo', $imgInfo);
        $this->display();
    }

    //已完成订单
    public function Dofinsh()
    {
        //查询我买入的
        $uid = session('userid');
        $traInfo = M('trans');
        $banks = M('ubanks');
        $where['payin_id'] = $uid;
        $where['pay_state'] = 3;
        //分页
        $p = getpage($traInfo, $where, 20);
        $page = $p->show();
        $orders = $traInfo->where($where)->order('id desc')->select();
        //收款人
        foreach ($orders as $k => $v) {
            //银行卡号.开户支行.开户银行
            $bankinfos = $banks->where(array('id' => $v['card_id']))->field('hold_name,card_number,card_id,open_card')->find();
            $uinfomsg = M('user')->where(array('userid' => $v['payout_id']))->Field('username,mobile')->find();
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

    /**
     * 买入记录
     */
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

    /**
     * 买入EP
     */
    public function Buycenter()
    {
        $userid = session('userid');
        $where['tr.pay_state'] = 0;
        $where['tr.trans_type'] = 0;
        $service_center = M('user')->where(['userid' => $userid])->getField('service_center');
        if (!$service_center) {
            $where['tr.pay_time'] = ['lt', time() - 3600];
        }

        $models = M('trans as tr')->join('LEFT JOIN  ysk_user as us on tr.payout_id = us.userid');
        $p = Fgetpage($models, $where);
        $page = $p->show();
        $order_info = $models->where($where)->order('id desc')->select();

        foreach ($order_info as $k => $v) {
            $order_info[$k]['pay_nums'] = intval($v['pay_nums']);
            $order_info[$k]['cardinfo'] = M('bank_name')->where(array('q_id' => $v['card_id']))->getfield('banq_genre');
            $order_info[$k]['spay'] = $v['pay_nums'] * 6.3;
        }

        $this->assign('page', $page);
        $this->assign('order_info', $order_info);
        $this->display();
    }

    /**
     * 买入
     */
    public function Dopurs()
    {
        if (IS_AJAX) {
            $uid = session('userid');
            $trid = I('trid', 1, 'intval');
            $sellnums = M('trans')->where(array('id' => $trid))->field('pay_nums,payout_id,pay_state')->find();

            if ($sellnums['payout_id'] == $uid) {
                ajaxReturn('您不能买入自己上架的哦~', 0);
            }
            if ($sellnums['pay_state'] != 0) {
                ajaxReturn('该订单存在异常,暂时无法购买哦~', 0);
            }
            //记录买入会员
            $res_Buy = M('trans')->where(array('id' => $trid))->setField(array('payin_id' => $uid, 'pay_state' => 1));
            if ($res_Buy) {
                $mobile = M('user')->where(['userid' => $uid])->getField('mobile');
                sendMessage($mobile, '109927');
                ajaxReturn('买入成功', 1);
            }
        }
        $this->display();
    }

    /**
     * 银行卡列表
     */
    public function Cardinfos()
    {
        $uid = session('userid');
        if (IS_AJAX) {
            //删除银行卡
            $card_id = I('bangid');
            $bank_user_id = M('ubanks')->where(array('id' => $card_id, 'delete_time' => ''))->getField('user_id');
            if (empty($bank_user_id)) {
                ajaxReturn('该张银行卡不存在~', 0);
            }
            if ($bank_user_id != $uid) {
                ajaxReturn('该张银行卡暂不属于您~', 0);
            }
            $res = M('ubanks')->where(array('id' => $card_id))->setField(['delete_time' => date('YmdHis')]);
            if ($res) {
                ajaxReturn('该银行卡删除成功', 1);
            }
        }
        $bank_list = M('ubanks as u')->where(array('u.user_id' => $uid, 'delete_time' => ''))
            ->join('RIGHT JOIN ysk_bank_name as banks ON u.card_id = banks.pid')
            ->field('u.hold_name,u.id,u.card_number,u.user_id,banks.banq_genre,banks.banq_img')
            ->order('u.id desc')
            ->select();
        $back_url = trim(I('back_url'));

        $url = 'javascript:void(0)';
        foreach ($bank_list as $k => $item) {
            if ($back_url) {
                if ($back_url == 'withdrawal') {
                    $url = U('Shop/Member/withdrawal', array('cid' => $item["id"]));
                } elseif ($back_url == 'withTurnover') {
                    $url = U('Shop/Member/withTurnover', array('cid' => $item["id"]));
                }
            }
            $bank_list[$k]['back_url'] = $url;
        }

        $this->assign('morecars', $bank_list);
        $this->assign('back_url', $back_url);
        $this->display();
    }

    /**
     * 总爱心基金池
     * @time 2018-7-22 01:07:25
     */
    public function SumPai()
    {
        $total_amount = M('charity_score_record')->sum('amount');
        $this->assign('total_amount', $total_amount);
        $this->display();
    }

    /**
     * 加速释放列表
     * @time 2018-7-22 01:25:08
     */
    public function SyList()
    {
        $userid = session('userid');      //当前用户ID

        $model = new TranmoneyModel();
        $type = ['0' => 26, '1' => 28];
        list($list, $total) = $model->getReleaseList($userid, $type);
        $this->assign('list', $list);
        $this->assign('total', $total);
        $this->display();
        $this->display();
    }

    /**
     * 基础释放列表
     * @time 2018-7-22 01:25:08
     */
    public function BaseRelease()
    {
        $userid = session('userid');      //当前用户ID
        $model = new TranmoneyModel();
        $type = ['0' => 2];
        list($list, $total) = $model->getReleaseList($userid, $type);
        $this->assign('list', $list);
        $this->assign('total', $total);
        $this->display();
    }

    //求购列表
    public function userBuy()
    {
        $uid = session('userid');

        //生成买入订单
        if (IS_AJAX) {
            $pwd = trim(I('pwd'));
            $buynum = trim(I('buynum'));//求购数量
            $sellAll = array(500, 1000, 3000, 5000, 10000, 30000);
            if (!in_array($buynum, $sellAll)) {
                ajaxReturn('请选择求购数量', 0);
            }

            //验证交易密码
            $minepwd = M('user')->where(array('userid' => $uid))->Field('account,mobile,safety_pwd,safety_salt')->find();
            $user_object = D('Home/User');
            $user_object->Trans($minepwd['account'], $pwd);

            //生成订单
            $data['uid'] = $uid;
            $data['num'] = $buynum;
            $data['status'] = 0;
            $data['create_time'] = date('YmdHis');
            $res_Add = M('buy_list')->add($data);
            if ($res_Add) {
                ajaxReturn('提交成功，等待匹配', 1);
            }
        }

        $userInfo = M('user')->where(['userid' => $uid])->field('quanxian')->find();
        $quanxian = explode("-", $userInfo['quanxian']);

        //EP
        $store = M('store')->where(['uid' => $uid])->field('cangku_num')->find();

        //求购列表
        $where['status'] = 0;
        $traInfo = M('buy_list');
        $orders = $traInfo->where($where)->order('id desc')->select();

        $this->assign('orders', $orders);
        $this->assign('quanxian', $quanxian);
        $this->assign('store', $store);
        $this->display();
    }

    /**
     * 支付宝收款二维码
     * @time 2020/2/25 15:28
     */
    public function alipay()
    {
        $path = $_SERVER['DOCUMENT_ROOT'];
        $uid = session('userid');
        if (file_exists($path . '/Uploads/alipay/' . $uid . '.png')) {
            $this->assign('status', 1);
        }
        $this->assign('type', 1);
        $this->assign('name', '支付宝收款码');
        $this->assign('uid', $uid);
        $this->display('qrcord');
    }

    /**
     * 微信收款二维码
     * @time 2020/2/25 15:29
     */
    public function weChat()
    {
        $path = $_SERVER['DOCUMENT_ROOT'];
        $uid = session('userid');
        if (file_exists($path . '/Uploads/wechat/' . $uid . '.png')) {
            $this->assign('status', 1);
        }
        $this->assign('name', '微信收款码');
        $this->assign('type', 2);
        $this->assign('uid', $uid);
        $this->display('qrcord');
    }

    /**
     * 上传 微信，支付宝二维码
     * @time 2020/2/28 13:55
     */
    public function subQrCord()
    {
        $type = I('post.type');
        $save_name = session('userid');
        if ($type == 1) {
            $root_path = 'alipay';
        } else {
            $root_path = 'wechat';
        }
        $data = uploading($root_path, $save_name);
        if (!$data['status']) {
            ajaxReturn($data['msg'], 0);
        } else {
            ajaxReturn('上传成功', 1);
        }
    }
}