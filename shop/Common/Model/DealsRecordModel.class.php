<?php
/**
 * Created by PhpStorm.
 * User: ldz
 * Date: 2020/1/18
 * Time: 14:17
 */

namespace Common\Model;

class DealsRecordModel extends ModelModel
{

    /**
     * 数据库表名
     */
    protected $tableName = 'deals_record';

    /**
     * 获取今日交易信息
     * @return mixed
     * @author ldz
     * @time 2020/1/18 14:23
     */
    public static function getTodayInfo()
    {
        $today_where['create_time'] = strtotime(date('Y-m-d'));
        $info = M('deals_record')->where($today_where)->find();
        return $info;
    }

    /**
     * 修改明细
     * @return bool
     * @author ldz
     * @time 2020/1/18 16:41
     */
    public function updateData()
    {
        $model = M('deals_record');
        $data = I('post.');

        if ($data['deal_num'] < 0) {
            $this->error = '成交单数不能小于0';
            return false;
        }

        if ($data['sell_num'] < 0) {
            $this->error = '卖出单数不能小于0';
            return false;
        }
        if ($data['buy_num'] < 0) {
            $this->error = '买入单数不能小于0';
            return false;
        }

        $result = $model->save($data);
        if (!$result) {
            $this->error = '更新失败';
            return false;
        }
        return true;
    }

}