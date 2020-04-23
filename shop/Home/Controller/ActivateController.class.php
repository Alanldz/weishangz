<?php
/**
 * Created by PhpStorm.
 * Date: 2018/11/30
 * Time: 22:03
 */

namespace Home\Controller;

use Home\Model\ActivateCardModel;
use Home\Model\PurchaseRecordModel;
use Home\Model\UserModel;

class ActivateController extends CommonController
{

    /**
     * 激活卡页面
     * @time 2018-12-15 12:39:07
     */
    public function activate_card()
    {
        $uid = session('userid');
        $step = intval(I('step', 0));
        $returnData = ActivateCardModel::search();
        $user = M('user')->where(['userid' => $uid])->field('service_center')->find();
        $list = [];
        foreach ($returnData['list'] as $k => $v) {
            $list[$k] = $v;
            $list[$k]['create_time'] = date('Y-m-d H:i:s', strtotime($v['create_time']));
            switch ($v['level']) {
                case 2:
                    $level_name = '二星';
                    break;
                case 3:
                    $level_name = '三星';
                    break;
                case 4:
                    $level_name = '四星';
                    break;
                case 1:
                default:
                    $level_name = '一星';
                    break;
            }
            $arrCard = M('activate_card')->where(['activation_code' => $v['activation_code']])->order('id asc')->select();
            $making_id = current($arrCard)['uid'];
            if (count($arrCard) > 1 && $user['service_center'] == 0) {
                $list[$k]['is_giving'] = false;
            } else {
                $list[$k]['is_giving'] = true;
            }
            if ($step == 0) {
                $list[$k]['header_name'] = '激活' . $level_name . '会员-制卡者：' . $making_id;
            } else {
                $card_record = M('activate_card_record')->where(['activate_card_id' => $v['id'], 'delete_time' => ''])->find();
                $list[$k]['header_name'] = '制作者：' . $making_id . ' 已激活' . $level_name . '会员：' . $card_record['userid'];
            }
        }

        $this->assign('list', $list);
        $this->assign('step', $step);
        $this->assign('page', $returnData['page']);
        $this->display();
    }

    /**
     * 制作激活卡
     * @time 2018-12-16 10:48:59
     */
    public function markCard()
    {
        if (IS_AJAX) {
            $models = new ActivateCardModel();
            $res = $models->mark_card();
            if (!$res) {
                ajaxReturn($models->getError(), 0);
            }
            ajaxReturn('制作成功', 1);
        }
        $uid = session('userid');
        $store = M('store')->where(['uid' => $uid])->field('cangku_num')->find();
        $this->assign('store', $store);
        $this->display();
    }

    /**
     * 赠送激活卡
     * @time 2018-12-16 11:33:36
     */
    public function giftActivationCard()
    {
        if (IS_AJAX) {
            $models = new ActivateCardModel();
            $res = $models->gift_card();
            if (!$res) {
                ajaxReturn($models->getError(), 0);
            }
            ajaxReturn('赠送成功', 1, '/Activate/activate_card');
        }
        $card_id = intval(I('card_id', 0));
        $cardInfo = ActivateCardModel::getCardInfoById($card_id, 'id,activation_code');
        $this->assign('cardInfo', $cardInfo);
        $this->display();
    }

    /**
     * 激活用户页
     */
    public function index()
    {
        $uid = session('userid');
        $userInfo = M('user')->where(['userid' => $uid])->field('username,mobile,level')->find();

        $this->assign('userInfo', $userInfo);
        $this->display();
    }

