<?php
/**
 * Created by PhpStorm.
 * Date: 2018/11/1
 * Time: 21:06
 */

namespace Common\Model;

class ConfigModel extends ModelModel
{
    /**
     * 数据库表名
     */
    protected $tableName = 'config';

    /**
     * 获取锁仓通证天数对应倍数列表
     * @return array
     * @author ldz
     * @time 2020/2/23 14:51
     */
    public static function getLockWareHouseInfo()
    {
        $arrFiles = ['lock_warehouse_day_one', 'lock_warehouse_day_two', 'lock_warehouse_day_three', 'lock_warehouse_day_four',
            'lock_warehouse_multiple_one', 'lock_warehouse_multiple_two', 'lock_warehouse_multiple_three', 'lock_warehouse_multiple_four'];
        $data = [];
        foreach ($arrFiles as $file) {
            $data[$file] = D('config')->where(['name' => $file])->getField("value");
        }

        $list = [
            $data['lock_warehouse_day_one'] => $data['lock_warehouse_multiple_one'],
            $data['lock_warehouse_day_two'] => $data['lock_warehouse_multiple_two'],
            $data['lock_warehouse_day_three'] => $data['lock_warehouse_multiple_three'],
            $data['lock_warehouse_day_four'] => $data['lock_warehouse_multiple_four'],
        ];

        return $list;
    }


}