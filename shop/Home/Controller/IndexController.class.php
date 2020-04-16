<?php

namespace Home\Controller;

use Common\Model\DealsRecordModel;
use Common\Model\UcoinsModel;
use Common\Util\Constants;
use Home\Model\StoreRecordModel;

class IndexController extends CommonController
{
    /**
     * 首页
     */
    public function index()
    {
        $user_id = session('userid');

        $storeInfo = M('store')->where(['uid' => $user_id])->find();
        $storeInfo['pass_card_amount'] = UcoinsModel::getAmount($user_id);//生态通证数量
        $coin_price = UcoinsModel::getCoinPrice(); //生态通证当前价格

        $this->assign([
            'storeInfo' => $storeInfo,
            'coin_price' => $coin_price
        ]);
        $this->display();
    }

    /*
    * 轮播私有方法链接数据库
    */
    private function get_banner()
    {
        $user_object = M('banner');
        $data_list = $user_object->order('sort')->select();
        return $data_list;
    }

    public function Dotrrela()
    {
        if (IS_AJAX) {
            $userid = session('userid');
            //是否存在当日转账释放红包
            $startime = date('Y-m-d');
            $endtime = date("Y-m-d", strtotime("+1 day"));
            $todaystime = strtotime($startime);
            $endtime = strtotime($endtime);
            $whereres['get_id'] = $userid;
            $whereres['is_release'] = 0;
            $whereres['get_type'] = 22;
            $whereres['get_time'] = array('between', array($todaystime, $endtime));
            $is_setnums = M('tranmoney')->where($whereres)->sum('get_nums') + 0;
            if ($is_setnums > 0) {
                $datapay['cangku_num'] = array('exp', 'cangku_num + ' . $is_setnums);
                $datapay['fengmi_num'] = array('exp', 'fengmi_num - ' . $is_setnums);
                $res_pay = M('store')->where(array('uid' => $userid))->save($datapay);//每日银积分释放金积分

                //添加释放记录
                $jifen_nums = $is_setnums;
                $jifen_dochange['pay_id'] = $userid;
                $jifen_dochange['get_id'] = $userid;
                $jifen_dochange['get_nums'] = $jifen_nums;
                $jifen_dochange['get_time'] = time();
                $jifen_dochange['get_type'] = 2;
                $res_addres = M('tranmoney')->add($jifen_dochange);
                //改成已释放
                $savedata['is_release'] = 1;
                $savedata['get_time'] = time();
                $is_setnums = M('tranmoney')->where($whereres)->save($savedata);
                if ($is_setnums) {
                    ajaxReturn('转账银积分释放成功', 1);
                } else {
                    ajaxReturn('转账银积分释放失败', 0);
                }
            }
        }
    }

    //转出
    public function Turnout()
    {
        if (IS_AJAX) {
            $uinfo = trim(I('uinfo'));
            //手机号码或者用户id
            $map['userid|mobile'] = $uinfo;

            $issetU = M('user')->where($map)->field('userid,username')->find();
            $userid = session('userid');

            if ($userid == $issetU['userid']) {
                ajaxReturn('您不能给自己转账哦~', 0);
            }
            if ($issetU) {
                $url = '/Index/Changeout/sid/' . $issetU['userid'];
                ajaxReturn($url, 1);
            } else {
                ajaxReturn('并不存在该用户哦~', 0);
            }
        }
        $userid = session('userid');
        $userInfo = M('user')->where(['userid' => $userid])->field('quanxian')->find();
        $quanxian = explode("-", $userInfo['quanxian']);

        $this->assign('quanxian', $quanxian);
        $this->display();
    }