    /**
     * 提交激活
     */
    public function submit()
    {
        $uid = session('userid');
        $type = I('type', 1);
        $activation_id = intval(I('activation_id'));
        $card_id = intval(I('card_id', 0));
        $trade_pwd = I('trade_pwd');

        if ($type == '') {
            ajaxReturn('请选择激活方式', 0);
        }
        if ($activation_id == 0) {
            ajaxReturn('请输入激活账户', 0);
        }
        if ($trade_pwd == '') {
            ajaxReturn('请输入交易密码', 0);
        }
        if ($type == 2 && ($card_id == 0 || $card_id == '')) {
            ajaxReturn('请选择激活卡', 0);
        }

        //验证交易密码
        $userModel = D('Home/User');
        $minepwd = $userModel->where(array('userid' => $uid))->Field('mobile,account,account_type,empty_order,service_center_account,service_center')->find();
        $userModel->Trans($minepwd['account'], $trade_pwd);
        $userStore = M('store')->where(['uid' => $uid])->field('cangku_num,complex_integral')->find();

        if ($minepwd['empty_order']) {
            ajaxReturn('您不能激活会员，有疑问请留言！', 0);
        }

        //激活用户信息
        $activation_account = $userModel->where(['userid' => $activation_id])->field('mobile,pid,junction_id,junction_path,zone,level,investment_grade,account_type,service_center_account')->find();
        if (!$activation_account) {
            ajaxReturn('激活用户不存在', 0);
        }
        if ($activation_account['level'] != 0) {
            ajaxReturn('该用户已被激活', 0);
        }

        //判断激活用户跟自己是不是同一个服务中心或者服务中心是自己
        if ($minepwd['service_center']) {
            if ($activation_account['service_center_account'] != $uid && $activation_account['service_center_account'] != $minepwd['service_center_account']) {
                ajaxReturn('激活失败，非同一个服务中心', 0);
            }
        } else {
            if ($activation_account['service_center_account'] != $minepwd['service_center_account']) {
                ajaxReturn('激活失败，非同一个服务中心', 0);
            }
        }
        $investment_grade = UserModel::$INVESTMENT_GRADE;   //投资金级别
        $purchase_rate = UserModel::$PURCHASE_RATE;         //购股积分比率

        M()->startTrans();   //开启事务
        $cangku_num = $investment_grade[$activation_account['investment_grade']];       //所需要支付ep值
        $get_purchase_integral = $cangku_num * $purchase_rate[$activation_account['investment_grade']];

        if ($type == 2) {//用激活卡激活
            $cardInfo = M('activate_card')->where(['id' => $card_id, 'delete_time' => ''])->find();
            if (empty($cardInfo)) {
                ajaxReturn('该激活卡不存在', 0);
            }
            if ($cardInfo['uid'] != $uid) {
                ajaxReturn('该激活卡不是您的，请重新选择', 0);
            }
            if ($cardInfo['status'] == 1) {
                ajaxReturn('该激活卡已被使用，请重新选择', 0);
            }
            if ($cardInfo['level'] != $activation_account['investment_grade']) {
                ajaxReturn('该激活卡只能激活' . $cardInfo['level'] . '星的用户', 0);
            }
            $res = M('activate_card')->where(['id' => $card_id])->save(['status' => 1, 'use_time' => date('YmdHis')]);
            if (!$res) {
                M()->rollback();
                ajaxReturn('激活卡激活失败，请重试', 0);
            }
            //添加激活卡记录
            $cardRecard = [
                'uid' => $uid,
                'activate_card_id' => $card_id,
                'userid' => $activation_id,
                'level' => $activation_account['investment_grade'],
                'type' => 1,
                'create_time' => date('YmdHis')
            ];
            $res = M('activate_card_record')->add($cardRecard);
            if (!$res) {
                M()->rollback();
                ajaxReturn('添加激活记录失败', 0);
            }
        } elseif ($type == 3) { //EF激活
            $is_quanxian = 0;
            if ($minepwd['mobile'] == $activation_account['mobile'] && $activation_account['account_type'] == 1) {
                $is_quanxian = 1;
            }

            if (!$is_quanxian) {
                ajaxReturn('您只能激活自己的子账户', 0);
            }

            if ($cangku_num > $userStore['complex_integral']) {
                ajaxReturn('您的EF金额不足，不能激活', 0);
            }

            //扣除ef值
            $datapay['complex_integral'] = array('exp', 'complex_integral -' . $cangku_num);
            $res_pay = M('store')->where(array('uid' => $uid))->save($datapay);
            if (!$res_pay) {
                M()->rollback();
                ajaxReturn('扣除EF失败，请重试', 0);
            }

            //添加EF记录
            $complex_record = [
                'uid' => $uid,
                'amount' => -$cangku_num,
                'type' => 3,
                'create_time' => date('YmdHis')
            ];
            $res = M('complex_record')->add($complex_record);
            if (!$res) {
                M()->rollback();
                ajaxReturn('复投积分流水创建失败', 0);
            }

            //添加激活记录
            $cardRecard = [
                'uid' => $uid,
                'userid' => $activation_id,
                'level' => $activation_account['investment_grade'],
                'type' => 4,
                'create_time' => date('YmdHis')
            ];
            $res = M('activate_card_record')->add($cardRecard);
            if (!$res) {
                M()->rollback();
                ajaxReturn('添加激活记录失败', 0);
            }

        } else {//ep激活
            $is_quanxian = 0;
            if ($minepwd['mobile'] == $activation_account['mobile'] && $activation_account['account_type'] == 1) {
                $is_quanxian = 1;
            }

            if ($activation_account['pid'] != $uid && !$is_quanxian) {
                ajaxReturn('您只能激活推荐人是自己和自己的子账户', 0);
            }

            if ($cangku_num > $userStore['cangku_num']) {
                ajaxReturn('您的EP金额不足，不能激活', 0);
            }

            //扣除ep值
            $datapay['cangku_num'] = array('exp', 'cangku_num -' . $cangku_num);
            $res_pay = M('store')->where(array('uid' => $uid))->save($datapay);
            if (!$res_pay) {
                M()->rollback();
                ajaxReturn('扣除ep失败，请重试', 0);
            }

            //Ep流水
            $data['pay_id'] = $uid;
            $data['get_id'] = $uid;
            $data['get_nums'] = -$cangku_num;
            $data['now_nums_get'] = $userStore['cangku_num'] - $cangku_num;
            $data['now_nums'] = $userStore['cangku_num'] - $cangku_num;
            $data['is_release'] = 1;
            $data['get_time'] = time();
            $data['get_type'] = 35;
            $data['remark'] = $activation_id;
            $res = M('tranmoney')->add($data);
            if (!$res) {
                M()->rollback();
                ajaxReturn('ep流水添加失败', 0);
            }

            //添加激活记录
            $cardRecard = [
                'uid' => $uid,
                'userid' => $activation_id,
                'level' => $activation_account['investment_grade'],
                'type' => 3,
                'create_time' => date('YmdHis')
            ];
            $res = M('activate_card_record')->add($cardRecard);
            if (!$res) {
                M()->rollback();
                ajaxReturn('添加激活记录失败', 0);
            }
        }


        //给激活用户加购股积分
        $dateGet['purchase_integral'] = array('exp', 'purchase_integral +' . $get_purchase_integral);
        $res_get = M('store')->where(array('uid' => $activation_id))->save($dateGet);
        if (!$res_get) {
            M()->rollback();
            ajaxReturn('购股积分添加失败', 0);
        }

        //添加购股记录
        $res = (new PurchaseRecordModel())->addData($activation_id, $get_purchase_integral, 1);
        if (!$res) {
            M()->rollback();
            ajaxReturn('添加购股记录失败，请重试', 0);
        }

        //给激活用户接点人增加见点积分、爱心基金 规则：1星可以给4层 2星给5层 3星给6层 4星给7层
        $see_point_integral = $cangku_num * 0.01 * 0.98;
        $charity_points = $cangku_num * 0.01 * 0.02;
        $junction_path = $this->getArrayjunction($activation_account['junction_path']);
        $junction_num = count($junction_path) + 1;  //层数
        $numplies = ($activation_account['investment_grade'] > 0) ? ($activation_account['investment_grade'] + 3) : 0;
        foreach ($junction_path as $k => $item) {
            $dateJunction = [];
            if ($k >= $numplies) {
                continue;
            }

            $dateJunction['cangku_num'] = array('exp', 'cangku_num +' . $see_point_integral);
            $dateJunction['charity_points'] = array('exp', 'charity_points +' . $charity_points);
            $res_get = M('store')->where(array('uid' => $item))->save($dateJunction);
            if (!$res_get) {
                M()->rollback();
                ajaxReturn('见点值添加失败', 0);
            }

            //见点积分Ep流水
            $superiorCangku_num = M('store')->where(array('uid' => $item))->getField('cangku_num');
            $data['pay_id'] = $item;
            $data['get_id'] = $item;
            $data['get_nums'] = $see_point_integral;
            $data['now_nums_get'] = $superiorCangku_num;
            $data['now_nums'] = $superiorCangku_num;
            $data['is_release'] = 1;
            $data['get_time'] = time();
            $data['get_type'] = 36;
            $data['remark'] = $activation_id;
            $res = M('tranmoney')->add($data);
            if (!$res) {
                M()->rollback();
                ajaxReturn('ep流水添加失败', 0);
            }

            //爱心基金流水
            $charity_record = [
                'uid' => $item,
                'amount' => $charity_points,
                'type' => 2,
                'create_time' => date('YmdHis')
            ];
            $res = M('charity_score_record')->add($charity_record);
            if (!$res) {
                M()->rollback();
                ajaxReturn('爱心基金流水创建失败', 0);
            }
        }

        //分享奖
        $share_price = $cangku_num * 0.08 * 0.98;
        $share_love_price = $cangku_num * 0.08 * 0.02;
        if (!empty($activation_account['pid'])) {
            $dateshare['cangku_num'] = ['exp', 'cangku_num +' . $share_price];
            $dateshare['charity_points'] = ['exp', 'charity_points +' . $share_love_price];
            $res = M('store')->where(array('uid' => $activation_account['pid']))->save($dateshare);
            if (!$res) {
                M()->rollback();
                ajaxReturn('分享奖添加失败', 0);
            }

            //分享奖Ep流水
            $shareCangku_num = M('store')->where(array('uid' => $activation_account['pid']))->getField('cangku_num');
            $data['pay_id'] = $activation_account['pid'];
            $data['get_id'] = $activation_account['pid'];
            $data['get_nums'] = $share_price;
            $data['now_nums_get'] = $shareCangku_num;
            $data['now_nums'] = $shareCangku_num;
            $data['is_release'] = 1;
            $data['get_time'] = time();
            $data['get_type'] = 37;
            $res = M('tranmoney')->add($data);
            if (!$res) {
                M()->rollback();
                ajaxReturn('ep流水添加失败', 0);
            }

            //爱心基金流水
            $charity_record = [
                'uid' => $activation_account['pid'],
                'amount' => $share_love_price,
                'type' => 3,
                'create_time' => date('YmdHis')
            ];
            $res = M('charity_score_record')->add($charity_record);
            if (!$res) {
                M()->rollback();
                ajaxReturn('爱心基金流水创建失败', 0);
            }
        }

        //层碰
        $founder = $this->getFounder($activation_account['junction_path']);
        $sql = "SELECT userid,junction_id,junction_path,level,get_touch_layer,activation_time,investment_grade FROM ysk_user WHERE length(junction_path) - LENGTH(replace(junction_path,'-','')) = " . $junction_num . " AND junction_path LIKE '-" . $founder . "-%' AND level != 0  AND userid != " . $activation_id;
        $same_layer_data = $userModel->query($sql);

        $get_touch_ids = []; //得到层碰将的用户
        $week_seal = UserModel::$WEEKSEAL_PRICE; //周封金额
        $lead = [];
        $arrTouch = [];
        foreach ($same_layer_data as $k => $item) {
            //判断是否跟激活者同一个接点人
            if ($item['junction_id'] == $activation_account['junction_id']) {
                $arrTouch[$item['junction_id']][] = $item;
            } else {
                $get_layer_id = $this->getLayerUser($item['junction_id'], $activation_account['junction_path']);//获得层碰金额用户id
                $arrTouch[$get_layer_id][] = $item;
            }
        }

        foreach ($arrTouch as $get_touch_layer_id => $arrLayer) {
            $flag = [];
            foreach ($arrLayer as $v) {
                $flag[] = $v['activation_time'];
            }
            array_multisort($flag, SORT_ASC, $arrLayer);
            $duipeng_info = [];
            foreach ($arrLayer as $v) {
                if (!empty($v['get_touch_layer'])) {
                    $get_touch_layer = explode(',', $v['get_touch_layer']);
                    if (in_array($get_touch_layer_id, $get_touch_layer)) {
                        continue;
                    }
                    $duipeng_info = $v;
                    break;
                } else {
                    $duipeng_info = $v;
                    break;
                }
            }
            if (!empty($duipeng_info)) {
                //修改对碰用户信息
                if (!empty($duipeng_info['get_touch_layer'])) {
                    $get_touch_path = explode(',', $duipeng_info['get_touch_layer']);
                    $get_touch_path[] = $get_touch_layer_id;
                } else {
                    $get_touch_path[0] = $get_touch_layer_id;
                }
                $res = $userModel->where(['userid' => $duipeng_info['userid']])->save(['get_touch_layer' => implode(',', $get_touch_path)]);
                if (!$res) {
                    M()->rollback();
                    ajaxReturn('修改层碰信息失败', 0);
                }

                //修改激活者信息
                $activation_get_touch_path = $userModel->where(['userid' => $activation_id])->getField('get_touch_layer');
                if (!empty($activation_get_touch_path)) {
                    $activation_touch_path = explode(',', $activation_get_touch_path);
                    $activation_touch_path[] = $get_touch_layer_id;
                } else {
                    $activation_touch_path[0] = $get_touch_layer_id;
                }
                $res = $userModel->where(['userid' => $activation_id])->save(['get_touch_layer' => implode(',', $activation_touch_path)]);
                if (!$res) {
                    M()->rollback();
                    ajaxReturn('修改层碰信息失败', 0);
                }

                $junction_info = $userModel->where(['userid' => $get_touch_layer_id])->field('path,level,junction_id,junction_path')->find();
                $junction_layer_amount = M('store')->where(['uid' => $get_touch_layer_id])->getField('layer_amount');
                $week_get_layer_amount = $this->getWeekLayeramount($get_touch_layer_id); //该用户本周得到的层碰金额
                //判断接点人是否超过周封顶和层碰顶
                if ($junction_layer_amount < $this->getCappinglayer($junction_info['level']) || $week_get_layer_amount < $week_seal[$junction_info['level']]) {
                    $touch_amount = $this->geTouchAmount($activation_account['investment_grade'], $duipeng_info['investment_grade']);
                    $get_touch_amount = $touch_amount * 0.08;
                    $user_touch_amount = $get_touch_amount * 0.98;      //层碰所得金额
                    $get_lead_charity_amount = $get_touch_amount * 0.02;//层碰爱心基金
                    $storeData = [
                        'cangku_num' => ['exp', 'cangku_num +' . $user_touch_amount],
                        'charity_points' => ['exp', 'charity_points +' . $get_lead_charity_amount]
                    ];
                    $res = M('store')->where(array('uid' => $get_touch_layer_id))->save($storeData);
                    if (!$res) {
                        M()->rollback();
                        ajaxReturn('层奖添加失败', 0);
                    }

                    //层奖Ep流水
                    $cangku_num = M('store')->where(array('uid' => $get_touch_layer_id))->getField('cangku_num');
                    $data['pay_id'] = $get_touch_layer_id;
                    $data['get_id'] = $get_touch_layer_id;
                    $data['get_nums'] = $user_touch_amount;
                    $data['now_nums_get'] = $cangku_num;
                    $data['now_nums'] = $cangku_num;
                    $data['is_release'] = 1;
                    $data['get_time'] = time();
                    $data['get_type'] = 38;
                    $data['remark'] = $duipeng_info['userid'] . '/' . $activation_id;
                    $res = M('tranmoney')->add($data);
                    if (!$res) {
                        M()->rollback();
                        ajaxReturn('ep流水添加失败', 0);
                    }

                    //爱心基金流水
                    $charity_record = [
                        'uid' => $get_touch_layer_id,
                        'amount' => $get_lead_charity_amount,
                        'type' => 4,
                        'create_time' => date('YmdHis')
                    ];
                    $res = M('charity_score_record')->add($charity_record);
                    if (!$res) {
                        M()->rollback();
                        ajaxReturn('爱心基金流水创建失败', 0);
                    }

                    //领导奖
                    $arrLead = $this->getArrayjunction($junction_info['path']);
                    foreach ($arrLead as $key => $lead_id) {
                        $lead_info = $userModel->where(['userid' => $lead_id])->field('level')->find();
                        $generations = $lead_info['level'] + 3;
                        if ($generations > $key) {
                            $lead[$lead_id][] = $get_touch_amount;
                        }
                    }

                    $get_touch_ids[] = [
                        'userid' => $get_touch_layer_id,
                        'money' => $user_touch_amount
                    ];
                }
            }

        }


        //领导奖
        if (!empty($lead)) {
            foreach ($lead as $lead_id => $item) {
                $get_touch_amount = 0;
                foreach ($item as $v) {
                    $get_touch_amount += $v;
                }
                $get_lead_amount = formatNum2($get_touch_amount * 0.05); //拿层奖的5%
                if ($get_lead_amount > 0) {
                    $leadData = [
                        'cangku_num' => ['exp', 'cangku_num +' . $get_lead_amount]
                    ];
                    $res = M('store')->where(array('uid' => $lead_id))->save($leadData);
                    if (!$res) {
                        M()->rollback();
                        ajaxReturn('领导奖添加失败', 0);
                    }

                    //领导奖Ep流水
                    $leadCangku_num = M('store')->where(array('uid' => $lead_id))->getField('cangku_num');
                    $data['pay_id'] = $lead_id;
                    $data['get_id'] = $lead_id;
                    $data['get_nums'] = $get_lead_amount;
                    $data['now_nums_get'] = $leadCangku_num;
                    $data['now_nums'] = $leadCangku_num;
                    $data['is_release'] = 1;
                    $data['get_time'] = time();
                    $data['get_type'] = 39;
                    $data['remark'] = $activation_id;
                    $res = M('tranmoney')->add($data);
                    if (!$res) {
                        M()->rollback();
                        ajaxReturn('ep流水添加失败', 0);
                    }
                }
            }
        }

        //给用户升级
        $updateUserInfo['level'] = $activation_account['investment_grade'];
        $updateUserInfo['activate'] = 1;
        $updateUserInfo['activation_time'] = time();
        $res = $userModel->where(array('userid' => $activation_id))->save($updateUserInfo);
        if (!$res) {
            M()->rollback();
            ajaxReturn('升级失败', 0);
        }

        //感恩奖
        foreach ($get_touch_ids as $key => $info) {
            $direct_drive_user = $userModel->where(['pid' => $info['userid'], 'level' => ['gt', 0]])->field('userid,level')->select();
            $total_amount = 0;
            foreach ($direct_drive_user as $item) {
                $total_amount += $investment_grade[$item['level']];
            }
            $average_amount = $info['money'] * 0.1 / $total_amount;
            foreach ($direct_drive_user as $v) {
                $gratitude_amount = formatNum2($average_amount * $investment_grade[$v['level']]);
                if ($gratitude_amount > 0) {
                    $directData = ['cangku_num' => ['exp', 'cangku_num +' . $gratitude_amount]];
                    $res = M('store')->where(array('uid' => $v['userid']))->save($directData);
                    if (!$res) {
                        M()->rollback();
                        ajaxReturn('感恩奖添加失败', 0);
                    }

                    //感恩奖Ep流水
                    $directCangku_num = M('store')->where(array('uid' => $v['userid']))->getField('cangku_num');
                    $data['pay_id'] = $v['userid'];
                    $data['get_id'] = $v['userid'];
                    $data['get_nums'] = $gratitude_amount;
                    $data['now_nums_get'] = $directCangku_num;
                    $data['now_nums'] = $directCangku_num;
                    $data['is_release'] = 1;
                    $data['get_time'] = time();
                    $data['get_type'] = 40;
                    $data['remark'] = $activation_id;
                    $res = M('tranmoney')->add($data);
                    if (!$res) {
                        M()->rollback();
                        ajaxReturn('ep流水添加失败', 0);
                    }
                }
            }
        }

        M()->commit();
        ajaxReturn('激活成功', 1);

    }

