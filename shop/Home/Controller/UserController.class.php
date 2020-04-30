<?php

namespace Home\Controller;

use Home\Model\UserModel;
use Lib\Image;

class UserController extends CommonController
{

    public function Imgup()
    {
        $uid = session('userid');
        $picname = $_FILES['uploadfile']['name'];
        $picsize = $_FILES['uploadfile']['size'];
        if ($uid != "") {
            if ($picsize > 2014000) { //限制上传大小
                ajaxReturn('图片大小不能超过2M', 0);
            }
            $type = strstr($picname, '.'); //限制上传格式
            if ($type != ".gif" && $type != ".jpg" && $type != ".png" && $type != ".jpeg") {
                ajaxReturn('图片格式不对', 0);
            }
            $rand = rand(100, 999);
            $pics = uniqid() . $type; //命名图片名称
            //上传路径
            $pic_path = "./Public/home/wap/heads/" . $pics;
            move_uploaded_file($_FILES['uploadfile']['tmp_name'], $pic_path);
        }
        $size = round($picsize / 1024, 2); //转换成kb
        $pic_path = trim($pic_path, '.');
        if ($size) {
            $res = M('user')->where(array('userid' => $uid))->setField('img_head', $pics);
            ajaxReturn($pic_path, 1);
        }
    }

    public function imgUps()
    {
        if (IS_AJAX) {
            $uid = session('userid');
            $dataflow = trim(I('dataflow'));
            $base64 = str_replace('data:image/jpeg;base64,', '', $dataflow);
            $img = base64_decode($base64);
            //保存地址
            $imgDir = './Public/home/wap/heads/';
            //要生成的图片名字
            $filename = md5(time() . mt_rand(10, 99)) . ".png"; //新图片名称
            $newFilePath = $imgDir . $filename;
            $res = file_put_contents($newFilePath, $img);//返回的是字节数
            if ($res > 1000) {
                //修改头像
                $res_change = M('user')->where(array('userid' => $uid))->setField('img_head', $filename);
                if ($res_change) {
                    ajaxReturn('头像修改成功', 1);
                } else {
                    ajaxReturn('头像修改失败', 0);
                }
            } else {
                ajaxReturn('头像修改失败', 0);
            }
        }
    }

    /**
     * 修改真实姓名
     */
    public function Setuname()
    {
        if (IS_AJAX) {
            $model = new UserModel();
            $res = $model->updateUserInfo();
            if (!$res) {
                ajaxReturn($model->getError(), 0);
            }
            ajaxReturn('修改成功', 1);
        }
        $uid = session('userid');
        $userInfo = M('user')->where(array('userid' => $uid))->field('username,mobile,identity_card,reg_date')->find();
        $this->assign('userInfo', $userInfo);
        $this->display();
    }

    /**
     * 安全设置
     */
    public function Setpwd()
    {
        $uid = session('userid');
        if (IS_POST) {
            $login_pwd = trim(I('login_pwd'));
            $safety_pwd = trim(I('safety_pwd'));
            $email = I('post.email');
            $code = trim(I('post.code'));
            if ($login_pwd == '') {
                ajaxReturn('新密码不能为空哦', 0);
            }
            if (empty($code)) {
                ajaxReturn('验证码不能为空');
            }
//            if(!check_email($code,$email,'setpwd_code_'.$email)){
//                ajaxReturn('验证码错误或已过期');
//            }
            $mobile = M('user')->where(['userid' => $uid])->getField('mobile');
            if (!check_sms($code, $mobile)) {
                ajaxReturn('验证码错误或已过期');
            }

            $user = D('Home/User');
            $salt = substr(md5(time()), 0, 3);
            $data['login_pwd'] = $user->pwdMd5($login_pwd, $salt);
            $data['login_salt'] = $salt;
            if ($safety_pwd != '') {
                $data['safety_pwd'] = $user->pwdMd5($safety_pwd, $salt);
                $data['safety_salt'] = $salt;
            }

            $res_Sapwd = M('user')->where(array('userid' => $uid))->save($data);
            if ($res_Sapwd) {
                ajaxReturn('密码修改成功', 1, U('Shop/member/mine'));
            } else {
                ajaxReturn('密码修改失败', 0);
            }
        }
        $userInfo = M('user')->where(array('userid' => $uid))->field('mobile')->find();
        $this->assign('userInfo', $userInfo);
        $this->display();
    }

    /**
     * 公告
     */
    public function News()
    {
        $newInfo = M('news')->order('id desc')->limit(50)->select();
        $this->assign('newinfo', $newInfo);
        $this->display();
    }