    /**
     * 转出
     * @time 2018-07-25 23:24:50
     */
    public function Changeout()
    {
        $sid = trim(I('sid'));
        $type = trim(I('type'));
        $uinfo = M('user as us')->JOIN('ysk_store as ms')->where(array('us.userid' => $sid))->field('us.mobile,us.userid,us.img_head,us.username,ms.cangku_num')->find();
        if (IS_AJAX) {
            $data = $_POST['post_data'];
            $trid = trim($data['zuid']);            //转账对象的ID
            $paytype = trim($data['paytype']);      //支付方式
            $paynums = $data['paynums'];            //转账金额
            $mobila = trim($data['mobila']);        //手机号后四位
            $pwd = trim(I('pwd'));                  //交易密码
            $uid = session('userid');               //自己的ID

            if ($type == 1) {                         //1代表 消费转出  2代表 1:1转账
                if ($paynums < 1) {
                    ajaxReturn('消费转出1元起', 0);
                }
            } else {
                $info2 = $paynums % 100;
                if ($paynums < 100) {
                    ajaxReturn('不得小于100', 0);
                }
                if ($info2) {
                    ajaxReturn('请输入100的倍数', 0);
                }
            }
            //验证交易密码
            $minepwd = M('user')->where(array('userid' => $uid))->Field('account,mobile,safety_pwd,safety_salt,quanxian')->find();
            if (!empty($minepwd['quanxian'])) {
                $quanxian = explode('-', $minepwd['quanxian']);
                if (in_array(2, $quanxian)) {
                    ajaxReturn('您没有转出权限，请联系您的上级。', 0);
                }
            }

            $user_object = D('Home/User');
            $user_info = $user_object->Trans($minepwd['account'], $pwd);
            //验证手机号码后四位
            $is_setm['userid|mobile'] = $trid;
            $tmobile = M('user')->where($is_setm)->getfield('mobile');
            $tmobile = substr($tmobile, -4);
            if ($tmobile != $mobila) {
                ajaxReturn('您输入的手机号码后四位有误', 0);
            }
            if ($paynums <= 0) {
                ajaxReturn('您输入的转账金额有误哦~', 0);
            }
            if ($uid == $trid) {
                ajaxReturn('您不能给自己转账哦~', 0);
            }
            $mine_money = M('store')->where(array('uid' => $uid))->getfield('cangku_num');
            if ($mine_money < $paynums) {
                ajaxReturn('您金积分暂无这么多哦~', 0);
            }

            if ($type == 1) {                     //转帐
                $tper = $paynums * 20 / 100;
                $eper = $paynums * 80 / 100;
                $fper = $paynums * 40 / 100;

                $datapay['cangku_num'] = array('exp', 'cangku_num - ' . $paynums);
                $datapay['fengmi_num'] = array('exp', 'fengmi_num + ' . $fper);
                $datapay['fengmi_total'] = array('exp', 'fengmi_total + ' . $fper);
                $res_pay = M('store')->where(array('uid' => $uid))->save($datapay);//转出的人+80%银积分

                $baseReleaseRate = M('config')->where(['id' => 14])->getField('value');   //基础释放率

                $dataget['cangku_num'] = array('exp', "cangku_num + $eper");
                $dataget['fengmi_num'] = array('exp', 'fengmi_num + ' . $tper);
                $dataget['fengmi_total'] = array('exp', 'fengmi_total + ' . $tper);
                $today_get_amount = $tper * $baseReleaseRate * 0.01;
                $dataget['today_get_amount'] = array('exp', 'today_get_amount + ' . $today_get_amount);
                $into_user = M('store')->where(array('uid' => $trid))->find();
                if ($into_user) {
                    $res_get = M('store')->where(array('uid' => $trid))->save($dataget);//转入的人+20%银积分
                } else {
                    $dataget['uid'] = $trid;
                    $res_get = M('store')->data($dataget)->add();
                }

                $pay_ny = M('store')->where(array('uid' => $uid))->getfield('fengmi_num');
                $get_ny = M('store')->where(array('uid' => $trid))->getfield('fengmi_num');


                //转入的人+20%银积分记录SSS
                $changenums['pay_id'] = $uid;
                $changenums['get_id'] = $trid;
                $changenums['now_nums'] = $pay_ny;
                $changenums['now_nums_get'] = $get_ny;
                $changenums['get_nums'] = $tper;
                $changenums['is_release'] = 1;
                $changenums['get_time'] = time();
                $changenums['get_type'] = 1;
                M('tranmoney')->add($changenums);

                //金积分转动奖---没有触发
                $this->zhuand15($uid, $paynums);//转出方15层得到转动奖
                $this->zhuand15($trid, $eper);//转入方15层得到转动奖

                //判断用户等级
                $uChanlev = D('Home/index');
                $uChanlev->Checklevel($trid);
                //执行转账
                $pay_n = M('store')->where(array('uid' => $uid))->getfield('cangku_num');
                $get_n = M('store')->where(array('uid' => $trid))->getfield('cangku_num');

                if ($res_pay && $res_get) {
                    $data['pay_id'] = $uid;
                    $data['get_id'] = $trid;
                    $data['get_nums'] = $paynums;
                    $data['now_nums'] = $pay_n;
                    $data['now_nums_get'] = $get_n;
                    $data['is_release'] = 1;
                    $data['get_time'] = time();
                }

                $add_Dets = M('tranmoney')->add($data);

                if ($add_Dets) {
                    ajaxReturn('转账成功哦~', 1, '/Index/index');
                }
            } else {
                //1:1转帐
                $datapay['cangku_num'] = array('exp', 'cangku_num - ' . $paynums);
                $res_pay = M('store')->where(array('uid' => $uid))->save($datapay);  //转出的E派扣除

                $dataget['cangku_num'] = array('exp', "cangku_num + $paynums");

                $into_user = M('store')->where(array('uid' => $trid))->find();
                if ($into_user) {
                    $res_get = M('store')->where(array('uid' => $trid))->save($dataget);//转入的人+20%银积分
                } else {
                    $dataget['uid'] = $trid;
                    $res_get = M('store')->data($dataget)->add();
                }

                $pay_n = M('store')->where(array('uid' => $uid))->getfield('cangku_num');
                $get_n = M('store')->where(array('uid' => $trid))->getfield('cangku_num');

                //1:1转帐记录
                $changenums = [];
                $changenums['pay_id'] = $uid;
                $changenums['get_id'] = $trid;
                $changenums['now_nums'] = $pay_n;
                $changenums['now_nums_get'] = $get_n;
                $changenums['get_nums'] = $paynums;
                $changenums['is_release'] = 1;
                $changenums['get_time'] = time();
                $changenums['get_type'] = 29;
                $add_Dets = M('tranmoney')->add($changenums);
                if ($add_Dets) {
                    ajaxReturn('转账成功哦~', 1, '/Index/index');
                }
            }
        }
        $this->assign('uinfo', $uinfo);
        $this->assign('type', $type);
        $this->display();
    }

