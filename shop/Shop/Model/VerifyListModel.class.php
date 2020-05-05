<?php
/**
 * Created by PhpStorm.
 * User: ldz
 * Date: 2020/2/23
 * Time: 15:11
 */

namespace Shop\Model;

use Common\Model\ModelModel;
use Common\Model\StoreRecordModel;
use Common\Util\Constants;
use Org\Net\IpLocation;
use Think\Exception;

class VerifyListModel extends ModelModel
{
    /**
     * 数据库表名
     */
    protected $tableName = 'verify_list';

    /**
     * 首页获取商家列表
     * @param int $limit
     * @return mixed
     * @author ldz
     * @time 2020/3/24 16:54
     */
    public static function getIndexList($limit = 6)
    {
        $where['type'] = Constants::VERIFY_PERSON;
        $where['status'] = Constants::VERIFY_STATUS_PASS;
        $fields = 'id,store_phone,store_name,city_id';
        $list = M('verify_list')->where($where)->field($fields)->order('id desc')->limit($limit)->select();
        return $list;
    }

    private static function validateFields($data)
    {
        if (empty($data['real_name'])) {
            return '姓名不能为空';
        }
        if (empty($data['phone'])) {
            return '手机号不能为空';
        }
        if (!isMobile($data['phone'])) {
            return '手机号格式有误';
        }
        if (empty($data['id_card'])) {
            return '身份证不能为空';
        }
        if (empty($data['store_name'])) {
            return '店铺名称不能为空';
        }
        if ($data['type'] == Constants::VERIFY_PERSON) {
            if ($data['province'] == '--请选择省份--') {
                return '请选择省份';
            }

            if ($data['city'] == '' || $data['city'] == '--请选择市--') {
                return '请选择市';
            }

            if ($data['district'] == '' || $data['district'] == '--请选择区--') {
                return '请选择区';
            }
        }

        if (empty($data['shore_address'])) {
            return ($data['type'] == Constants::VERIFY_BUSINESS) ? '店铺地址不能为空' : '请输入详细地址';
        }

        return '';
    }

    /**
     * 新增或修改认证信息
     * @return bool
     * @throws \Think\Exception
     */
    public function addOrUpdate()
    {
        $user_id = session('userid');
        $id = intval(I('id'));
        $params['real_name'] = trim(I("real_name"));
        $params['phone'] = trim(I("phone"));
        $params['id_card'] = trim(I("id_card"));
        $params['store_name'] = trim(I("store_name"));
        $params['province'] = trim(I("province"));
        $params['city'] = trim(I("city"));
        $params['district'] = trim(I("district"));
        $params['shore_address'] = trim(I("shore_address"));
        $params['type'] = intval(trim(I("type", Constants::VERIFY_PERSON)));

        $error = $this->validateFields($params);
        if ($error) {
            $this->error = $error;
            return false;
        }

        $userInfo = M("user")->where(array("userid" => $user_id))->field('mobile,username,is_e_verify,is_p_verify')->find();
        if ($userInfo['is_e_verify']) {
            $this->error = '您已经完成' . Constants::getVerifyTypeItems($params['type']);
            return false;
        }

        if (!$id) {
            $verifyCount = $this->where(array("uid" => $user_id, "type" => $params['type']))->count();
            if ($verifyCount > 0) {
                $this->error = '您已提交过认证申请';
                return false;
            }
        } else {
            $info = $this->where(array('id' => $id, "uid" => $user_id))->field('status')->find();
            if (!$info) {
                $this->error = '认证失败，您不能修改别人的认证信息';
                return false;
            }
            if ($info['status'] == Constants::VERIFY_STATUS_PASS) {
                $this->error = '您已经认证通过了，无须在认证';
                return false;
            }
        }

        $imgInfo = $_FILES;
        $is_upload = 0;
        if ($id) {
            if ($params['type'] == Constants::VERIFY_BUSINESS) {
                if ($imgInfo['legal_person_hand_card']['name'] || $imgInfo['business_license']['name']) {
                    $is_upload = 1;
                }
            } else {
                if ($imgInfo['legal_person_hand_card']['name'] || $imgInfo['business_license']['name'] || $imgInfo['store_phone']['name']) {
                    $is_upload = 1;
                }
            }

        } else {
            $is_upload = 1;
        }

        $img = [];
        if ($is_upload) {
            $img = moreimg_uploads();
            if ($img['status'] == '0') {
                $this->error = $img['res'];
                return false;
            }
        }

        $data = [];
        $i = 0;
        foreach ($imgInfo as $key => $v) {
            if (!empty($v['name'])) {
                $data[$key] = $img['res'][$i];
                $i++;
            }
        }
        if (!$id) {
            if ($params['type'] == Constants::VERIFY_BUSINESS) {
                if (empty($data['legal_person_hand_card'])) {
                    $this->error = '请上传有关证件';
                    return false;
                } else {
                    $dede['hand_idcard'] = $data['legal_person_hand_card'];
                }
            } else {
                if (empty($data['legal_person_hand_card'])) {
                    $this->error = '请上传有关证件';
                    return false;
                } else {
                    $dede['hand_idcard'] = $data['legal_person_hand_card'];
                }
            }
        }

        if ($data['legal_person_hand_card']) {
            $dede['hand_idcard'] = $data['legal_person_hand_card'];
        }
        if ($data['business_license']) {
            $dede['licence'] = $data['business_license'];
        }
        if ($data['store_phone']) {
            $dede['store_phone'] = $data['store_phone'];
        }
        if ($params['type'] == Constants::VERIFY_PERSON) {
            if ($id) {
                $verify_store_phone = $this->where(['id' => $id])->getField('store_phone');
                if (empty($verify_store_phone) && empty($dede['store_phone'])) {
                    $this->error = '请上传店铺照片';
                    return false;
                }
            }
            $dede['province_id'] = $params['province'];
            $dede['city_id'] = $params['city'];
            $dede['country_id'] = $params['district'];
        }

        $dede['uid'] = $user_id;
        $dede['account'] = $userInfo['mobile'];
        $dede['username'] = $userInfo['username'];
        $dede['type'] = $params['type'];
        $dede['realname'] = $params['real_name'];
        $dede['phone'] = $params['phone'];
        $dede['idcard'] = $params['id_card'];
        $dede['store_name'] = $params['store_name'];
        $dede['shore_address'] = $params['shore_address'];
        $dede['status'] = Constants::VERIFY_STATUS_WAIT;
        $dede['time'] = time();
        $dede['datestr'] = date("Ymd");
        $dede['business_hours'] = '';
        $dede['content'] = '';
        if ($id) {
            $res = $this->where(['id' => $id])->save($dede);
        } else {
            $res = $this->add($dede);
        }
        if (!$res) {
            $this->error = '提交申请失败';
            return false;
        }

        return true;
    }

