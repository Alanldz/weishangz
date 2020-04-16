<?php
/**
 * Created by PhpStorm.
 * User: Alan
 * Date: 2019/4/6
 * Time: 15:35
 */

namespace Admin\Controller;

use Admin\Model\WithdrawalRecordModel;
use Common\Util\Constants;

class WithdrawalRecordController extends AdminController
{
    /**
     * 提现列表
     */
    public function index()
    {
        $map['wr.delete_time'] = '';
        $table = M('withdrawal_record')->alias('wr')->join('ysk_user us on us.userid=wr.uid', 'left')
            ->join('ysk_ubanks ub on ub.id=wr.bank_id', 'left')
            ->join('ysk_bank_name bn on bn.q_id = ub.card_id', 'left');

        //按日期搜索
        $date = date_search('wr.create_time');
        if ($date) {
            $where = $date;
            if (isset($map))
                $map = array_merge($map, $where);
            else
                $map = $where;
        }

        $status = intval(I('status', -1));

        if ($status != -1) {
            $map['wr.status'] = $status;
        }

        $keyword = trim(I('keyword'));
        if ($keyword) {
            $map['wr.uid'] = $keyword;
        }

        //分页
        $p = getpage($table, $map, 10);
        $page = $p->show();
        $model = clone $table;
        $other_data['total_amount'] = $model->where($map)->sum('wr.amount');
        $other_data['current_amount'] = 0;

        $data_list = $table
            ->where($map)
            ->order('wr.id desc')
            ->field('wr.id,us.account,us.userid,wr.amount,wr.type,wr.poundage,wr.status,wr.out_of_time,wr.create_time,ub.card_number,ub.hold_name,bn.banq_genre')
            ->select();

        foreach ($data_list as $k => $item) {
            $data_list[$k]['type_name'] = Constants::getWithdrawalTypeItems($item['type']);
            $other_data['current_amount'] += $item['amount'];
        }

        $this->assign('list', $data_list);
        $this->assign('status', $status);
        $this->assign('table_data_page', $page);
        $this->assign('other_data', $other_data);
        $this->display();
    }

    /**
     * 发放
     */
    public function sendWithdrawal()
    {
        $id = intval(trim(I('id')));
        if (empty($id)) {
            $this->error('参数有误');
        }
        $withdrawal_record = M('withdrawal_record')->where(['id' => $id, 'delete_time' => ''])->field('status')->find();
        if (empty($withdrawal_record)) {
            $this->error('该提现记录不存在');
        }
        if ($withdrawal_record['status'] != 0) {
            $this->error('该提现记录已发放');
        }
        $saveData['status'] = 1;
        $saveData['out_of_time'] = date('YmdHis', time());
        try {
            M()->startTrans();
            $res = M('withdrawal_record')->where(['id' => $id])->save($saveData);
            if (!$res) {
                throw new \Exception('发放失败');
            }
            M()->commit();
            $this->success('发放成功');
        } catch (\Exception $ex) {
            M()->rollback();
            $this->error($ex->getMessage());
        }
    }

    /**
     * 删除
     */
    public function del()
    {
        $model = new WithdrawalRecordModel();
        $res = $model->del();
        if (!$res) {
            $this->error($model->getError());
        }
        $this->success('删除成功');
    }

}