    public function get_between($input, $start, $end)
    {
        $substr = substr($input, strlen($start) + strpos($input, $start), (strlen($input) - strpos($input, $end)) * (-1));
        return $substr;
    }

    //管理奖和直推奖， 管理拿2-4代
    private function Manage_reward($uid, $paynums)
    {
        $Lasts = D('Home/index');
        $Lastinfo = $Lasts->Getlasts($uid, 15, 'path');
        if (count($Lastinfo) > 0) {
            $Manage_b = M('config')->where(array('group' => 6, 'status' => 1))->order('id asc')->select();//分享奖比例
            $Manage_a = M('config')->where(array('group' => 7, 'status' => 1))->order('id asc')->select();//管理奖比例
            foreach ($Lastinfo as $k => $v) {
                if (!empty($v)) {//当前会员信息
                    if ($k == 0) {//第一代，即为直推奖
                        $u_Grade = M('user')->where(array('userid' => $v))->getfield('use_grade');
                        $direct_fee = 0;
                        if ($u_Grade > 0) $direct_fee = (float)$Manage_b[$u_Grade - 1]["value"];//判断是什么比例
                        $zhitui_reward = $direct_fee / 100 * $paynums;//直推的人所得分享奖
                        M('user')->where(array('userid' => $v))->setInc('releas_rate', $zhitui_reward);
                    }

                    if ($k > 0 && $k <= 3) {//2-4代,拿直推的人的分享奖*相应比例，即为管理奖
                        $t = $k - 1;
                        $zhitui_num = M('user')->where(array('pid' => $v))->count(1);//计算直推人数
                        $suoxu_num = (int)$Manage_a[$t]["tip"];
                        if ($zhitui_num >= $suoxu_num) {//直推人数满足条件

                            $My_reward = $Manage_a[$t]["value"] / 100 * $zhitui_reward;
                            $res_Incrate = M('user')->where(array('userid' => $v))->setInc('releas_rate', $My_reward);
                        }
                    }
                }
            }
        }
    }

    //区块奖和VIP奖   区块拿15层
    private function Addreas15($uid, $paynums)
    {
        $Lasts = D('Home/index');
        $Lastinfo = $Lasts->Getlasts($uid, 15, 'path');
        if (count($Lastinfo) > 0) {
            $add_relinfo = M('config')->where(array('group' => 9, 'status' => 1))->order('id asc')->select();
            $vips = M('config')->where(array('group' => 10, 'status' => 1))->order('id asc')->select();
            $i = 0;
            $n = 0;
            foreach ($Lastinfo as $k => $v) {
                //查询当前自己等级
                if (!empty($v)) {
                    $zhitui_num = M('user')->where(array('pid' => $v))->count(1);//计算直推人数
                    $t = $k + 1;
                    $tkey = 0;
                    $daishu = array(3, 6, 9, 12, 15);
                    foreach ($daishu as $key1 => $value1) {
                        if ($t > $value1) $tkey = $key1 + 1;
                    }
                    $suoxu_num = (int)$add_relinfo[$tkey]["tip"];
                    if ($zhitui_num >= $suoxu_num) {//直推人数满足条件 得区块奖
                        $Lastone = $My_reward = $add_relinfo[$tkey]["value"] / 100 * $paynums;
                        $res_Incrate = M('user')->where(array('userid' => $v))->setInc('releas_rate', $Lastone);
                    }
                    //VIP奖，有集差，加速释放
                    $v_Grade = M('user')->where(array('userid' => $v))->getfield('vip_grade');
                    if (($v_Grade == 1 && $i == 0) || ($v_Grade == 2 && $i == 0)) {//VIP1奖
                        $u_get_money = $vips[0]['value'] / 100 * $paynums;
                        $res_Add = M('user')->where(array('userid' => $v))->setInc('releas_rate', $u_get_money);
                        $i++;
                    } elseif ($v_Grade == 2 && $i != 0 && $n == 0) {//VIP2奖
                        $u_get_money = $vips[1]['value'] / 100 * $paynums;
                        $res_Add = M('user')->where(array('userid' => $v))->setInc('releas_rate', $u_get_money);
                        $n++;
                    }
                }
            }
        }
    }

