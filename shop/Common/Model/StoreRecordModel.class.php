<?php

namespace Common\Model;

use Think\Exception;

class StoreRecordModel extends ModelModel
{
    protected $tableName = 'store_record';

    /**
     * 修改钱包金额，并添加记录
     * @param $uid
     * @param $fields
     * @param double $num 数量，正数为增加，负数为减少
     * @param $store_type
     * @param int $type 类型
     * @param $remark
     * @param $release_amount
     * @param $is_release
     * @throws Exception
     * @author ldz
     * @time 2020/2/25 15:57
     */
    public static function addRecord($uid, $fields, $num, $store_type, $type, $remark = '', $release_amount = 0, $is_release = 1)
    {
        if ($num != 0) {
            $res = M('store')->where(['uid' => $uid])->setInc($fields, $num);
            if (!$res) {
                throw new Exception('修改金额失败');
            }

            //添加记录
            $storeRecord = [
                'uid' => $uid,
                'amount' => $num,
                'release_amount' => $release_amount,
                'store_type' => $store_type,
                'type' => $type,
                'is_release' => $is_release,
                'remark' => $remark,
                'create_time' => time(),
            ];

            $res = M('store_record')->add($storeRecord);
            if (!$res) {
                throw new Exception('新增记录失败');
            }
        }
    }

}
