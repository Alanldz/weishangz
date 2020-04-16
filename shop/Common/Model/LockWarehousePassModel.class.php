<?php
/**
 * Created by PhpStorm.
 * User: ldz
 * Date: 2020/2/23
 * Time: 15:11
 */

namespace Common\Model;

use Common\Util\Constants;
use Think\Exception;

class LockWarehousePassModel extends ModelModel
{
    /**
     * 数据库表名
     */
    protected $tableName = 'lock_warehouse_pass';

    /**
     * 新增未完全释放的锁仓中新增释放值，平均分
     * @param $user_id
     * @param $amount
     * @throws Exception
     * @author ldz
     * @time 2020/4/7 15:04
     */
    public static function addReleaseAccount($user_id, $amount)
    {
        $where['uid'] = $user_id;
        $where['is_release'] = Constants::YesNo_No;
        $arrID = M('lock_warehouse_pass')->where($where)->getField('id', true);
        $countID = count($arrID);
        if ($countID > 0) {
            $release_amount = formatNum2($amount / $countID);
            $last_release_amount = 0;
            if ($release_amount * $countID != $amount) {
                $last_release_amount = $amount - $release_amount * ($countID - 1);
            }

            foreach ($arrID as $key => $lock_id) {
                if ($key == ($countID - 1) && $last_release_amount > 0) {
                    $res = M('lock_warehouse_pass')->where(['id' => $lock_id])->setInc('release_amount', $last_release_amount);
                } else {
                    $res = M('lock_warehouse_pass')->where(['id' => $lock_id])->setInc('release_amount', $release_amount);
                }
                if ($res === false) {
                    throw new Exception('增加释放总数失败');
                }
            }
        }
    }

}