    //金积分转动奖  拿15层
    public function zhuand15($uid, $paynums)
    {
        $Lasts = D('Home/index');
        $Lastinfo = $Lasts->Getlasts($uid, 15, 'path');
        if (count($Lastinfo) > 0) {
            $Manage_b = M('config')->where(array('group' => 8, 'status' => 1))->order('id asc')->select();//金积分转动奖比例
            foreach ($Lastinfo as $k => $v) {
                if (!empty($v)) {//当前会员信息
                    $u_Grade = M('user')->where(array('userid' => $v))->getfield('use_grade');
                    $direct_fee = 0;
                    if ($u_Grade > 0) $direct_fee = (float)$Manage_b[$u_Grade - 1]["value"];//判断是什么比例
                    $zhuand_reward = $direct_fee / 100 * $paynums;//我得到转动奖的加速
                    M('user')->where(array('userid' => $v))->setInc('releas_rate', $zhuand_reward);
                }
            }
        }
    }

    //根据关系进行分销
    public function Doprofit($uid, $paynums, $type)
    {
        $Lasts = D('Home/index');
        $Lastinfo = $Lasts->Getlasts($uid, 15, 'path');
        //数量的多少
        if ($type == 1) {
            $paynums = $paynums * 20 / 100;
        } else {
            $paynums = $paynums;
        }
        if (count($Lastinfo) > 0) {
            $this->Addreas($uid, $Lastinfo, $paynums, $type);//加速银积分释放
        }
    }

    //加速银积分释放【银积分基础释放， 1代银积分加速释放，2-15代，2代vip ，2-15代vip银积分】
    private function Addreas($uid, $Lastinfo, $paynums, $type)
    {
        //银积分加速释放率
        $add_relinfo = M('config')->where(array('group' => 4, 'status' => 1))->order('id asc')->select();
        $i = 0;
        foreach ($Lastinfo as $k => $v) {
            $k = $k + 1;
            //查询当前自己等级
            if (!empty($v)) {
                //当前会员信息 等级字段
                $u_Grade = M('user')->where(array('userid' => $v))->getfield('use_grade');
                if ($u_Grade >= 1) {
                    if ($k == 1) {
                        $release_bili = $add_relinfo[1]['value'];
                    } else {
                        $release_bili = $add_relinfo[2]['value'];
                    }
                    $Lastone = $release_bili / 100 * $paynums;
                    //加速银积分释放
                    $res_Incrate = M('user')->where(array('userid' => $v))->setInc('releas_rate', $Lastone);
                    //增加银积分
                    $u_get_money = $add_relinfo[3]['value'] / 100 * $paynums;
                    if ($u_Grade == 3 && $i == 0) {
                        $res_Add = M('store')->where(array('uid' => $v))->setInc('fengmi_num', $u_get_money);//给上级会员加银积分
                        if ($res_Add) {
                            $earns['pay_id'] = $uid;
                            $earns['get_id'] = $v;
                            $earns['get_nums'] = $u_get_money;
                            $earns['get_level'] = $k;
                            $earns['get_types'] = $type;
                            $earns['get_time'] = time();
                            $res_Earn = M('moneyils')->add($earns);
                            $i++;
                        }
                    }
                }
            }//if
        }//foreach
    }

    //转出记录
    public function Outrecords()
    {
        $traInfo = M('tranmoney');
        $uid = session('userid');
        $where['pay_id|get_id'] = $uid;
        $where['get_type'] = 0;
        //分页
        $p = getpage($traInfo, $where, 50);
        $page = $p->show();
        $Chan_info = $traInfo->where($where)->order('id desc')->select();
        foreach ($Chan_info as $k => $v) {
            $Chan_info[$k]['get_timeymd'] = toDate($v['get_time'], 'Y-m-d');
            $Chan_info[$k]['get_timedate'] = toDate($v['get_time'], 'H:i:s');
            //转入转出
            if ($uid == $v['pay_id']) {
                $Chan_info[$k]['trtype'] = 1;
            } else {
                $Chan_info[$k]['trtype'] = 2;
            }
        }
        if (IS_AJAX) {
            if (count($Chan_info) >= 1) {
                ajaxReturn($Chan_info, 1);
            } else {
                ajaxReturn('暂无记录', 0);
            }
        }
        $this->assign('page', $page);
        $this->assign('Chan_info', $Chan_info);
        $this->assign('uid', $uid);
        $this->display();
    }