    /**
     * 激活记录
     * @time 2018-12-16 20:54:35
     */
    public function activate_record()
    {
        $uid = session('userid');
        $page = I('p', 1);
        $limit = 12;
        $record_list = M('activate_card_record')->where(['uid' => $uid, 'delete_time' => '', 'type' => ['in', '1,3,4']])
            ->limit(($page - 1) * $limit, $limit)->order('id desc')->select();

        foreach ($record_list as $k => $v) {
            $record_list[$k]['create_time'] = date('Y-m-d H:i:s', strtotime($v['create_time']));
            $pid = M('user')->where(['userid' => $v['userid']])->getField('pid');
            if ($v['type'] == 3) {
                $record_list[$k]['type_name'] = 'EP激活';
            } elseif ($v['type'] == 4) {
                $record_list[$k]['type_name'] = 'EF激活';
            } else {
                if ($pid == $uid) {
                    $record_list[$k]['type_name'] = '激活卡(直推）';
                } else {
                    $record_list[$k]['type_name'] = '激活卡(非直推）';
                }
            }

            if ($v['level'] == 2) {
                $record_list[$k]['level_name'] = '二星';
            } elseif ($v['level'] == 3) {
                $record_list[$k]['level_name'] = '三星';
            } elseif ($v['level'] == 4) {
                $record_list[$k]['level_name'] = '四星';
            } else {
                $record_list[$k]['level_name'] = '一星';
            }
        }
        if (IS_AJAX) {
            if (count($record_list) >= 1) {
                ajaxReturn($record_list, 1);
            } else {
                ajaxReturn('暂无记录', 0);
            }
        }

        $this->assign('record_list', $record_list);
        $this->display();
    }

