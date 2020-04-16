<?php
/**
 * Created by PhpStorm.
 * User: ldz
 * Date: 2020/2/23
 * Time: 15:11
 */

namespace SAdmin\Model;

use Common\Model\ModelModel;
use Common\Util\Constants;
use Think\Exception;

class VerifyListModel extends ModelModel
{
    /**
     * 数据库表名
     */
    protected $tableName = 'verify_list';

    private static function validateFields($data)
    {
        if (empty($data['realname'])) {
            return '姓名不能为空';
        }
        if (empty($data['phone'])) {
            return '手机号不能为空';
        }
        if (!isMobile($data['phone'])) {
            return '手机号格式有误';
        }
        if (empty($data['idcard'])) {
            return '身份证不能为空';
        }
        if (empty($data['store_name'])) {
            return '店铺名称不能为空';
        }
        if ($data['type'] == Constants::VERIFY_PERSON) {
            if ($data['province_id'] == '--请选择省份--' || $data['province_id'] == '') {
                return '请选择省份';
            }

            if ($data['city_id'] == '' || $data['city_id'] == '--请选择市--') {
                return '请选择市';
            }

            if ($data['country_id'] == '--请选择区--' || $data['country_id'] == '') {
                return '请选择区';
            }
        }

        if (empty($data['shore_address'])) {
            return ($data['type'] == Constants::VERIFY_BUSINESS) ? '店铺地址不能为空' : '请输入详细地址';
        }

        return '';
    }

    /**
     * 修改认证信息
     * @return bool
     * @author ldz
     * @time 2020/3/19 17:09
     */
    public function editInfo()
    {
        M()->startTrans();
        try {
            $data = I('post.');
            $id = $data['id'];
            unset($data['id']);
            $error = $this->validateFields($data);
            if ($error) {
                throw new Exception($error);
            }

            $info = $this->where(['id' => $id])->field('id,uid')->find();
            if (!$info) {
                throw new Exception('该认证信息不存在，请重新选择');
            }

            $res = $this->where(['id' => $id])->save($data);
            if ($res === false) {
                throw new Exception('修改信息失败');
            }

            $typeField = ($data['type'] == Constants::VERIFY_BUSINESS) ? "is_e_verify" : "is_p_verify";
            $statusField = ($data['status'] == Constants::VERIFY_STATUS_PASS) ? Constants::YesNo_Yes : Constants::YesNo_No;

            $res = M("user")->where(array("userid" => $info['uid']))->setField($typeField, $statusField);
            if ($res === false) {
                throw new Exception('操作失败，请重试');
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