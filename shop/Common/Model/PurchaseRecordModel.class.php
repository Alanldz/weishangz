<?php

namespace Common\Model;

use Think\Exception;

class PurchaseRecordModel extends ModelModel
{
    protected $tableName = 'purchase_record';

    /**
     * 修改钱包金额，并添加记录
     * @param $uid
     * @param double $num 数量，正数为增加，负数为减少
     * @param $type
     * @param $remark
     * @throws Exception
     * @author ldz
     * @time 2020/2/25 15:57
     */
    public static function addRecord($uid, $num, $type, $remark = '')
    {
        if ($num != 0) {
            $res = M('store')->where(['uid' => $uid])->setInc('purchase_integral', $num);
            if (!$res) {
                throw new Exception('修改签到账户失败');
            }

            $purchase_record = [
                'uid' => $uid,
                'amount' => $num,
                'type' => $type,
                'remark' => $remark,
                'create_time' => date('YmdHis')
            ];
            $res = M('purchase_record')->add($purchase_record);
            if (!$res) {
                throw new Exception('添加签到账户记录失败');
            }
        }
    }

}
