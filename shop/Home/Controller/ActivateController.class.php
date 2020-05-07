<?php
/**
 * Created by PhpStorm.
 * Date: 2018/11/30
 * Time: 22:03
 */

namespace Home\Controller;

use Common\Util\Constants;
use Home\Model\ActivateCardModel;
use Home\Model\UserModel;

class ActivateController extends CommonController
{

    /**
     * 进货确认
     * @time 2018-12-15 12:39:07
     */
    public function activate_card()
    {
        $uid = session('userid');
        $step = intval(I('step', 0));
        $where['order_sellerid'] = $uid;
        if ($step == 1) {
            $where['status'] = ['neq', 0];
        } else {
            $where['status'] = 0;
        }
        $where['shop_type'] = Constants::SHOP_TYPE_REGENERATE;
        $returnData = ActivateCardModel::search($where);
        $list = [];
        foreach ($returnData['list'] as $k => $v) {
            $list[$k] = $v;
            $userInfo = M('user')->where(['userid' => $v['uid']])->field('username,mobile')->find();
            $list[$k]['username'] = $userInfo['username'];
            $list[$k]['mobile'] = $userInfo['mobile'];
            $list[$k]['detail'] = M('order_detail')->where(['order_id' => $v['order_id']])->find();
            $list[$k]['pay_type'] = Constants::getPayWayItems($v['pay_type']);
        }

        $this->assign('list', $list);
        $this->assign('step', $step);
        $this->assign('page', $returnData['page']);
        $this->display();
    }

    /**
     * 我要进货
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
            ajaxReturn('进货成功，等待上级确认', 1);
        }
        $uid = session('userid');
        $userInfo = M('user')->where(['userid' => $uid])->field('pid,level')->find();
        $userInfo['pid_name'] = M('user')->where(['userid' => $userInfo['pid']])->getField('username');
        $userInfo['cangku_num'] = M('store')->where(['uid' => $uid])->getField('cangku_num');
        if ($userInfo['level'] == Constants::USER_LEVEL_A_FOUR) {
            $level = Constants::USER_LEVEL_A_THREE;
        } else {
            $level = $userInfo['level'];
        }
        $userInfo['price'] = M('product_detail')->where(['level' => $level])->getField('price');
        $this->assign('userInfo', $userInfo);
        $this->display();
    }

    /**
     * 我的进货
     * @time 2018-12-15 12:39:07
     */
    public function my_card()
    {
        $uid = session('userid');
        $where['uid'] = $uid;
        $where['shop_type'] = Constants::SHOP_TYPE_REGENERATE;
        $returnData = ActivateCardModel::search($where);
        $list = [];
        foreach ($returnData['list'] as $k => $v) {
            $list[$k] = $v;
            $userInfo = M('user')->where(['userid' => $v['uid']])->field('username,mobile')->find();
            $list[$k]['username'] = $userInfo['username'];
            $list[$k]['mobile'] = $userInfo['mobile'];
            $list[$k]['detail'] = M('order_detail')->where(['order_id' => $v['order_id']])->find();
            $list[$k]['pay_type'] = Constants::getPayWayItems($v['pay_type']);
        }

        $this->assign('list', $list);
        $this->assign('page', $returnData['page']);
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
     * 升级申请
     */
    public function index()
    {
        if (IS_AJAX) {
            $models = new ActivateCardModel();
            $res = $models->levelApply();
            if (!$res) {
                ajaxReturn($models->getError(), 0);
            }
            ajaxReturn('申请成功,请耐心等待', 1);
        }

        $uid = session('userid');
        $userInfo = M('user')->where(['userid' => $uid])->field('username,mobile,level')->find();
        $level = [Constants::USER_LEVEL_A_ONE, Constants::USER_LEVEL_A_TWO, Constants::USER_LEVEL_A_THREE];

        $this->assign('userInfo', $userInfo);
        $this->assign('level', $level);
        $this->display();
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

    /**
     * 确认收款
     * @author ldz
     * @time 2020/4/29 19:58
     */
    public function determinePayment()
    {
        if (IS_POST) {
            $models = new ActivateCardModel();
            $res = $models->determinePayment();
            if (!$res) {
                ajaxReturn($models->getError(), 0);
            }
            ajaxReturn('操作成功', 1, U('Activate/activate_card', array('step' => 1)));
        } else {
            ajaxReturn('请求方法有误', 0);
        }
    }

    /**
     * 申请邮寄
     * @time 2018-12-16 10:48:59
     */
    public function applyMail()
    {
        if (IS_AJAX) {
            $models = new ActivateCardModel();
            $res = $models->applyMail();
            if (!$res) {
                ajaxReturn($models->getError(), 0);
            }
            ajaxReturn('申请成功', 1);
        }
        $uid = session('userid');
        $storeInfo = M('store')->where(['uid' => $uid])->field('cloud_library')->find();

        $this->assign('storeInfo', $storeInfo);
        $this->display();
    }

    public function userList()
    {
        $step = intval(I('step', 0)) ? 1 : 0;
        $user_id = session('userid');
        if ($step == 0) {
            $userList = UserModel::getUnActivateUser($user_id);
        } else {
            $userList = M('activate_record')->where(['uid' => $user_id])->select();
            $user_name = M('user')->where(['userid' => $user_id])->getField('username');
            foreach ($userList as $k => $item) {
                $activate_user_info = M('user')->where(['userid' => $item['activate_user_id']])->field('mobile,pid,username')->find();
                $userList[$k]['mobile'] = $activate_user_info['mobile'];
                $userList[$k]['pid'] = $activate_user_info['pid'];
                $userList[$k]['username'] = $activate_user_info['username'];
                $userList[$k]['pid_username'] = $user_name;
                $userList[$k]['pid_mobile'] = M('user')->where(['userid' => $activate_user_info['pid']])->getField('mobile');
            }
        }

        $this->assign('step', $step);
        $this->assign('userList', $userList);
        $this->display('list');
    }

    /**
     * 激活用户
     * @author ldz
     * @time 2020/5/5 16:31
     */
    public function activateUser()
    {
        if (IS_AJAX) {
            $res = (new UserModel())->activateUser();
            if (!$res) {
                ajaxReturn((new UserModel())->getError(), 0);
            }
            ajaxReturn('激活成功', 1, U('Activate/userList'));
        } else {
            ajaxReturn('请求方式有误', 0);
        }
    }

    /**
     * 删除用户
     * @author ldz
     * @time 2020/5/5 16:31
     */
    public function deleteUser()
    {
        if (IS_AJAX) {
            $models = new UserModel();
            $res = $models->deleteUser();
            if (!$res) {
                ajaxReturn($models->getError(), 0);
            }
            ajaxReturn('申请成功', 1, U('Activate/userList'));
        } else {
            ajaxReturn('请求方式有误', 0);
        }
    }
}