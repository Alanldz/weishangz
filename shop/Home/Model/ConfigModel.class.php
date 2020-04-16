<?php

namespace Home\Model;

/**
 * 用户模型
 *
 */
class ConfigModel extends \Common\Model\ConfigModel
{

    public function getValue($field)
    {
        $where['name'] = $field;
        return $this->where($where)->getField('value');
    }

    public function getById($id)
    {
        $where['id'] = $id;
        return $this->where($where)->field('value,tip')->find();
    }

    //获取拆分概率
    public function getVl()
    {

        $config = D('Config')->field('name,value')->where(array('type' => 5))->select();
        //转一维数组
        foreach ($config as $key => $val) {
            $data[$val['name']] = $val['value'];
        }
        return $data;
    }

    public function getMaxLand($type)
    {
        switch ($type) {
            case 1:
                $where['id'] = 9;
                break;
            case 2:
                $where['id'] = 11;
                break;
            case 3:
                $where['id'] = 13;
                break;
            case 4:
                $where['id'] = 37;
                break;
            default:
                return false;
                break;
        }
        return $this->where($where)->getField('value');

    }

    /**
     * 用户是否在sp指导范围内
     *
     * @param int $userid 用户ID
     * @return bool
     */
    public static function in_scope_guidance($userid)
    {
        $minUID = D('Config')->where("name='minUID'")->getField('value');
        $maxUID = D('Config')->where("name='maxUID'")->getField('value');
        if ($userid < $minUID || $userid > $maxUID) {
            return false;
        }
        return true;
    }

    /**
     * 用户是否在指导销售范围内
     *
     * @param int $userid 用户ID
     * @param int $num 出售股数
     * @return bool
     */
    public static function is_sales_scope($userid, $num)
    {
        $userLevel = M('user')->where(['userid' => $userid])->getField('level');
        $configModel = D('Config');
        switch ($userLevel) {
            case 1:
                $salesNum = $configModel->where(['name' => 'sp_one_start'])->getField('value');
                break;
            case 2:
                $salesNum = $configModel->where(['name' => 'sp_two_start'])->getField('value');
                break;
            case 3:
                $salesNum = $configModel->where(['name' => 'sp_three_start'])->getField('value');
                break;
            case 4:
                $salesNum = $configModel->where(['name' => 'sp_four_start'])->getField('value');
                break;
            default :
                return false;
        }
        $c_nums = M('ucoins')->where(array('c_uid' => $userid, 'cid' => 1))->getField('c_nums');
        if (($c_nums - $num) < $salesNum) {
            return false;
        }

        return true;
    }

    /**
     * 判断卖家今日是否已经卖出大于今日可卖额度
     * @param $user_id
     * @param int $cid
     * @return array
     * @author ldz
     * @time 2020/2/2 20:53
     */
    public static function userIsCanSellQbb($user_id, $sell_num, $cid = 1)
    {
        $surplus_num = ConfigModel::getUserCanSellQbbNum($user_id, $cid);
        if ($surplus_num > 0) {
            if ($surplus_num >= $sell_num) {
                return [1, ''];
            } else {
                return [0, '今日您最多可售' . $surplus_num . 'QBB'];
            }

        } else {
            return [0, '今日已达卖出额度，请明天再卖'];
        }
    }

    /**
     * 获取用户剩余可卖出QBB数量
     * @param $user_id
     * @param int $cid
     * @return mixed
     * @author ldz
     * @time 2020/2/3 16:51
     */
    public static function getUserCanSellQbbNum($user_id, $cid = 1)
    {
        $qbb_sell_acount = D('config')->getValue('qbb_sell_acount');

        $starTime = strtotime(date('Y-m-d'));
        $endTime = strtotime(date("Y-m-d", strtotime("+1 day")));
        $today_where['create_time'] = array(array('egt', $starTime), array('lt', $endTime), 'and');
        $today_where['sell_id'] = $user_id;
        $today_where['cid'] = $cid;
        $user_today_sell_qbb_num = M('deal')->where($today_where)->sum('real_qbb_num');

        $surplus_num = $qbb_sell_acount - $user_today_sell_qbb_num;
        return $surplus_num;
    }

    /**
     * 判断买家今日是否已经买入大于今日可购额度
     * @param $user_id
     * @param int $cid
     * @return array
     * @author ldz
     * @time 2020/2/2 20:53
     */
    public static function userIsCanBuyQbb($user_id, $buy_num, $cid = 1)
    {

        $surplus_num = ConfigModel::getUserCanBuyQbbNum($user_id, $cid);
        if ($surplus_num > 0) {
            if ($surplus_num >= $buy_num) {
                return [1, ''];
            } else {
                return [0, '今日您最多可购' . $surplus_num . 'QBB'];
            }

        } else {
            return [0, '今日已达可购额度，请明天再买'];
        }
    }

    /**
     * 获取用户剩余可买入的qbb数量
     * @param $user_id
     * @param int $cid
     * @return mixed
     * @author ldz
     * @time 2020/2/3 16:45
     */
    public static function getUserCanBuyQbbNum($user_id, $cid = 1)
    {
        $qbb_buy_acount = D('config')->getValue('qbb_buy_acount');

        $starTime = strtotime(date('Y-m-d'));
        $endTime = strtotime(date("Y-m-d", strtotime("+1 day")));
        $today_where['create_time'] = array(array('egt', $starTime), array('lt', $endTime), 'and');
        $today_where['buy_id'] = $user_id;
        $today_where['cid'] = $cid;
        $today_where['status'] = ['neq', 4];
        $user_today_buy_qbb_num = M('deals')->where($today_where)->sum('num');

        $surplus_num = $qbb_buy_acount - $user_today_buy_qbb_num;

        return $surplus_num;
    }

}
