<?php
/**
 * Created by PhpStorm.
 * Date: 2020/1/14
 * Time: 11:23
 */

namespace Home\Model;

use Common\Model\ModelModel;
use Think\Exception;

class DealModel extends ModelModel
{
    protected $tableName = 'deal';

    /**
     * 获取手续费，和总金额
     * @param int $num 数量
     * @param float $price 单价
     * @param float $qbb_sell_multiple 倍数
     * @return array
     * @author ldz
     * @time 2020/1/16 10:57
     */
    public static function getOrderFeeAndMoney($num, $price, $qbb_sell_multiple)
    {
        $reality_num = $num * $price;
        $fee_num = $reality_num * 0.15; // 手续费
        $total_money = formatNum2($reality_num / $qbb_sell_multiple + ($reality_num - $fee_num - ($reality_num / $qbb_sell_multiple)) * 0.5, 4);//总金额

        return [$fee_num, $total_money];
    }

    /**
     * 获取今天交易单数
     * @param $user_id
     * @return int
     * @author ldz
     * @time 2020/1/15 15:02
     */
    public static function getTodayOrderNum($user_id)
    {
        $starTime = strtotime(date('Y-m-d'));
        $endTime = strtotime(date("Y-m-d", strtotime("+1 day")));
        $today_where['create_time'] = array(array('egt', $starTime), array('lt', $endTime), 'and');
        $today_where['sell_id'] = $user_id;
        $today_sell_num = M('deal')->where($today_where)->count();
        return $today_sell_num ? $today_sell_num : 0;
    }

    /**
     * 获取今天买入单数
     * @param $user_id
     * @return int
     * @author ldz
     * @time 2020/1/15 15:02
     */
    public static function getTodayBuyNum($user_id)
    {
        $starTime = strtotime(date('Y-m-d'));
        $endTime = strtotime(date("Y-m-d", strtotime("+1 day")));
        $today_where['create_time'] = array(array('egt', $starTime), array('lt', $endTime), 'and');
        $today_where['buy_id'] = $user_id;
        $today_sell_num = M('deals')->where($today_where)->count();
        return $today_sell_num ? $today_sell_num : 0;
    }

    /**
     * 卖出
     * @return bool
     * @author ldz
     * @time 2020/2/10 15:43
     */
    public function saleSell()
    {
        $uid = session('userid');
        $sell_way = intval(trim(I('sell_way', 1)));
        $sell_num = round(I('num', 'intval'));
        $pwd = trim(I('pwd'));
        $type_payment = intval(I('post.payment_type', 1));
        $card_id = intval(I('card_id', 0));

        if ($sell_num < 0) {
            $this->error = '卖出数量不能小于零';
            return false;
        }

        if (!isNumberByMultiple(10, $sell_num)) {
            $this->error = '卖出数量必须是10的倍数';
            return false;
        }

        if ($type_payment == 1) {
            $bank_id = M('ubanks')->where(['user_id' => $uid, 'id' => $card_id])->getField('id');
            if (!$bank_id) {
                $this->error = '请添加银行卡';
                return false;
            }
        } else if ($type_payment == 2) {
            $path = $_SERVER['DOCUMENT_ROOT'];
            if (!file_exists($path . '/Uploads/alipay/' . $uid . '.png')) {
                $this->error = '请上传支付宝二维码';
                return false;
            }
        } else if ($type_payment == 3) {
            $path = $_SERVER['DOCUMENT_ROOT'];
            if (!file_exists($path . '/Uploads/wechat/' . $uid . '.png')) {
                $this->error = '请上传微信二维码';
                return false;
            }
        }

        if ($pwd == '') {
            $this->error = '请输入交易密码';
            return false;
        }

        $userInfo = M('user')->where(array('userid' => $uid))->Field('account,quanxian')->find();
        //验证交易密码
        $userModel = new UserModel();
        $userModel->Trans('', $pwd);

        $quanXian = getArray($userInfo['quanxian']);
        if (in_array(4, $quanXian)) {
            $this->error = '您没有卖出的权限';
            return false;
        }

        $fee_num = $sell_num * 0.06; //手续费
        $total_num = $sell_num + $fee_num;

        $storeInfo = M('store')->where(['uid' => $uid])->field('cangku_num,fengmi_num')->find();
        M()->startTrans();
        try {
            if ($sell_way == 1) {
                $fields = 'cangku_num';
                $fields_name = '生态总资产';
                $get_type = 0;
                $now_nums = $storeInfo['cangku_num'];
                $now_nums_get = $storeInfo['cangku_num'] - $total_num;
            } else {
                $fields = 'fengmi_num';
                $fields_name = '消费通证';
                $get_type = 1;
                $now_nums = $storeInfo['fengmi_num'];
                $now_nums_get = $storeInfo['fengmi_num'] - $total_num;
            }

            if ($total_num > $storeInfo[$fields]) {
                throw new Exception('您的' . $fields_name . '不足');
            }
            $res = M('store')->where(['uid' => $uid])->setDec($fields, $total_num);
            if (!$res) {
                throw new Exception('扣除' . $fields_name . '失败');
            }

            //添加金额记录
            $tranMoney = [
                'pay_id' => $uid,
                'get_id' => $uid,
                'get_nums' => -$total_num,
                'get_type' => $get_type,
                'now_nums' => $now_nums,
                'now_nums_get' => $now_nums_get,
                'is_release' => 1,
                'get_time' => time()
            ];

            $res = M('tranmoney')->add($tranMoney);
            if (!$res) {
                throw new Exception('添加记录失败');
            }

            //生成交易记录
            $deal['total_num'] = $sell_num;
            $deal['num'] = $sell_num;
            $deal['sell_id'] = $uid;
            $deal['fee_num'] = $fee_num;
            $deal['sell_type'] = $sell_way;
            $deal['pay_way'] = $type_payment;
            $deal['card_id'] = $card_id;
            $deal['create_time'] = time();
            $res_tran = M('deal')->add($deal);
            if (!$res_tran) {
                throw new Exception('出售失败');
            }
            M()->commit();
            return true;
        } catch (Exception $ex) {
            M()->rollback();
            $this->error = $ex->getMessage();
            return false;
        }
    }