    //获取仓库数据
    public function StoreData()
    {
        if (!IS_AJAX) {
            return false;
        }
        $store = D('Store');
        $userid = get_userid();
        $where['uid'] = $userid;
        $s_info = $store->field('cangku_num,fengmi_num,plant_num,huafei_total')->where($where)->find();
        $data['cangku'] = $s_info['cangku_num'] + 0;
        $data['plant'] = $s_info['plant_num'] + 0;
        $this->ajaxReturn($data);
    }

    //果树数据
    public function landdata()
    {
        if (!IS_AJAX) {
            return false;
        }
        $table = M('nzusfarm');
        $uid = session('userid');
        $where['uid'] = $uid;
        $where['status'] = array('gt', 0);
        $info = $table->field('id,seeds+fruits as num,farm_type type,status')->where($where)->order('id')->select();
        if ($info) {
            $this->ajaxReturn($info);
        }

    }

    public function tooldata()
    {
        if (!IS_AJAX) {
            return false;
        }
        $tree = M('config')->where(array('id' => array('in', array(8, 10, 12, 36))))->order('id asc')->field('value as price,id')->select();
        $tool = M('tool')->field('t_num as price,id')->order('id asc')->select();
        $data = array_merge($tree, $tool);
        if (empty($data)) {
            ajaxReturn('数据加载失败');
        } else {
            ajaxReturn('数据加载成功', 1, '', $data);
        }
    }

    //一键采蜜和狗粮
    public function onefooddata()
    {
        if (!IS_AJAX) {
            return false;
        }
        $where['uid'] = session('userid');
        $data = M('user_tool_month')->field('oneclick one,end_oneclick_time endo,dogfood food,end_dogfood_time endf')->where($where)->find();
        if (empty($data)) {
            ajaxReturn(null);
        } else {
            $time = time();
            if ($data['one'] > 0) {
                if ($time > $data['endo'])
                    $data['one_status'] = '已过期';
                else
                    $data['one_status'] = '使用中';

                $data['endo1'] = toDate($data['endo'], 'Y-m-d');
            }
            if ($data['food'] > 0) {
                if ($time > $data['endf'])
                    $data['food_status'] = '已过期';
                else
                    $data['food_status'] = '使用中';

                $data['endf1'] = toDate($data['endf'], 'Y-m-d');
            }
            ajaxReturn('数据加载成功', 1, '', $data);
        }
    }

    /**
     * 站内信
     */
    public function znx()
    {
        if (IS_AJAX) {
            $db_letter = M('nzletter');
            $userid = session('userid');
            $userInfo = session('user_login');
            $data['recipient_id'] = 0;
            $data['send_id'] = $userid;
            $data['title'] = trim(I('post.title'));
            $data['content'] = trim(I('post.content'));
            $data['username'] = $userInfo['username'];
            $data['account'] = $userInfo['account'];
            if (empty($data['title']) || empty($data['content'])) {
                ajaxReturn('标题或内容不能为空');
                return;
            }
            $data['time'] = time();
            $res = $db_letter->data($data)->add();
            if ($res) {
                ajaxReturn('我们已收到，会尽快处理您的问题', 1);
            } else {
                ajaxReturn('提交失败');
            }
        }
    }