    /**
     * 赠送记录
     * @time 2018-12-16 20:54:35
     */
    public function giving_records()
    {
        $uid = session('userid');
        $page = I('p', 1);
        $limit = 12;
        $record_list = M('activate_card_record')->where(['uid' => $uid, 'delete_time' => '', 'type' => 2])
            ->limit(($page - 1) * $limit, $limit)->order('id desc')->select();

        foreach ($record_list as $k => $v) {
            $record_list[$k]['create_time'] = date('Y-m-d', strtotime($v['create_time']));
            $record_list[$k]['activation_code'] = M('activate_card')->where(['id' => $v['activate_card_id']])->getField('activation_code');
            if ($v['level'] == 2) {
                $record_list[$k]['level_name'] = '二星';
            } elseif ($v['level'] == 3) {
                $record_list[$k]['level_name'] = '三星';
            } elseif ($v['level'] == 4) {
                $record_list[$k]['level_name'] = '四星';
            } else {
                $record_list[$k]['level_name'] = '一星';
            }
        }
        if (IS_AJAX) {
            if (count($record_list) >= 1) {
                ajaxReturn($record_list, 1);
            } else {
                ajaxReturn('暂无记录', 0);
            }
        }

        $this->assign('record_list', $record_list);
        $this->display();
    }

