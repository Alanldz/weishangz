<?php
namespace Admin\Controller;

use Think\Page;
use Admin\Model\CrowdsdModel;
use Admin\Model\UserModel;
/**
 * 用户控制器
 * 
 */
class CrowdsdController extends AdminController
{


    /**
     * 众筹列表
     * 
     */
     public function index()
    {


         // 搜索
        $keyword    = I('keyword', '', 'string');
        $querytype  = I('querytype','userid','string');
        $status     = I('status');
        if($keyword){
            
            $map['uid'] = $keyword;
        }
        

         //按日期搜索
        $date=date_query('create_time');
        if($date){
            $where=$date;
            if(isset($map))
                $map=array_merge($map,$where);
            else
                $map=$where;
        }
      
        if($level!=''){
            $map['a.level']=$level;
        }

        // 获取所有用户
        $crowd  = new CrowdsdModel();
       $crowdsd=$crowd->select();
    //   var_dump($crowdsd);die;
        if(!isset($map)){
            $map=true;
        }
        // var_dump($map);


        //分页
       
        $p=getpage($crowd,$map,15);
        $page=$p->show();  

        $data_list     = $crowd->where($map)->order('id desc')->select();
        // echo $crowd->getlastsql();
        // var_dump($data_list);die;
        foreach($data_list as $v){
         
            $v['create_time']=date('Y-m-d H:i:s',$v['create_time']);
            $v['username']=M('user')->where("userid={$v['uid']}")->getField('username');
            $v['is_empty']=M('user')->where("userid={$v['uid']}")->getField('empty_order');

            $data_lists[]=$v;
        }
    
         //取管理员会员列表的权限   
        $uids= is_login();    
        $hylbs="1,2,3,4,5"; 
        $auth_id    = M('admin')->where(array('id'=>$uids))->getField('auth_id');   
        if($auth_id<>1){
        $auth_id    = M('admin')->where(array('id'=>$uids))->getField('auth_id');
        $hylbs    = M('group')->where(array('auth_id'=>$auth_id))->getField('hylb');

        }

        $hylb=explode(",",$hylbs);
        $this->assign('hylb',$hylb);
        $this->assign('list',$data_lists);
        $this->assign('table_data_page',$page);
        $this->display();
    }

    /**
     * 挂售列表
     */
    public function sellList(){

        $where = ['t.pay_state'=>0, 't.trans_type'=>0];         //订单状态为pay_state=>0,表示上架中；trans_type=>0表示卖出

        $table = M('trans as t');
        $p=getpage($table,$where,15);
        $page=$p->show();

        $order_info = $table->field('t.id,t.payout_id,t.pay_no,t.pay_nums,t.pay_time,ub.hold_name,u.reg_date')
            ->join('ysk_user as u on u.userid = t.payout_id')
            ->join('ysk_ubanks as ub on ub.id = t.card_id')
            ->where($where)->order('u.reg_date desc')->select();

        $this->assign('list',$order_info);
        $this->assign('table_data_page',$page);
        $this->display();
    }

    /**
     * 交易中
     */
    public function sellTrading(){

        $status = I('status');

        $where = ['trans_type'=>0];
        if($status == 1){
            $where['pay_state'] = ['in',[1,2]];
        }else{
            $where['pay_state'] = 3;
        }
        $table = M('trans as tr')->join('LEFT JOIN  ysk_user as us on tr.payout_id = us.userid');

        $p=getpage($table,$where,15);
        $page=$p->show();

        $order_info = $table->field('id,payout_id,payin_id,pay_state,pay_no,pay_nums,pay_time,card_id,trans_img,img_upload_time')->where($where)->order('id desc')->select();

        foreach($order_info as $k => $v){
            $order_info[$k]['cardinfo'] = M('ubanks as b')->field('b.id,b.hold_name,bn.banq_genre,b.card_number,b.open_card')->where(array('b.id'=>$v['card_id']))->join('LEFT JOIN  ysk_bank_name as bn on bn.q_id = b.card_id')->find();
        }

        $this->assign('status',$status);
        $this->assign('list',$order_info);
        $this->assign('table_data_page',$page);

        $this->display();
    }

    /**
     * 挂售列表交易中删除
     */
    public function sellDelete(){
        $id = I('id');
        $type = I('type','');
        if(!$id){
            $this->error('参数不正确');
        }
        $query = M('trans')->where(['id'=>$id]);

        $trans = $query->find();
        if(!$trans){
            $this->error('数据不存在');
        }

        if($type == 'unsold'){                          //未出售删除
            $allNum = $trans['pay_nums'] * 1.1;
            $payout_id = $trans['payout_id'];
            $del = $query->delete();
            if(!$del){
                $this->error('删除失败');
            }

            //取消订单时用户EP加回来
            $storeInfo = M('store')->where(['uid'=>$payout_id])->find();
            $data['cangku_num'] = $storeInfo['cangku_num'] + $allNum;
            M('store')->where(['uid'=>$payout_id])->save($data);

            //添加取消卖出记录
            $pay_n = M('store')->where(array('uid' => $payout_id))->getfield('cangku_num');
            $jifen_dochange['now_nums'] = $pay_n;
            $jifen_dochange['now_nums_get'] = $pay_n;
            $jifen_dochange['is_release'] = 1;
            $jifen_dochange['pay_id'] = 0;
            $jifen_dochange['get_id'] = $payout_id;
            $jifen_dochange['get_nums'] = $allNum;
            $jifen_dochange['get_time'] = time();
            $jifen_dochange['get_type'] = 10;
            M('tranmoney')->add($jifen_dochange);

            $this->success('删除成功');
        }else{                                          //交易中删除
            $data['payin_id'] = '';
            $data['pay_state'] = 0;
            $data['trans_img'] = '';
            $data['img_upload_time'] = '';

            $res = M('trans')->where(['id'=>$id])->save($data);
            if(!$res){
                $this->error('删除失败');
            }
            $this->success('删除成功');
        }

    }


    
}