    //购买道具
    public function buytool()
    {
        if (!IS_AJAX) {
            return false;
        }
        $id = I('post.id', 0, 'intval');
        $num = I('post.num', 1, 'intval');
        $typetree = I('post.type');
        if (empty($id)) {
            ajaxReturn('参数错误');
        }

        $uid = session('userid');
        if ($typetree == 'tree') {

            if ($id == 8 || $id == 36) {
                $type = 1;
            } elseif ($id == 10) {
                $type = 2;
            } elseif ($id == 12) {
                $type = 3;
            } else {
                ajaxReturn("操作失败");
            }
            //农田里最低的果子数
            $config = D('config');
            $min_guozi = $config->where(array('id' => $id))->getField('value');

            $des_num = $min_guozi;
            $is_land = no_land();
            if ($is_land && $id != 36) {
                $des_num = $des_num + 30;
            }

            $t_info['t_num'] = $des_num;
            $t_info['t_name'] = '树';
            $t_info['t_img'] = '';
            $num = 1;
            $order_type = 1; //树
        } else {

            $t_info = M('tool')->find($id);
            if (empty($t_info)) {
                ajaxReturn('参数错误');
            }

            //判断是否已拥有此道具，如果已拥有，不在购买
            $type = $t_info['t_type'];
            if ($type == 2) {
                $field = $t_info['t_fieldname'];
                $isbuytool = M('user_level')->where(array('uid' => $uid))->getField($field);
                if ($isbuytool > 0) {
                    ajaxReturn('您已经拥有该宠物了哦！');
                }
            }
            $order_type = 0; //道具
        }

        $data['tool_id'] = $id;
        $data['tool_name'] = $t_info['t_name'];
        $data['tool_price'] = $t_info['t_num'];
        $data['tool_img'] = $t_info['t_img'];
        $data['order_status'] = 0;
        $data['order_no'] = date('YmdHis');
        $data['tool_num'] = $num;
        $data['total_price'] = $num * $t_info['t_num'];
        $data['uid'] = $uid;
        $data['order_type'] = $order_type;

        $order = M('order');
        $order->startTrans();
        $res = $order->add($data);
        if ($res) {
            $url = U('Index/orderdetail', array('order_no' => $data['order_no']));
            ajaxReturn('购买成功', 1, $url);
        } else {
            ajaxReturn('购买失败');
            $order->startTrans();
        }
    }

    //选择支付
    public function orderdetail()
    {
        $order_no = I('order_no');
        $order_no = safe_replace($order_no);
        if (empty($order_no)) {
            return false;
        }
        $where['order_no'] = $order_no;
        $where['order_status'] = 0;
        $order = M('order');
        $o_info = $order->where($where)->find();
        if (empty($o_info)) {
            return false;
        }
        $uid = session('userid');
        $cangku_num = M('store')->where(array('uid' => $uid))->getField('cangku_num');
        $this->assign('o_info', $o_info)->assign('cangku_num', $cangku_num)->display();
    }

    public function gopay()
    {
        if (!IS_POST) {
            return false;
        }
        $order_paytype = I('post.paytype');
        $type_arr = array(1, 2, 3);
        if (!in_array($order_paytype, $type_arr)) {
            ajaxReturn('请选择支付方式');
        }
        $order_no = I('post.order_no');
        $order_no = safe_replace($order_no);
        if (empty($order_no)) {
            ajaxReturn('订单不存在');
        }
        $where['order_no'] = $order_no;
        $where['order_status'] = 0;
        $order = M('order');
        $count = $order->where($where)->count(1);
        if ($count == 0) {
            ajaxReturn('该订单已失效，请重新下单');
        }

        $arr = array(1 => '微信支付', 2 => '支付宝支付', 3 => '果子支付');
        $res = $order->where($where)->setField('order_paytype', $arr[$order_paytype]);
        $wxurl = 'http://yxgsgy.com/wxPay/example/jsapi.php?order_no=' . $order_no;
        $arr_url = array(1 => $wxurl, 2 => '', 3 => U('Ajaxdz/kaiken'));
        if ($res === false) {
            ajaxReturn('下单失败');
        } else {
            ajaxReturn('', 1, $arr_url[$order_paytype]);
        }
    }

    public function DogEat()
    {
        $uid = session('userid');
        $eat = M('user_dogeat');
        $pcount = $eat->where(array('uid' => $uid, 'datestr' => date('Ymd')))->count(1);
        if ($pcount > 0) {
            ajaxReturn('今天已经喂食过了哦');
        }

        $where['uid'] = $uid;
        $dog = M('user_level')->where($where)->getField('zangao_num');
        if ($dog) {
            //判断是否过期
            $table = M('user_tool_month');
            $where['dogfood'] = array('gt', 0);
            $info = $table->where($where)->field('dogfood,end_dogfood_time')->find();
            if (empty($info)) {
                ajaxReturn('您还没狗粮哦，赶紧去购买吧');
            }
            $time = time();
            if ($info['end_dogfood_time'] < $time) {
                ajaxReturn('狗粮没有了，赶紧去购买吧');
            }

            $eat = M('user_dogeat');
            $count = $eat->where(array('uid' => $uid))->count(1);
            $data['uid'] = $uid;
            if ($count == 0) {
                $eat->add($data);
            }
            $data['datestr'] = date('Ymd');
            $data['create_time'] = time();
            $res = $eat->where(array('uid' => $uid))->save($data);
            if ($res)
                ajaxReturn('喂食成功！', 1);
            else
                ajaxReturn('喂食失败！');
        }
    }