    /**
     * 公告详情
     */
    public function Newsdetail()
    {
        $nid = I('nid', 'intval', 0);
        $newDetails = M('news')->where(array('id' => $nid))->find();
        $this->assign('newdets', $newDetails);
        $this->display();
    }

    /**
     * 个人二维码
     */
    public function Sharecode()
    {
        $user_id = session('userid');
        $u_ID = $user_id;
        $dPath = './Uploads/Scode';
        $images = 'codes' . $user_id . '.png';
        $url = './Uploads/Scode/' . $images;
        if (!file_exists($dPath . '/' . $images)) {
            sp_dir_create($dPath);
            vendor("phpqrcode.phpqrcode");
            $phpqrcode = new \QRcode();
            $hurl = "http://" . $_SERVER['SERVER_NAME'] . U('Login/register/mobile/' . $u_ID);
            $size = "7";
            $errorLevel = "L";
            $phpqrcode->png($hurl, $dPath . '/' . $images, $errorLevel, $size);
            $phpqrcode->scerweima1($hurl, $url, $hurl);
        }
        $aurl = "http://" . $_SERVER['SERVER_NAME'] . U('Login/register/mobile/' . $u_ID);

        $this->urel = ltrim($url, ".");
        $this->aurl = $aurl;
        $this->display();
    }

    /**
     * [Friends 我的好友]
     */
    public function FriendsData()
    {
        $userid = session('userid');
        $where['pid'] = $userid;
        $where['gid'] = $userid;
        $where['ggid'] = $userid;
        $where['_logic'] = 'or';
        if (IS_AJAX) {
            $p = I('p', '0', 'intval');
            $page = $p * 10;
            $u_info = M('user a')->join('ysk_user_huafei b ON a.userid=b.uid')->field('username as u_name,account as u_zh,pid as u_fuji,gid as u_yeji,ggid as u_yyj,pid_caimi,gid_caimi,ggid_caimi,datestr,uid')->where($where)->limit($page, 10)->order('userid')->select();

            if (empty($u_info)) {
                $u_info = null;
            }

            $this->ajaxReturn($u_info);
        }
    }

    /**
     * 修改密码
     */
    public function updatepassword()
    {
        if (!IS_AJAX)
            return;

        $password_old = I('post.old_pwdt');
        $password = I('post.new_pwd');
        $passwordr = I('post.rep_pwd');
        $two_password = I('post.new_pwdt');
        $two_passwordr = I('post.rep_pwdt');
        if (empty($password_old)) {
            ajaxReturn('请输入登录密码');
            return;
        }
        if ($password != $passwordr) {
            ajaxReturn('两次输入登录密码不一致');
            return;
        }

        if ($two_password != $two_passwordr) {
            ajaxReturn('两次输入交易密码不一致');
        }

        $user = D('User');
        $user->startTrans();
        //验证旧密码
        if (!$user->check_pwd_one($password_old)) {
            ajaxReturn('旧登录密码错误');
        }

        //=============登录密码加密==============
        if ($password) {
            $salt = substr(md5(time()), 0, 3);
            $data['login_salt'] = $salt;
            $data['login_pwd'] = md5(md5(trim($password)) . $salt);
        }

        //=============安全密码加密==============
        if ($two_password) {
            $two_salt = substr(md5(time()), 0, 3);
            $data['safety_salt'] = $two_salt;
            $data['safety_pwd'] = $two_password = md5(md5(trim($two_passwordr)) . $two_salt);
        }
        if (empty($data)) {
            ajaxReturn("请输入要修改的密码");
        }
        $userid = session('userid');
        $where['userid'] = $userid;
        $res = $user->where($where)->save($data);

        if ($res) {
            $user->commit();
            ajaxReturn("修改成功", 1);
        } else {
            $user->rollback();
            ajaxReturn("修改失败");
        }

    }

    /**
     * 投诉建议
     */
    public function Complaint()
    {
        $uid = session('userid');
        if (IS_POST) {
            $content = I('post.content');
            $data['content'] = $content;
            $data['user_id'] = $uid;
            $data['create_time'] = time();
            $Complaint = M('complaint');
            $result = $Complaint->add($data);
            if ($result) {
                ajaxReturn("提交成功", 1, U('User/Complaint'));
            } else {
                ajaxReturn("提交失败");
            }
            exit;
        }
        $this->display();
    }