    /**
     * 未激活列表
     */
    public function unactivated_list()
    {
        $uid = session('userid');
        $page = I('p', 1);
        $limit = 12;
        $where['pid'] = $uid;
        $where['junction_id'] = $uid;
        $where['service_center_account'] = $uid;
        $where['_logic'] = 'OR';
        $map["_complex"] = $where;
        $map['account_type'] = 2;
        $map['level'] = 0;
        $list = M('user')->where($map)->field('userid,pid,junction_id,reg_date')
            ->limit(($page - 1) * $limit, $limit)->order('userid desc')->select();

        foreach ($list as $k => $v) {
            $list[$k]['reg_date'] = date('Y-m-d H:i:s', $v['reg_date']);
            if ($v['level'] == 2) {
                $list[$k]['level_name'] = '二星';
            } elseif ($v['level'] == 3) {
                $list[$k]['level_name'] = '三星';
            } elseif ($v['level'] == 4) {
                $list[$k]['level_name'] = '四星';
            } else {
                $list[$k]['level_name'] = '一星';
            }
        }

        if (IS_AJAX) {
            if (count($list) >= 1) {
                ajaxReturn($list, 1);
            } else {
                ajaxReturn('暂无记录', 0);
            }
        }
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 获取用户信息
     */
    public function getUserInfo()
    {
        $investment_grade = UserModel::$INVESTMENT_GRADE;
        $user_id = intval(I('user_id'));
        $uid = session('userid');
        if ($user_id == $uid) {
            ajaxReturn('激活账户不能为自己', 0);
        }
        $user_info = M('user')->where(['userid' => $user_id])->field('username,level,investment_grade')->find();
        if ($user_info) {
            $card = M('activate_card')->where(['uid' => $uid, 'status' => 0, 'delete_time' => '', 'level' => $user_info['investment_grade']])
                ->field('id,activation_code,level')->limit(10)->order('id asc')->select();
            $data = [
                'username' => $user_info['username'],
                'level' => $user_info['level'],
                'investment_price' => $investment_grade[$user_info['investment_grade']],
                'card_list' => $card
            ];
            ajaxReturn($data, 1);
        } else {
            ajaxReturn('激活账户不存在请重新输入', 0);
        }
    }

    //获取数组，并倒叙
    public function getArrayjunction($arr)
    {
        $data = explode('-', $arr);
        array_shift($data);   //移除头部
        array_pop($data);      //移除尾部
        $count = count($data) - 1;
        $path = [];
        for ($i = $count; $i >= 0; $i--) {
            $path[] = $data[$i];
        }
        return $path;
    }

    //获取第一个接点人
    public function getFounder($junction_path)
    {
        $data = explode('-', $junction_path);
        array_shift($data);   //移除头部
        array_pop($data);      //移除尾部
        return array_shift($data);
    }

    //获取层封金额 层碰*100
    public function getCappinglayer($level)
    {
        $amount = 0;
        if ($level > 0) {
            $amount = ($level - 1) * 20 + 40;
        }
        return $amount * 100;
    }

    //对碰金额
    public function geTouchAmount($investment_grade_one, $investment_grade_two)
    {
        if ($investment_grade_one >= $investment_grade_two) {
            $key = $investment_grade_two;
        } else {
            $key = $investment_grade_one;
        }
        $investment_grade = UserModel::$INVESTMENT_GRADE;   //投资金级别
        return $investment_grade[$key];
    }

    //本周层碰金额
    public function getWeekLayeramount($userid)
    {
        $startTime = strtotime(date('Y-m-d', strtotime("this week Monday", time())));
        $endTime = strtotime(date('Y-m-d', strtotime("this week Sunday", time()))) + 24 * 3600 - 1;
        $where['get_id'] = $userid;
        $where['get_type'] = 38;
        $where['get_time'] = array(array('egt', $startTime), array('elt', $endTime), 'AND');
        $amount = M('tranmoney')->where($where)->sum('get_nums') ? '' : 0;
        return $amount;
    }

    /**
     * @param $junction_id
     * @param $junction_path
     * @return int
     */
    public function getLayerUser($junction_id, $junction_path)
    {
        $user_info = M('user')->where(['userid' => $junction_id])->field('level,junction_id,junction_path')->find();
        if (strpos($junction_path, $user_info['junction_path']) !== false) {
            return $user_info['junction_id'];
        } else {
            return $this->getLayerUser($user_info['junction_id'], $junction_path);
        }
    }
}