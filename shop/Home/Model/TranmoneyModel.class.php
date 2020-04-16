<?php
/**
 * Created by PhpStorm.
 * Date: 2018/7/25
 * Time: 22:15
 */

namespace Home\Model;

use Common\Model\ModelModel;

class TranmoneyModel extends ModelModel
{

    /**
     * 获取释放列表
     * @param $userID //当前用户ID
     * @return mixed
     * @time 2018-7-25 22:30:42
     */
    public function getReleaseList($userID, $type)
    {
        $list = M('tranmoney')->field('get_id,get_nums,now_nums_get,get_time,get_type')
            ->where(['get_id' => $userID, 'get_type' => ['in', $type], 'is_release' => 1])
            ->order('get_time desc')
            ->select();

        $total = 0;  //合计
        foreach ($list as $k => $item) {
            $list[$k]['last_num'] = $item['now_nums_get'] - $item['get_nums'];
            $list[$k]['get_time'] = date('m-d', $item['get_time']);
            $total += $item['get_nums'];
        }
        return [$list, $total];
    }

}