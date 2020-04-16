<?php

namespace Home\Model;

use Common\Util\Constants;

class StoreRecordModel extends \Common\Model\StoreRecordModel
{
    /**
     * 获取列表
     * @param array $params
     * @return mixed
     * @author ldz
     * @time 2020/2/24 14:33
     */
    public static function search($params = [])
    {
        $uid = session('userid');
        $where['uid'] = $uid;
        $where['store_type'] = $params['store_type'] ? $params['store_type'] : Constants::STORE_TYPE_SHARE_REWARD;
        //分页
        $page = I('p', 1);
        $limit = 10;
        $list = M('store_record')->where($where)->limit(($page - 1) * $limit, $limit)
            ->field('amount,type,remark,create_time')->order('id desc')->select();

        switch ($where['store_type']) {
            case Constants::STORE_TYPE_MERITS_REWARD : //绩效奖（百宝箱）
                $list = self::getMeritsRewardItems($list);
                break;
            case Constants::STORE_TYPE_THANKFUL_REWARD : //感恩奖（百宝箱）
                $list = self::getThankfulRewardItems($list);
                break;
            case Constants::STORE_TYPE_FLOW : //流动通证（百宝箱）
                $list = self::getFlowItems($list);
                break;
            case Constants::STORE_TYPE_FEEDBACK : //回馈（百宝箱）
                $list = self::getFeedbackItems($list);
                break;
            case Constants::STORE_TYPE_CAN_FLOW : //可用流动通证
                $list = self::getCanFlowItems($list);
                break;
            case Constants::STORE_TYPE_CURRENT_ASSETS : //流动资产
                $list = self::getCurrentAssetsItems($list);
                break;
            case Constants::STORE_TYPE_PRODUCT_INTEGRAL : //产品积分
                $list = self::getProductIntegralItems($list);
                break;
            case Constants::STORE_TYPE_TURNOVER : //营业款账户
                $list = self::getTurnoverItems($list);
                break;
            case Constants::STORE_TYPE_SHARE_REWARD : //分享奖（百宝箱）
            default :
                $list = self::getShareRewardItems($list);
                break;
        }

        return $list;
    }

    /**
     * 获取分享奖类型
     * @param $list
     * @return mixed
     */
    public static function getShareRewardItems($list)
    {
        foreach ($list as $k => $v) {
            $list[$k]['create_time'] = toDate($v['create_time']);
            $remark = $v['remark'] ? '(' . $v['remark'] . ')' : '';
            switch ($v['type']) {
                case 0:
                    $list[$k]['type_name'] = '购物';
                    break;
                case 1:
                    $list[$k]['type_name'] = '分享奖' . $remark;
                    break;
                case 2:
                    $list[$k]['type_name'] = '分享释放';
                    break;
                default :
                    $list[$k]['type_name'] = '';
                    break;
            }
        }
        return $list;
    }

    /**
     * 获取绩效奖类型
     * @param $list
     * @return mixed
     */
    public static function getMeritsRewardItems($list)
    {
        foreach ($list as $k => $v) {
            $list[$k]['create_time'] = toDate($v['create_time']);
            $remark = $v['remark'] ? '(' . $v['remark'] . ')' : '';
            switch ($v['type']) {
                case 0:
                    $list[$k]['type_name'] = '团队收益' . $remark;
                    break;
                case 1:
                    $list[$k]['type_name'] = '绩效释放';
                    break;
                default :
                    $list[$k]['type_name'] = '';
                    break;
            }
        }
        return $list;
    }

    /**
     * 获取绩效奖类型
     * @param $list
     * @return mixed
     */
    public static function getThankfulRewardItems($list)
    {
        foreach ($list as $k => $v) {
            $list[$k]['create_time'] = toDate($v['create_time']);
            $remark = $v['remark'] ? '(' . $v['remark'] . ')' : '';
            switch ($v['type']) {
                case 0:
                    $list[$k]['type_name'] = '感恩' . $remark;
                    break;
                case 1:
                    $list[$k]['type_name'] = '感恩释放';
                    break;
                default :
                    $list[$k]['type_name'] = '';
                    break;
            }
        }
        return $list;
    }

    /**
     * 获取流动通证类型
     * @param $list
     * @return mixed
     */
    public static function getFlowItems($list)
    {
        foreach ($list as $k => $v) {
            $list[$k]['create_time'] = toDate($v['create_time']);
            $remark = $v['remark'] ? '(' . $v['remark'] . ')' : '';
            switch ($v['type']) {
                case 0:
                    $list[$k]['type_name'] = '流动释放';
                    break;
                case 1:
                    $list[$k]['type_name'] = '资产释放';
                    break;
                case 2:
                    $list[$k]['type_name'] = '';
                    break;
                default :
                    $list[$k]['type_name'] = '';
                    break;
            }
        }
        return $list;
    }