    /**
     * 用户购入
     * @return bool
     * @author ldz
     * @time 2020/1/14 14:58
     */
    public function buyShares()
    {
        $uid = session('userid'); //买入的id
        $deal_id = intval(I('order_id'));
        $buy_num = trim(I('buy_num'));
        $sale_pwd = trim(I('sale_pwd'));

        $deal_info = M('deal')->where(['id' => $deal_id, 'status' => ['in', [0, 1]]])->find();
        if (empty($deal_info)) {
            $this->error = '您买入的订单不存在，请重新选择~';
            return false;
        }

        if ($deal_info['sell_id'] == $uid) {
            $this->error = '不能购买自己出售的订单,请重新选择~';
            return false;
        }

        $surplus_num = $deal_info['num'] - $buy_num; //剩余数量
        if ($surplus_num < 0) {
            $this->error = '购买数量不能大于出售数量,请重新输入~';
            return false;
        }

        $user_info = M('user')->where(array('userid' => $uid))->Field('username')->find();
        //验证交易密码
        D('Home/User')->Trans('', $sale_pwd);

        M()->startTrans();
        try {
            $data['num'] = $buy_num;
            $data['sell_id'] = $deal_info['sell_id'];
            $data['buy_id'] = $uid;
            $data['fee_num'] = 0;
            $data['buy_uname'] = $user_info['username'];
            $data['sell_type'] = $deal_info['sell_type'];
            $data['d_id'] = $deal_info['id'];
            $data['create_time'] = time();

            $res = M('deals')->add($data);
            if (!$res) {
                throw new Exception('购买QBB失败');
            }

            if ($surplus_num == 0) {
                $deal_status = 2;
            } else {
                $deal_status = 1;
            }

            $res = M('deal')->where(['id' => $deal_id])->save(['status' => $deal_status, 'num' => $surplus_num]);
            if (!$res) {
                throw new Exception('修改状态失败，请重试');
            }

            M()->commit();
            return true;
        } catch (Exception $ex) {
            $this->error = $ex->getMessage();
            M()->rollback();
            return false;
        }
    }

