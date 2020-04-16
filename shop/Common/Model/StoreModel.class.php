<?php

namespace Common\Model;

use Think\Exception;

class StoreModel extends ModelModel
{
    protected $tableName = 'store';

    /**
     * 修改钱包中的生态总资产，消费通证
     * @param $uid
     * @param $fields
     * @param $num
     * @param $get_type
     * @param int $is_release
     * @param string $remark
     * @throws Exception
     */
    public static function changStore($uid, $fields, $num, $get_type, $is_release = 1, $remark = '')
    {
        if ($num != 0) {
            $res = M('store')->where(['uid' => $uid])->setInc($fields, $num);
            if (!$res) {
                throw new Exception('修改金额失败');
            }
            $now_nums = M('store')->where(['uid' => $uid])->getField($fields);
            //添加金额记录
            $tranMoney = [
                'pay_id' => $uid,
                'get_id' => $uid,
                'get_nums' => $num,
                'get_type' => $get_type,
                'now_nums' => $now_nums,
                'now_nums_get' => $now_nums + $num,
                'is_release' => $is_release,
                'remark' => $remark,
                'get_time' => time()
            ];

            $res = M('tranmoney')->add($tranMoney);
            if (!$res) {
                throw new Exception('添加记录失败');
            }
        }
    }
}