    /**
     * 获取回馈类型
     * @param $list
     * @return mixed
     */
    public static function getFeedbackItems($list)
    {
        foreach ($list as $k => $v) {
            $list[$k]['create_time'] = toDate($v['create_time']);
            $remark = $v['remark'] ? '(' . $v['remark'] . ')' : '';
            switch ($v['type']) {
                case 0:
                    $list[$k]['type_name'] = '回馈' . $remark;
                    break;
                case 1:
                    $list[$k]['type_name'] = '回馈释放';
                    break;
                default :
                    $list[$k]['type_name'] = '';
                    break;
            }
        }
        return $list;
    }

    /**
     * 获取可用流动通证类型
     * @param $list
     * @return mixed
     */
    public static function getCanFlowItems($list)
    {
        foreach ($list as $k => $v) {
            $list[$k]['create_time'] = toDate($v['create_time']);
            $remark = $v['remark'] ? '(' . $v['remark'] . ')' : '';
            switch ($v['type']) {
                case 0:
                    $list[$k]['type_name'] = '锁仓释放';
                    break;
                case 1:
                    $list[$k]['type_name'] = '生态释放';
                    break;
                case 2:
                    $list[$k]['type_name'] = '流动释放';
                    break;
                case 3:
                    $list[$k]['type_name'] = '分享奖' . $remark;
                    break;
                case 4:
                    $list[$k]['type_name'] = '绩效奖' . $remark;
                    break;
                case 5:
                    $list[$k]['type_name'] = '购物';
                    break;
                case 6:
                    $list[$k]['type_name'] = '自营释放';
                    break;
                case 7:
                    $list[$k]['type_name'] = '平台操作';
                    break;
                case 8:
                    $list[$k]['type_name'] = '实体消费';
                    break;
                case 9:
                    $list[$k]['type_name'] = '自营消费';
                    break;
                case 10:
                    $list[$k]['type_name'] = '增值额';
                    break;
                case 11:
                    $list[$k]['type_name'] = '回馈宝箱';
                    break;
                default :
                    $list[$k]['type_name'] = '';
                    break;
            }
        }
        return $list;
    }

    /**
     * 获取流动资产类型
     * @param $list
     * @return mixed
     */
    public static function getCurrentAssetsItems($list)
    {
        foreach ($list as $k => $v) {
            $list[$k]['create_time'] = toDate($v['create_time']);
            $remark = $v['remark'] ? '(' . $v['remark'] . ')' : '';
            switch ($v['type']) {
                case 0:
                    $list[$k]['type_name'] = '直推释放' . $remark;
                    break;
                case 1:
                    $list[$k]['type_name'] = '购物';
                    break;
                case 2:
                    $list[$k]['type_name'] = '自营释放';
                    break;
                case 3:
                    $list[$k]['type_name'] = '平台操作';
                    break;
                case 4:
                    $list[$k]['type_name'] = '注册赠送';
                    break;
                case 5:
                    $list[$k]['type_name'] = '实体消费';
                    break;
                case 6:
                    $list[$k]['type_name'] = '自营消费';
                    break;
                default :
                    $list[$k]['type_name'] = '';
                    break;
            }
        }
        return $list;
    }

    /**
     * 获取产品积分类型
     * @param $list
     * @return mixed
     */
    public static function getProductIntegralItems($list)
    {
        foreach ($list as $k => $v) {
            $list[$k]['create_time'] = toDate($v['create_time']);
            $remark = $v['remark'] ? '(' . $v['remark'] . ')' : '';
            switch ($v['type']) {
                case 0:
                    $list[$k]['type_name'] = '流动宝箱' . $remark;
                    break;
                case 1:
                    $list[$k]['type_name'] = '购物';
                    break;
                default :
                    $list[$k]['type_name'] = '';
                    break;
            }
        }
        return $list;
    }

    /**
     * 获取营业款账户类型
     * @param $list
     * @return mixed
     */
    public static function getTurnoverItems($list)
    {
        foreach ($list as $k => $v) {
            $list[$k]['create_time'] = toDate($v['create_time']);
            $remark = $v['remark'] ? '(' . $v['remark'] . ')' : '';
            switch ($v['type']) {
                case 0:
                    $list[$k]['type_name'] = '实体收入' . $remark;
                    break;
                case 1:
                    $list[$k]['type_name'] = '提现';
                    break;
                default :
                    $list[$k]['type_name'] = '';
                    break;
            }
        }
        return $list;
    }

}