    /**
     * 投诉列表
     */
    public function ComplaintList()
    {
        $uid = session('userid');
        $Complaint = M('complaint');

        $where['user_id'] = $uid;
        //分页
        $p = getpage($Complaint, $where, 10);
        $page = $p->show();

        $list = $Complaint->where($where)->order('id desc')->select();
        $this->assign('page', $page);
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 市场
     * @author ldz
     * @time 2020/2/4 15:42
     */
    public function Found()
    {
        $uid = session('userid');
        $storeInfo = M('store')->where(['uid' => $uid])->field('total_amount,total_num,month_num')->find();

        $this->assign('systemMsgNum', 0);
        $this->assign('storeInfo', $storeInfo);
        $this->assign('methods', 'found');
        $this->display();
    }

    //我的好友
    public function Friends()
    {
        //查询我的会员
        $uid = session('userid');
        $uinfo = '';
        if (IS_POST) {
            $uinfo = trim(I('uinfo'));
            if (!empty($uinfo) && $uinfo != '') {
                $where['userid|mobile'] = array('like', '%' . $uinfo . '%');
            }
        }
        $where['pid'] = $uid;
        $muinfo = M('user')->where($where)->order('userid desc')->select();
        for ($i = 0; $i < count($muinfo); $i++) {
            $moneyinfo = M('store')->where(array('uid' => $muinfo[$i]['userid']))->field('cangku_num,fengmi_num')->find();
            $muinfo[$i]['powerValue'] = $moneyinfo['fengmi_num'] ? sprintf('%.2f', $moneyinfo['fengmi_num']) : 0.00;
        }

        $this->assign('uid', $uid);
        $this->assign('muinfo', $muinfo);
        $this->assign('uinfo', $uinfo);
        $this->display();
    }

    public function findPid($user, $pid)
    {
        if ($user['pid'] == 0) {
            return false;
        } elseif ($user['pid'] == $pid) {
            return true;
        } elseif ($user['gid'] == 0) {
            return false;
        } elseif ($user['gid'] == $pid) {
            return true;
        } elseif ($user['ggid'] == 0) {
            return false;
        } elseif ($user['ggid'] == $pid) {
            return true;
        } else {
            $nowUser = M('user')->where(['userid' => $user['ggid']])->field('userid,pid,gid,ggid')->find();
            $this->findPid($nowUser, $pid);
        }
    }

    //退出登录
    public function Loginout()
    {
        session_destroy();
        M('config')->where(['name' => 'online_number'])->setDec('value');
        $this->redirect('Login/login');
    }

    /**
     * 系统消息-列表
     * @time 2018-10-2 16:33:06
     */
    public function SystemMsg()
    {
        $uid = session('userid');
        $systemMsgInfo = M('system_msg')->where(['user_id' => $uid])->order('id desc')->limit(15)->select();
        $this->assign('systemMsgInfo', $systemMsgInfo);
        $this->display();
    }

    /**
     * 系统消息详情
     * @time 2018-10-02 16:41:19
     */
    public function SystemMsgdetail()
    {
        $nid = I('nid', 'intval', 0);
        $systemMsgInfo = M('system_msg')->where(array('id' => $nid))->field('id,title,content')->find();

        //更新已读状态
        $data['is_read'] = 1;
        M('system_msg')->where(['id' => $nid])->save($data);

        $this->assign('systemMsgInfo', $systemMsgInfo);
        $this->display();
    }

    /**
     * 网路图谱
     */
    public function map()
    {
        if (IS_AJAX) {
            $userModel = new UserModel();
            $data = $userModel->map();
            ajaxReturn($data, 1);
        }
        $user_id = session('userid');
        $this->assign('user_id', $user_id);
        $this->display();
    }

    /**
     * 获取上级id
     */
    public function getSuperiorid()
    {
        $id = intval(I('id'));
        $type = trim(I('type'));
        $is_admin = I('is_admin', '');
        if ($is_admin) {
            $user_id = M('user')->where(['junction_id' => 0])->getField('userid');
        } else {
            $user_id = session('userid');
        }
        if (!$id) {
            ajaxReturn('参数错误,请重试', 0);
        }
        if ($id == $user_id) {
            ajaxReturn('已经返回到第一代了', 0);
        }
        if ($type == 'founder') {
            ajaxReturn($user_id, 1);
        } else {
            $superior_id = M('user')->where(['userid' => $id])->getField('junction_id');
            if (!$superior_id) {
                ajaxReturn('已经返回到第一代了', 0);
            }
            ajaxReturn($superior_id, 1);
        }
    }

    /**
     * 获取服务中心账号
     */
    public function getServiceCenterAccount()
    {
        $referral_id = intval(I('referral_id'));
        if (!$referral_id) {
            ajaxReturn('参数错误', 0);
        }
        $user = M('user')->where(['userid' => $referral_id])->field('service_center,service_center_account')->find();
        if (!$user) {
            ajaxReturn('推荐人不存在，请重试', 0);
        }
        if ($user['service_center']) {
            $service_center_account = $referral_id;
        } else {
            $service_center_account = $user['service_center_account'];
        }
        ajaxReturn($service_center_account, 1);
    }

    /**
     * 获取用户等级
     */
    public function is_activation()
    {
        $user_id = intval(I('user_id'));
        $level = M('user')->where(['userid' => $user_id])->getField('level');
        ajaxReturn($level, 1);
    }

    /**
     * 修改未激活用户信息
     */
    public function changeUserInfo()
    {
        if (IS_AJAX) {
            $models = new UserModel();
            $res = $models->changeUserInfo();
            if (!$res) {
                ajaxReturn($models->getError(), 0);
            }
            ajaxReturn('修改成功', 1, '/activate/unactivated_list');
        }

        $userid = session('userid');

        $change_id = intval(I('id'));
        $userInfo = M('user')->where(['userid' => $change_id])->find();
        $list = [];
        if ($userInfo['pid'] == $userid || $userInfo['junction_id'] == $userid || $userInfo['service_center_account'] == $userid) {
            $list = $userInfo;
        }
        //投资级别
        $investment_grade = UserModel::$INVESTMENT_GRADE;
        $this->assign('investment_grade', $investment_grade);
        $this->assign('userInfo', $list);
        $this->display();
    }

    /**
     * 关于BAT
     * @time 2019-2-23 15:23:09
     */
    public function about()
    {
        $version = M('version_announcement')->where(['delete_time' => ''])->order("create_time desc")->find();

        $this->assign('version', $version);
        $this->display();
    }

    /**
     * 历史版本列表
     * @time 2019-2-23 15:23:24
     */
    public function versionHistory()
    {
        $version = M('version_announcement')->where(['delete_time' => ''])->order("create_time desc")->select();

        $this->assign('version', $version);
        $this->display();
    }

    /**
     * 获取用户最左区的id
     */
    public function getUserLeftId()
    {
        if (!IS_AJAX) return;
        $user_id = session('userid');
        $type = trim(I('type', 'left'));
        if ($type == 'right') {
            $user_id = M('user')->where(['junction_id' => $user_id, 'zone' => 2])->getField('userid');
        }

        $userModel = new UserModel();
        $last_id = $userModel->getLastLeftUser($user_id);
        ajaxReturn(['userid' => $last_id], 1);
    }

    /**
     * 分享二维码
     * @author ldz
     * @time 2020/2/1 22:26
     */
    public function Share()
    {
        $user_id = session('userid');
        $userInfo = M('user')->where(['userid' => $user_id])->field('mobile,is_share')->find();
        $mobile = $userInfo['mobile'];
        $drPath = './Uploads/Scode';
        $imgUrl = 'codes' . $user_id . '.png';
        $url = './Uploads/Scode/' . $imgUrl;
        if (!file_exists($drPath . '/' . $imgUrl) && $userInfo['is_share']) {
            sp_dir_create($drPath);
            vendor("phpqrcode.phpqrcode");
            $phpqrcode = new \QRcode();
            $hurl = "http://" . $_SERVER['SERVER_NAME'] . U('Home/Login/register/mobile/' . $mobile);
            $size = "7";
            $errorLevel = "L";
            $phpqrcode->png($hurl, $drPath . '/' . $imgUrl, $errorLevel, $size);
            $phpqrcode->scerweima1($hurl, $url, $hurl);
        }
        $userInfo['mobile'] = substr_replace($mobile, '****', 3, 4);
        $this->assign([
            'shareImg' => ltrim($url, "."),
            'userInfo' => $userInfo
        ]);
        $this->display();
    }

    /**
     * 激活用户分享权限
     * @author ldz
     * @time 2020/2/11 16:39
     */
    public function openSharePower()
    {
        if (IS_AJAX) {
            $model = new UserModel();
            $res = $model->openSharePower();
            if (!$res) {
                ajaxReturn($model->getError(), 0);
            }
            ajaxReturn('激活成功', 1);
        } else {
            ajaxReturn('请求方式有误', 0);
        }
    }

    /**
     * 签到
     * @author ldz
     * @time 2020/2/12 9:57
     */
    public function signIn()
    {
        if (IS_AJAX) {
            $model = new UserModel();
            $res = $model->signIn();
            if (!$res) {
                ajaxReturn($model->getError(), 0);
            }
            ajaxReturn('签到成功', 1, U('Shop/member/mine'));
        } else {
            ajaxReturn('请求方式有误', 0);
        }
    }

    /**
     * 我的授权书
     * @author ldz
     * @time 2020/4/30 17:40
     */
    public function myAuthorization()
    {
//        $img = '/Public/NewHome/img/authorization.png';
//        $image = new Image($img);





        $this->display();
    }

}