    /**
     * 生态总资产流水记录
     */
    public function Bancerecord()
    {
        $uid = session('userid');
        $where['pay_id|get_id'] = $uid;
        $where['get_type'] = ['in', '0,2,4,6,8,10,12,14,16,18,20,22,24,26,28,30'];
        $where['is_release'] = 1;
        //分页
        $page = I('p', 1);
        $limit = 12;
        $Chan_info = M('tranmoney')->where($where)->limit(($page - 1) * $limit, $limit)
            ->field('pay_id,get_id,get_time,get_type,get_nums,remark')->order('id desc')->select();

        foreach ($Chan_info as $k => $v) {
            $Chan_info[$k]['get_time'] = toDate($v['get_time']);

            $remark = $v['remark'] ? '(' . $v['remark'] . ')' : '';
            switch ($v['get_type']) {
                case '0':
                    $Chan_info[$k]['type_name'] = '资产卖出';
                    break;
                case '2':
                    $Chan_info[$k]['type_name'] = '平台操作';
                    break;
                case '4':
                    $Chan_info[$k]['type_name'] = '资产买入';
                    break;
                case '6':
                    $Chan_info[$k]['type_name'] = '激活二维码' . $remark;
                    break;
                case '8':
                    $Chan_info[$k]['type_name'] = '流动宝箱' . $remark;
                    break;
                case 10:
                    $Chan_info[$k]['type_name'] = '兑换';
                    break;
                case 12:
                    $Chan_info[$k]['type_name'] = '转账' . $remark;
                    break;
                case 14:
                    $Chan_info[$k]['type_name'] = '兑换资产';
                    break;
                case 16:
                    $Chan_info[$k]['type_name'] = '购物';
                    break;
                case 18:
                    $Chan_info[$k]['type_name'] = '提现';
                    break;
                case 20:
                    $Chan_info[$k]['type_name'] = '提现失败';
                    break;
                case 22:
                    $Chan_info[$k]['type_name'] = '分享宝箱';
                    break;
                case 24:
                    $Chan_info[$k]['type_name'] = '绩效宝箱';
                    break;
                case 26:
                    $Chan_info[$k]['type_name'] = '感恩宝箱';
                    break;
                case 28:
                    $Chan_info[$k]['type_name'] = '回馈宝箱';
                    break;
                case 30:
                    $Chan_info[$k]['type_name'] = '实体消费' . $remark;
                    break;
                default:
                    $Chan_info[$k]['type_name'] = '';
                    break;
            }
        }

        if (IS_AJAX) {
            if (count($Chan_info) >= 1) {
                ajaxReturn($Chan_info, 1);
            } else {
                ajaxReturn('暂无记录', 0);
            }
        }
        $this->assign('Chan_info', $Chan_info);
        $this->display();
    }

    /**
     * 消费通证记录
     * @time 2018-07-25 23:25:17
     */
    public function Turncords()
    {
        $traInfo = M('tranmoney');
        $uid = session('userid');
        $where['pay_id'] = $uid;
        $where['get_type'] = ['in', '1,3,5,7,9,11'];

        $page = I('p', 1);
        $limit = 12;

        $Chan_info = $traInfo->where($where)->limit(($page - 1) * $limit, $limit)->order('id desc')->select();
        foreach ($Chan_info as $k => $v) {
            $Chan_info[$k]['get_time'] = toDate($v['get_time']);
            $remark = $v['remark'] ? '(' . $v['remark'] . ')' : '';
            switch ($v['get_type']) {
                case 1:
                    $Chan_info[$k]['type_name'] = '资产卖出';
                    break;
                case 3:
                    $Chan_info[$k]['type_name'] = '平台操作';
                    break;
                case 5:
                    $Chan_info[$k]['type_name'] = '资产买入';
                    break;
                case 7:
                    $Chan_info[$k]['type_name'] = '购物';
                    break;
                case 9:
                    $Chan_info[$k]['type_name'] = '兑换';
                    break;
                case 11:
                    $Chan_info[$k]['type_name'] = '转账' . $remark;
                    break;
                default:
                    $Chan_info[$k]['type_name'] = '';
                    break;
            }
        }
        if (IS_AJAX) {
            if (count($Chan_info) >= 1) {
                ajaxReturn($Chan_info, 1);
            } else {
                ajaxReturn('暂无记录', 0);
            }
        }
        $this->assign('Chan_info', $Chan_info);
        $this->display();
    }

