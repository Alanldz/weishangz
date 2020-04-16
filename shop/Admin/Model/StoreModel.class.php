<?php

namespace Admin\Model;

use Common\Model\StoreRecordModel;
use Common\Model\UcoinsModel;
use Common\Util\Constants;
use Think\Exception;

class StoreModel extends \Common\Model\StoreModel
{

    /**
     * 修改用户财富
     * @return bool
     */
    public function changeWealth()
    {
        $user_id = intval(I('user_id'));
        $num = trim(I('num'));
        $store_type = trim(I('store_type'));
        $add_or_sub = intval(I('add_or_sub'));
        if (empty($num)) {
            $this->error = '数量不能为空';
            return false;
        }
        if (!preg_match('/^[1-9]\d*$/', $num)) {
            $this->error = '请输入整数';
            return false;
        }
        if (empty($store_type)) {
            $this->error = '请选择操作类型';
            return false;
        }
        $can_type = ['cangku_num', 'fengmi_num', 'ecological_certification', 'can_flow_amount', 'current_assets'];
        if (!in_array($store_type, $can_type)) {
            $this->error = '您选择的操作类型不存在，请从重新选择';
            return false;
        }
        $amount = $add_or_sub ? $num : -$num;
        M()->startTrans();
        try {
            switch ($store_type) {
                case 'ecological_certification':
                    UcoinsModel::addRecord($user_id, $amount, 3);
                    break;
                case 'cangku_num':
                    self::changStore($user_id, $store_type, $amount, 2);
                    break;
                case 'fengmi_num':
                    self::changStore($user_id, $store_type, $amount, 3);
                    break;
                case 'can_flow_amount':
                    StoreRecordModel::addRecord($user_id, $store_type, $amount, Constants::STORE_TYPE_CAN_FLOW, 7);
                    break;
                case 'current_assets':
                    StoreRecordModel::addRecord($user_id, $store_type, $amount, Constants::STORE_TYPE_CURRENT_ASSETS, 3);
                    break;

            }
            M()->commit();
            return true;
        } catch (Exception $ex) {
            M()->rollback();
            $this->error = $ex->getMessage();
            return false;
        }
    }

}