    /**
     * 搜索商家
     * @param array $params
     * @return mixed
     * @author ldz
     * @time 2020/3/23 17:54
     */
    public static function search($params = [])
    {
        if (!empty($params['store_name'])) {
            $where['store_name'] = ['like', '%' . trim($params['store_name']) . '%'];
        }
        if (!empty($params['province_id']) && $params['province_id'] != '--请选择省份--') {
            $where['province_id'] = trim($params['province_id']);
        }
        if (!empty($params['city_id']) && $params['city_id'] != '--请选择市--') {
            $where['city_id'] = trim($params['city_id']);
        }
        if (!empty($params['country_id']) && $params['country_id'] != '--请选择区--') {
            $where['country_id'] = trim($params['country_id']);
        }
        $where['status'] = Constants::VERIFY_STATUS_PASS;
        $where['type'] = Constants::VERIFY_PERSON;
        $fields = 'id,store_name,store_phone,phone,province_id,city_id,country_id,shore_address,business_hours,buy_num,username';
        $list = M('verify_list')->where($where)->field($fields)->select();
        foreach ($list as &$value){
            $value['mobile_hidden'] = '';
            if($value['phone']){
                $mobile_start = substr($value['phone'],0,2);
                $mobile_end = substr($value['phone'],5);
                $value['mobile_hidden'] = $mobile_start.'***'.$mobile_end;
            }


        }
        return $list;
    }

    /**
     * 获取某个IP地址所在的位置
     * @param string $ip
     * @return bool|string
     */
    public static function getProvinceByIP($ip = '')
    {
        $Ips = new IpLocation('UTFWry.dat');
        $area = $Ips->getlocation($ip); //
        $specialProvince = ['北京', '上海', '天津', '重庆', '内蒙古', '西藏', '宁夏'];
        $province = '福建';
        if ($area) {
            if (strpos($area['country'], '省') !== false) {
                $province = substr($area['country'], 0, strrpos($area['country'], "省"));
            } else {
                foreach ($specialProvince as $item) {
                    if (strpos($area['country'], $item) !== false) {
                        $province = $item;
                        break;
                    }
                }
            }
        }

        return $province;
    }

    /**
     * 向商家支付
     * @return bool
     * @author ldz
     * @time 2020/3/24 9:55
     */
    public function storePay()
    {
        M()->startTrans();
        try {
            $user_id = session('userid');
            if (!$user_id) {
                throw new Exception('您还未登录，请先登录');
            }
            $id = intval(I('id'));
            $amount = formatNum2(trim(I('amount', 0)));
            $safe_pwd = trim(I('safe_pwd'));
            if ($amount <= 0) {
                throw new Exception('付款金额必须大于零');
            }

            if (empty($safe_pwd)) {
                throw new Exception('交易密码不能为空');
            }
            //验证交易密码
            $res = Trans($user_id, $safe_pwd);
            if (!$res['status']) {
                throw new Exception($res['msg']);
            }

            $where['id'] = $id;
            $where['status'] = Constants::VERIFY_STATUS_PASS;
            $where['type'] = Constants::VERIFY_PERSON;
            $info = M('verify_list')->where($where)->field('uid')->find();
            if (empty($info)) {
                throw new Exception('该商家信息不存在，请重试');
            }

            if ($info['uid'] == $user_id) {
                throw new Exception('不能付款给自己，请选择他人');
            }

            $storeInfo = M('store')->where(['uid' => $user_id])->field('cangku_num')->find();
            if ($amount > $storeInfo['cangku_num']) {
                throw new Exception('您的可用余额不足');
            }
            //扣除可用余额
            StoreModel::changStore($user_id, 'cangku_num', -$amount, 30);
            //增加营业账户
            StoreRecordModel::addRecord($info['uid'], 'turnover', $amount, Constants::STORE_TYPE_TURNOVER, 0, $user_id);
            //从流动资产释放到流动通证
            StoreModel::flowReleasePassCard($user_id, $amount, 5, 8);

            M()->commit();
            return true;
        } catch (Exception $ex) {
            $this->error = $ex->getMessage();
            M()->rollback();
            return false;
        }
    }

}