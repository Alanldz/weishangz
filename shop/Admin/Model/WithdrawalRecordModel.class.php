<?php

namespace Admin\Model;

use Common\Model\ModelModel;
use Think\Exception;

/**
 * 提现模型
 */
class WithdrawalRecordModel extends ModelModel
{
    protected $tableName = 'withdrawal_record';

    /**
     * 删除
     * @return bool
     */
    public function del()
    {
        $id = intval(trim(I('id')));
        if (!$id) {
            $this->error = '参数不正确';
            return false;
        }
        $withdrawal_record = M('withdrawal_record')->where(['id' => $id, 'delete_time' => ''])->field('uid,status,amount,poundage')->find();
        if (empty($withdrawal_record)) {
            $this->error = '该提现记录不存在';
            return false;
        }
        if ($withdrawal_record['status'] != 0) {
            $this->error = '该提现记录已发放,不可删除';
            return false;
        }
        try {
            M()->startTrans();

            $res = M('withdrawal_record')->where(['id' => $id])->save(['delete_time' => date('YmdHis', time())]);
            if (!$res) {
                throw new Exception('删除失败');
            }
            //给用户加回生态总资产
            StoreModel::changStore($withdrawal_record['uid'], 'cangku_num', $withdrawal_record['amount'], 20);

            M()->commit();
            return true;
        } catch (Exception $ex) {
            M()->rollback();
            $this->error = $ex->getMessage();
            return false;
        }
    }


}
