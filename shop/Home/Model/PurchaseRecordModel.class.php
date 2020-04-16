<?php

namespace Home\Model;


/**
 * 用户模型
 */
class PurchaseRecordModel extends \Common\Model\PurchaseRecordModel
{

    /**
     * 新增记录
     * @param $uid
     * @param $amount
     * @param $type
     * @return mixed
     */
    public function addData($uid, $amount, $type)
    {
        if ($amount != 0) {
            $purchase_record = [
                'uid' => $uid,
                'amount' => $amount,
                'type' => $type,
                'create_time' => date('YmdHis')
            ];
            return M('purchase_record')->add($purchase_record);
        }
        return true;
    }

    /**
     * 判断是否超过3天没签到
     * @param $uid
     * @return bool
     * @author ldz
     * @time 2020/2/12 15:30
     */
    public static function is_over_three($uid)
    {
        $time = time();
        $three_day_time = $time - 60 * 60 * 24 * 3;
        $dateTreeTime = date('Ymd', $three_day_time) . '000000';

        $where['uid'] = $uid;
        $where['type'] = 1;
        $record = M('purchase_record')->where($where)->order('create_time desc')->find();
        if ($record['create_time'] < $dateTreeTime) {
            return true;
        } else {
            return false;
        }
    }


}
