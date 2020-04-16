<?php

namespace Admin\Controller;

use Common\Model\DealsRecordModel;
use Common\Model\UcoinsModel;
use Home\Model\DealModel;

class TurntableController extends AdminController
{
    /**
     * 求购列表
     * @author Alan
     * @time 2018-10-11 20:23:29
     */
    public function buy_list()
    {
        // 搜索
        $keyword = I('keyword', '', 'string');
        $querytype = I('querytype', 'userid', 'string');
        $map['type'] = 2;
        if ($keyword) {
            $map[$querytype] = array("LIKE", '%' . $keyword . '%');
        }
        //按日期搜索
        $date = date_query('create_time');
        if ($date) {
            $where = $date;
            if (isset($map))
                $map = array_merge($map, $where);
            else
                $map = $where;
        }

        $deal = M('deal');
        //分页
        $p = getpage($deal, $map, 10);
        $page = $p->show();
        $deal_list = $deal->where($map)->order('id desc')->select();

        //币种类型
        $type_list = UcoinsModel::$TYPE_TABLE;

        $this->assign('list', $deal_list);
        $this->assign('querytype', $querytype);
        $this->assign('table_data_page', $page);
        $this->assign('type_list', $type_list);
        $this->display();
    }

    /**
     * 出售列表
     * @author Alan
     * @time 2018-10-11 20:23:29
     */
    public function sell_list()
    {
        // 搜索
        $keyword = I('keyword', '', 'string');
        $querytype = I('querytype', 'userid', 'string');
        $map['type'] = 1;
        if ($keyword) {
            $map[$querytype] = array("LIKE", '%' . $keyword . '%');
        }
        //按日期搜索
        $date = date_query('create_time');
        if ($date) {
            $where = $date;
            if (isset($map))
                $map = array_merge($map, $where);
            else
                $map = $where;
        }

        $deal = M('deal');
        //分页
        $p = getpage($deal, $map, 10);
        $page = $p->show();
        $deal_list = $deal->where($map)->order('id desc')->select();

        //币种类型
        $type_list = UcoinsModel::$TYPE_TABLE;

        $this->assign('list', $deal_list);
        $this->assign('querytype', $querytype);
        $this->assign('table_data_page', $page);
        $this->assign('type_list', $type_list);
        $this->display();
    }