    /**
     * 确认打款
     * @return bool
     * @author ldz
     * @time 2020/1/14 15:19
     */
    public function confirmReceipt()
    {
        $uid = session('userid');
        $picname = $_FILES['uploadfile']['name'];
        $picsize = $_FILES['uploadfile']['size'];
        $deals_id = intval(I('trid'));

        $deals_info = M('deals')->where(['id' => $deals_id])->find();
        if (empty($deals_info)) {
            $this->error = '该订单不存在';
            return false;
        }
        if ($deals_info['status'] != 1) {
            $this->error = '该订单已打款，请重新选择';
            return false;
        }
        if ($deals_info['buy_id'] != $uid) {
            $this->error = '该订单不是您购买的，不能打款';
            return false;
        }
        if (empty($picname)) {
            $this->error = '请上传打款截图';
            return false;
        }
        $pic_path = '';
        if ($picname != "") {
            if ($picsize > 2014000) { //限制上传大小
                $this->error = '图片大小不能超过2M';
                return false;
            }
            $type = substr($picname, strripos($picname, ".") + 1);; //限制上传格式
            if ($type != "gif" && $type != 'GIF' && $type != "jpg" && $type != "JPG" && $type != "png" && $type != 'PNG' && $type != "jpeg" && $type != "JPEG") {
                $this->error = '图片格式不对';
                return false;
            }
            $pics = uniqid() . '.' . $type; //命名图片名称
            //上传路径
            $pic_path = "./Uploads/Payvos/" . $pics;
            move_uploaded_file($_FILES['uploadfile']['tmp_name'], $pic_path);
        }
        $pic_path = trim($pic_path, '.');
        M()->startTrans();
        try {
            $data['img'] = $pic_path;
            $data['status'] = 2;
            $data['img_upload_time'] = time();
            $res = M('deals')->where(['id' => $deals_id])->save($data);
            if (!$res) {
                throw new Exception('打款成功');
            }

            M()->commit();
            return true;
        } catch (Exception $ex) {
            $this->error = $ex->getMessage();
            M()->rollback();
            return false;
        }
    }

    /**
     * 确认收款
     * @param $uid
     * @param $deals_id
     * @return bool
     * @author ldz
     * @time 2020/1/16 14:58
     */
    public function confirmSk($uid, $deals_id)
    {
        //挂卖单人的ID
        $deals_info = M('deals')->where(array('id' => $deals_id, 'status' => 2, 'sell_id' => $uid))->find();
        if (empty($deals_info)) {
            $this->error = '收款单不存在，请重新选择~';
            return false;
        }

        M()->startTrans();
        try {
            $storeInfo = M('store')->where(['uid' => $uid])->field('cangku_num,fengmi_num')->find();

            //卖家得到
            if ($deals_info['sell_type'] == 1) {
                $fields = 'cangku_num';
                $fields_name = '生态总资产';
                $get_type = 4;
                $now_nums = $storeInfo['cangku_num'];
                $now_nums_get = $storeInfo['cangku_num'] + $deals_info['num'];
            } else {
                $fields = 'fengmi_num';
                $fields_name = '消费通证';
                $get_type = 5;
                $now_nums = $storeInfo['fengmi_num'];
                $now_nums_get = $storeInfo['fengmi_num'] + $deals_info['num'];
            }

            $res = M('store')->where(['uid' => $deals_info['buy_id']])->setInc($fields, $deals_info['num']);
            if (!$res) {
                throw new Exception('卖家得到' . $fields_name . '失败');
            }

            $sellTranRecord = [
                'pay_id' => $deals_info['buy_id'],
                'get_id' => $deals_info['buy_id'],
                'now_nums' => $now_nums,
                'now_nums_get' => $now_nums_get,
                'get_nums' => $deals_info['num'],
                'is_release' => 1,
                'get_type' => $get_type,
                'get_time' => time(),
            ];

            $res = M('tranmoney')->add($sellTranRecord);
            if (!$res) {
                throw new Exception('添加生态总资产记录');
            }

            //修改订单状态
            $res = M('deals')->where(['id' => $deals_id])->save(['status' => 3]);
            if (!$res) {
                throw new Exception('修改订单状态失败');
            }

            M()->commit();
            return true;
        } catch (Exception $ex) {
            $this->error = $ex->getMessage();
            M()->rollback();
            return false;
        }
    }


}