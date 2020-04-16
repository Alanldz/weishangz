<?php

namespace Shop\Model;

use Think\Model;

/**
 * 用户等级
 */
class GrandModel extends Model
{
    protected $tableName = 'grand';

    /**
     * 最大业绩，和最小业绩，获得相应等级
     *
     * @param $regional_performance
     * @param $cell_performance
     * @return int
     */
    public static function getUserLevel($regional_performance, $cell_performance)
    {
        $unit = 10000;//最大业绩，最小业绩单位
        $grandList = M('grand')->select();
        $grand = 0;
        foreach ($grandList as $item) {
            if ($regional_performance >= ($item['regional_performance'] * $unit) && $cell_performance >= ($item['cell_performance'] * $unit)) {
                if ($item['id'] > $grand) {
                    $grand = $item['id'];
                }
            }
        }
        return $grand;
    }

}
