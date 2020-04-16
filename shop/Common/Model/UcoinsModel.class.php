<?php
/**
 * Created by PhpStorm.
 * Date: 2018/11/1
 * Time: 21:06
 */

namespace Common\Model;

use Think\Exception;

class UcoinsModel extends ModelModel
{
    /**
     * 数据库表名
     */
    protected $tableName = 'ucoins';

    //类型
    static $TYPE_TABLE = [
        1 => '生态通证',
    ];

    /**
     * 按cid获取当前价格
     * @param $cid
     * @return mixed
     * @author ldz
     * @time 2020/1/13 16:07
     */
    public static function getCurrentPriceByCid($cid)
    {
        return M('coindets')->order('coin_addtime desc,cid asc')->where(array("cid" => $cid))->find();
    }

    /**
     * 获取生态通证总数
     * @param int $cid
     * @return mixed
     * @author ldz
     * @time 2020/1/15 15:32
     */
    public static function getAllUserAmountByCid($cid = 1)
    {
        return M('ucoins')->where(['cid' => $cid])->sum('c_nums');
    }

    /**
     * 获取生态通证价格
     * @param int $cid
     * @return mixed
     * @author ldz
     * @time 2020/2/25 15:13
     */
    public static function getCoinPrice($cid = 1)
    {
        $coin_price = M('coindets')->where(['cid' => $cid])->order('coin_addtime desc')->getField('coin_price');
        return $coin_price;
    }

    /**
     * 获取数量
     * @param $user_id
     * @param int $cid
     * @return mixed
     * @author ldz
     * @time 2020/2/28 10:46
     */
    public static function getAmount($user_id, $cid = 1)
    {
        $amount = M('ucoins')->where(['c_uid' => $user_id, 'cid' => $cid])->getField('c_nums');
        return $amount;
    }

    /**
     * 修改数量，且添加记录
     * @param $uid
     * @param $num
     * @param $type
     * @param int $coin_price
     * @param int $cid
     * @throws Exception
     */
    public static function addRecord($uid, $num, $type, $coin_price = 0, $cid = 1)
    {
        if ($num != 0) {
            $res = M('ucoins')->where(array('c_uid' => $uid, 'cid' => $cid))->setInc('c_nums', $num);
            $cid_name = UcoinsModel::$TYPE_TABLE[$cid];
            if (!$res) {
                throw new Exception('修改' . $cid_name . '数量失败');
            }
            //添加记录
            $record = [
                'uid' => $uid,
                'cid' => $cid,
                'amount' => $num,
                'type' => $type,
                'price' => $coin_price,
                'create_time' => date('YmdHis')
            ];
            $res = M('ucoins_record')->add($record);
            if (!$res) {
                throw new Exception('生态通证记录添加失败');
            }
        }
    }

}