<?php
namespace Admin\Controller;
use Common\Model\UserModel;
use Think\Controller;
use Think\Page;
/**
 * 用户控制器
 * 陶
 */
class TreeController extends AdminController
{


    /**
     * 用户列表
     * @author jry <598821125@qq.com>
     */
    public function index()
    {   
         // 搜索
        $pid        =   I('keyword', '0', 'string');
        $sex=0;
        $user           =   M('user');
        if($pid!='0')
        {
            $k_where['userid|username|account'] = array(
                $pid,
                $pid,
                $pid,
                '_multi' => true,
            );
            $query=$user->where($k_where)->Field('userid,pid')->find();
            $pid=$query['pid'];
        }
       
        $tree           =   $this->getTree($pid);
        $this->assign('tree',$tree);

        $this->display();
    }


    public  function getTree($pid='0')
    {
        $t=M('user');
        $sex=0;
        $wherea=array(  
        "pid"=>$pid,
        "sex"=>$sex
         );
        //$list=$t->where(array('pid'=>$pid,'sex'==0))->order('userid asc')->select();
        $list=$t->where($wherea)->order('userid asc')->select();

        if(is_array($list)){
            $html = '';
                $i++;
                foreach($list as $k => $v)
                {
                    $map['pid']=$v['userid'];
                    $count=$t->where($map)->count(1);
                    $class=$count==0 ? 'fa-user':'fa-sitemap';

                   if($v['pid'] == $pid)
                   {   
                        //父亲找到儿子
                        $html .= '<li style="display:none" >';
                        $html .= '<span><i class="icon-plus-sign '.$class.' blue "></i>';
                        $html .= $v['username'];
                        $html .= '</span> <a href="'.U('User/edit',array('id'=>$v['userid'])).'">';
                        $html .= $v['account'];
                        $html .= '</a>';
                        $html .= $this->getTree($v['userid']);
                        $html = $html."</li>";
                   }
                }
            return $html ? '<ul>'.$html.'</ul>' : $html ;
        }
    }

    /**
     * 会员图谱
     */
    public function map(){
        if (IS_AJAX) {
            $userModel = new UserModel();
            $data = $userModel->map();
            ajaxReturn($data, 1);
        }
        $user_id = intval(I('user_id'));
        if(empty($user_id)){
            $user_id = M('user')->where(['junction_id'=>0])->getField('userid');
        }
        $this->assign('user_id',$user_id);
        $this->display();
    }
    
    
}
