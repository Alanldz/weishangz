<?php
/**
 * Created by PhpStorm.
 * User: ldz
 * Date: 2020/2/23
 * Time: 15:11
 */

namespace Shop\Model;

use Common\Model\ConfigModel;
use Common\Util\Constants;

class LockWarehousePassModel extends \Common\Model\LockWarehousePassModel
{
    /**
     * 是否需要复购，true 需要， false 不需要
     * @param $user_id
     * @return bool
     * @author ldz
     * @time 2020/2/26 15:54
     */
    public static function isNeedAgainBuy($user_id)
    {
        $where['is_release'] = 1;
        $where['flow_amount'] = 0;
        $where['is_again_bug'] = 0;
        $where['uid'] = $user_id;
        $num = M('lock_warehouse_pass')->where($where)->count();
        if ($num) {
            return true;
        }
        return false;
    }

    /**
     * 选择天数
     * @return bool
     * @author ldz
     * @time 2020/2/23 16:06
     */
    public function selectDay()
    {
        $user_id = session('userid');
        $lock_warehouse_id = intval(I('lock_warehouse_id'));
        $day = intval(I('day'));

        $lock_warehouse = $this->where(['id' => $lock_warehouse_id, 'uid' => $user_id])->find();
        if (empty($lock_warehouse)) {
            $this->error = '您选择的锁仓通证不存在，请重新操作';
            return false;
        }
        if ($lock_warehouse['status'] != Constants::LOCK_WAREHOUSE_NOT_SELECT) {
            $this->error = '该锁仓通证已选择时间，请刷新重试';
            return false;
        }

        $multipleList = ConfigModel::getLockWareHouseInfo();
        if (!array_key_exists($day, $multipleList)) {
            $this->error = '您选择的天数不存在，请重新选择';
            return false;
        }
        if ($day != 1) {
            $starTime = strtotime(date('Y-m-d'));
            $endTime = strtotime(date("Y-m-d", strtotime("+1 day")));
            $today_where['start_time'] = array(array('egt', $starTime), array('lt', $endTime), 'and');
            $today_where['day'] = $day;
            $today_where['uid'] = $user_id;
            $today_select_num = $this->where($today_where)->count();
            if ($today_select_num) {
                $this->error = '该天数今天已经选过了，请明天在选';
                return false;
            }
        }
        $multiple = $multipleList[$day];
        $data['growth_amount'] = $multiple * $lock_warehouse['amount'];
        $data['release_amount'] = $lock_warehouse['release_amount'] + $data['growth_amount'];
        $data['multiple'] = $multiple;
        $data['day'] = $day;
        $data['status'] = Constants::LOCK_WAREHOUSE_GROWTH;
        $data['start_time'] = time();
        $res = $this->where(['id' => $lock_warehouse_id])->save($data);

        if (!$res) {
            $this->error = '选择失败，请重试';
            return false;
        }
        return true;
    }


}