    /**
     * 手续费记录
     */
    public function ComplexRecord()
    {
        $uid = session('userid');
        $page = I('p', 1);
        $limit = 12;
        $record_list = M('complex_record')->where(['uid' => $uid])
            ->limit(($page - 1) * $limit, $limit)->order('id desc')->select();

        foreach ($record_list as $k => $v) {
            $record_list[$k]['create_time'] = toDate(strtotime($v['create_time']));
            switch ($v['type']) {
                case 1:
                    $record_list[$k]['type_name'] = '卖出QBB';
                    break;
                default:
                    $record_list[$k]['type_name'] = '';
                    break;
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
     * 不可用QBB记录
     */
    public function charityScoreRecord()
    {
        $uid = session('userid');
        $page = I('p', 1);
        $limit = 12;
        $record_list = M('charity_score_record')->where(['uid' => $uid])
            ->limit(($page - 1) * $limit, $limit)->order('id desc')->select();

        foreach ($record_list as $k => $v) {
            $record_list[$k]['create_time'] = toDate(strtotime($v['create_time']));
            switch ($v['type']) {
                case 1:
                    $record_list[$k]['type_name'] = '注册';
                    break;
                case 2:
                    $record_list[$k]['type_name'] = '买入QBB';
                    break;
                case 3:
                    $record_list[$k]['type_name'] = '推荐奖';
                    break;
                default:
                    $record_list[$k]['type_name'] = '';
                    break;
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
     * 签到账户记录
     */
    public function PurchaseRecord()
    {
        $uid = session('userid');
        $page = I('p', 1);
        $limit = 12;
        $record_list = M('purchase_record')->where(['uid' => $uid])
            ->limit(($page - 1) * $limit, $limit)->order('id desc')->select();

        foreach ($record_list as $k => $v) {
            $record_list[$k]['create_time'] = toDate(strtotime($v['create_time']));
            $remark = $v['remark'] ? '(' . $v['remark'] . ')' : '';
            switch ($v['type']) {
                case 1:
                    $record_list[$k]['type_name'] = '签到';
                    break;
                case 2:
                    $record_list[$k]['type_name'] = '未签清零';
                    break;
                case 3:
                    $record_list[$k]['type_name'] = '平台操作';
                    break;
                case 4:
                    $record_list[$k]['type_name'] = '直推释放' . $remark;
                    break;
                case 5:
                    $record_list[$k]['type_name'] = '自营释放' . $remark;
                    break;
                default :
                    $record_list[$k]['type_name'] = '';
            }
        }
        if (IS_AJAX) {
            if (count($record_list) >= 1) {
                ajaxReturn($record_list, 1);
            } else {
                ajaxReturn('暂无记录', 0);
            }
        }
        $this->assign('today_sign_num',today_sign_num($uid));
        $this->assign('record_list', $record_list);
        $this->display();
    }

    /**
     * 生态通证明细
     */
    public function ucoinsRecord()
    {
        $uid = session('userid');
        $page = I('p', 1);
        $limit = 12;
        $record_list = M('ucoins_record')->where(['uid' => $uid, 'cid' => 1])
            ->limit(($page - 1) * $limit, $limit)->order('id desc')->select();

        foreach ($record_list as $k => $v) {
            $record_list[$k]['create_time'] = toDate(strtotime($v['create_time']), 'Y-m-d');
            switch ($v['type']) {
                case 1:
                    $record_list[$k]['type_name'] = '流动通证释放';
                    break;
                case 2:
                    $record_list[$k]['type_name'] = '兑换资产';
                    break;
                case 3:
                    $record_list[$k]['type_name'] = '平台操作';
                    break;
                default :
                    $record_list[$k]['type_name'] = '';
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
     * 可用QBB记录
     */
    public function dealsRecord()
    {
        if (empty(DealsRecordModel::getTodayInfo())) {
            M('deals_record')->add(['create_time' => strtotime(date('Y-m-d'))]);
        }

        $page = I('p', 1);
        $limit = 12;
        $record_list = M('deals_record')->limit(($page - 1) * $limit, $limit)->order('create_time desc')->select();
        $type = trim(I('type', 'complete'));
        foreach ($record_list as $k => $v) {
            $record_list[$k]['create_time'] = toDate($v['create_time'], 'Y-m-d');
            if ($type == 'complete') {
                $record_list[$k]['num'] = $v['deal_num'];
            } else if ($type == 'sell') {
                $record_list[$k]['num'] = $v['sell_num'];
            } else {
                $record_list[$k]['num'] = $v['buy_num'];
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
     * 钱包明细
     * @throws \Think\Exception
     */
    public function storeRecord()
    {
        $params['store_type'] = intval(I('store_type', Constants::STORE_TYPE_SHARE_REWARD));
        $recordList = StoreRecordModel::search($params);

        if (IS_AJAX) {
            if (count($recordList) >= 1) {
                ajaxReturn($recordList, 1);
            } else {
                ajaxReturn('暂无记录', 0);
            }
        }

        $title = Constants::getStoreTypeItems($params['store_type']) . '明细';
        $this->assign('title', $title);
        $this->assign('url', U('Index/storeRecord'));
        $this->assign('recordList', $recordList);
        $this->display('storeRecord');
    }
}