    /**
     * 取消交易
     * @author Alan
     * @time 2018-10-13 14:08:44
     */
    public function cancel_deal()
    {
        $id = (int)I('id', 'intval', 0);
        $uid = session('userid');
        $mydeal = M('deal')->where(array("id" => $id))->find();
        if (!$mydeal) {
            $this->error('订单不存在');
        }
        if ($mydeal['status'] != 0) {
            $this->error('订单只有在求购中才能取消');
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
        //把此订单状态设置为2，即为取消
        $payout2['status'] = 2;
        $res3 = M('deal')->where(array("id" => $id))->save($payout2);

        if ($res3) {
            $this->success('取消成功');
        } else {
            $this->success('操作失败,请重试');
        }
    }

    /**
     * 求购列表
     * @author Alan
     * @time 2018-10-11 20:23:29
     */
    public function deals_list()
    {
        // 搜索
        $keyword = trim(I('keyword', '', 'string'));
        $querytype = trim(I('querytype', 'userid', 'string'));
        $type = intval(I('type', ''));
        $map = [];
        if ($keyword) {
            $map['ds.' . $querytype] = array("LIKE", '%' . $keyword . '%');
        }

        if ($type) {
            $map['ds.type'] = $type;
        }

        //按日期搜索
        $date = date_query('ds.create_time');
        if ($date) {
            $where = $date;
            if (isset($map))
                $map = array_merge($map, $where);
            else
                $map = $where;
        }
        $banks = M('ubanks ub');
        $deal = M('deals ds')->join('ysk_deal d on d.id = ds.d_id');
        //分页
        $p = getpage($deal, $map, 10);
        $page = $p->show();
        $deal_list = $deal->where($map)->field('ds.*,d.card_id,d.pay_way')->order('ds.id desc')->select();
        foreach ($deal_list as $k => $v) {
            //银行卡号.开户支行.开户银行
            $bankinfos = $banks->where(array('id' => $v['card_id']))->field('hold_name,card_number,card_id,open_card')->find();
            $uinfomsg = M('user')->where(array('userid' => $v['sell_id']))->Field('username,mobile')->find();
            $deal_list[$k]['cardnum'] = $bankinfos['card_number'];
            $deal_list[$k]['bname'] = M('bank_name')->where(array('q_id' => $bankinfos['card_id']))->getfield('banq_genre');
            $deal_list[$k]['openrds'] = $bankinfos['open_card'];
            $deal_list[$k]['uname'] = $bankinfos['hold_name'];
            $deal_list[$k]['umobile'] = $uinfomsg['mobile'];
            $deal_list[$k]['img_upload_time'] = date('Y-m-d H:i:s', $v['img_upload_time']);
        }

        $this->assign('list', $deal_list);
        $this->assign('querytype', $querytype);
        $this->assign('type', $type);
        $this->assign('table_data_page', $page);
        $this->display();
    }

    /**
     * 每日交易明细
     * @author ldz
     * @time 2020/1/18 16:23
     */
    public function deal_record()
    {
        $todayTime = strtotime(date('Y-m-d')); // 今日时间搓
        if (empty(DealsRecordModel::getTodayInfo())) {
            M('deals_record')->add(['create_time' => $todayTime]);
        }
        $model = M('deals_record');
        //分页
        $p = getpage($model, [], 10);
        $page = $p->show();

        $data_list = $model->order('create_time desc')->select();
        $this->assign('list', $data_list);
        $this->assign('table_data_page', $page);
        $this->assign('todayTime', $todayTime);
        $this->display();
    }

    /**
     * 编辑用户
     */
    public function deal_record_edit($id){
        if (IS_POST) {
            $versionModel = new DealsRecordModel();
            $res = $versionModel->updateData();
            if(!$res){
                $this->error($versionModel->getError());
            }
            $this->success('修改成功', U('Turntable/deal_record'));
        }

        // 获取账号信息
        $info = M('deals_record')->find($id);
        $info['create_time'] = date('Y-m-d',$info['create_time']);
        $this->assign('info',$info);
        $this->display();
    }


    /**
     * 求购列表
     * @author Alan
     * @time 2018-10-11 20:23:29
     */
    public function unusual_deals_list()
    {
        // 搜索
        $keyword = trim(I('keyword', '', 'string'));
        $querytype = trim(I('querytype', 'userid', 'string'));
        $type = intval(I('type', ''));
        $map = [];
        if ($keyword) {
            $map['ds.' . $querytype] = array("LIKE", '%' . $keyword . '%');
        }

        if ($type) {
            $map['ds.type'] = $type;
        }

        //按日期搜索
        $date = date_query('ds.create_time');
        if ($date) {
            $where = $date;
            if (isset($map))
                $map = array_merge($map, $where);
            else
                $map = $where;
        }
        $map['ds.is_punish'] = ['neq',1];
        $map['ds.img_upload_time'] = ['neq',''];
        $map['ds.status'] = ['in',[2,4]];
        $time = time() - 30 * 60;
        $map['img_upload_time'] = ['lt',$time];
        $banks = M('ubanks ub');
        $deal = M('deals ds')->join('ysk_deal d on d.id = ds.d_id');
        //分页
        $p = getpage($deal, $map, 10);
        $page = $p->show();
        $deal_list = $deal->where($map)->field('ds.*,d.card_id')->order('ds.id desc')->select();
        foreach ($deal_list as $k => $v) {
            //银行卡号.开户支行.开户银行
            $bankinfos = $banks->where(array('id' => $v['card_id']))->field('hold_name,card_number,card_id,open_card')->find();
            $uinfomsg = M('user')->where(array('userid' => $v['sell_id']))->Field('username,mobile')->find();
            $deal_list[$k]['cardnum'] = $bankinfos['card_number'];
            $deal_list[$k]['bname'] = M('bank_name')->where(array('q_id' => $bankinfos['card_id']))->getfield('banq_genre');
            $deal_list[$k]['openrds'] = $bankinfos['open_card'];
            $deal_list[$k]['uname'] = $bankinfos['hold_name'];
            $deal_list[$k]['umobile'] = $uinfomsg['mobile'];
            $deal_list[$k]['img_upload_time'] = date('Y-m-d H:i:s', $v['img_upload_time']);
        }

        $this->assign('list', $deal_list);
        $this->assign('querytype', $querytype);
        $this->assign('type', $type);
        $this->assign('table_data_page', $page);
        $this->display();
    }

    /**
     * 取消订单
     * @author ldz
     * @time 2020/2/4 16:51
     */
    public function cancelDealsOrder()
    {
        $id = intval(I('id'));
        $deals = M('deals')->where(['id'=>$id])->find();
        if(!$deals){
            $this->error('订单不存在，请重新选择');
        }
        M()->startTrans();
        //取消订单
        $res = M('deals')->where(['id' => $id])->save(['status' => 4]);
        if ($res === false) {
            M()->rollback();
            $this->error('修改订单状态失败');
        }

        $dealInfo = M('deal')->where(['id' => $deals['d_id']])->find();
        $total_num = $dealInfo['num'] + $deals['num'];
        if ($total_num == $dealInfo['total_num']) {
            $deal_change_data['status'] = 0;
        } else {
            $deal_change_data['status'] = 1;
        }
        $deal_change_data['num'] = $total_num;

        $res = M('deal')->where(['id' => $deals['d_id']])->save($deal_change_data);
        if ($res === false) {
            M()->rollback();
            $this->error('取消订单失败');
        }

        M()->commit();
        $this->success('取消成功');
    }

    /**
     * 确认收款
     * @author ldz
     * @time 2020/2/4 16:56
     */
    public function confirmSk()
    {
        if (IS_AJAX) {
            $uid = intval(I('user_id'));
            $deals_id = intval(I('deals_id', 0));
            $dealModel = new DealModel();
            $res = $dealModel->confirmSk($uid,$deals_id);
            if(!$res){
                $this->error($dealModel->getError());
            }
            $this->success('确认收款成功');
        }else{
            $this->error('确认收款失败');
        }
    }
}