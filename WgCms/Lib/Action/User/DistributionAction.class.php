<?php
class DistributionAction extends UserAction{
	public $set;
	public $admin_account;
	public function _initialize(){
		parent::_initialize();
		$data=D('Distribution_set');
		$set=$data->where(array('token'=>session('token'),'uid'=>session('uid')))->find();
		$this->set = $set;
	}
	//公司收益明细
	public function companyEarnDetails(){
		$type = $this->_get('type');
		switch ($type) {
			case 'red':
				$condition = array(
					'aid' => -1,
					'red' => array('neq',0),
				);
				break;
			
			case 'green':
				$condition = array(
					'aid' => array('neq',-1),
					'green' => array('neq',0),
				);
				break;
		}
		$count = D('Earn')->where($condition)->count();
		$page = new Page($count,25);
		
		$key = trim($this->_post('name'));
		if($key && $type == 'red'){
			$maps['username'] = array('like','%'.$key.'%');
			$aid = D('Account')->where($maps)->getField('id');
			if($aid){
				$condition['fromid'] = $aid;
			}
		}
		if($key && $type == 'green'){
			$maps['username'] = array('like','%'.$key.'%');
			$aid = D('Account')->where($maps)->getField('id');
			if($aid){
				$condition['aid'] = $aid;
			}
		}

		$list = D('Earn')->relation(true)->where($condition)->limit($page->firstRow.','.$page->listRows)->order('addtime desc')->select();

		$this->assign('page',$page->show());
		$this->assign('list',$list);
		switch ($type) {
			case 'red':
				$this->display('companyRedDetails');
				break;
			
			case 'green':
				$this->display('companyGreenDetails');
				break;
		}
	}
	public function set(){		
		$data=D('Distribution_set');
		$set = $this->set;
		$this->assign('set',$set);
		if(IS_POST){
			$_POST['token']=session('token');			
			if($data->create()){
				if($set==false){
					if($data->add()){
						$this->success('操作成功');					
					}else{
						$this->error('服务器繁忙，请稍候再试1');
					}
				}else{
					$_POST['id']=$set['id'];
					if($data->save($_POST)){
						$this->success('操作成功');					
					}else{
						$this->error('服务器繁忙，请稍候再试2');
					}
				}
			}else{
				$this->error($data->getError());
			}
		}else{
			$this->display();
		}
	}
	public function forwardSet(){		
		//if($this->_get('token')!=session('token')){$this->error('非法操作');}
		$data=D('Distribution_forward_set');
		$info=$data->where(array('token'=>session('token'),'uid'=>session('uid')))->find();
		$this->assign('info',$info);
		if(IS_POST){
			$_POST['token']=session('token');			
			if($data->create()){
				if($info==false){
					if($data->add()){
						$this->success('操作成功');					
					}else{
						$this->error('服务器繁忙，请稍候再试1');
					}
				}else{
					$_POST['id']=$info['id'];
					if($data->save($_POST)){
						$this->success('操作成功');					
					}else{
						$this->error('服务器繁忙，请稍候再试2');
					}
				}
			}else{
				$this->error($data->getError());
			}
		}else{
			$this->display();
		}
	}
	public function member(){
		$id = $this->_get('id');
		if($id){
			$token = session('token');
			$db = M('Distribution_member');
			$member = $db->where('id='.$id)->find();
			$from_user = $db->where('id='.$member['bindmid'])->find();
			$db = M('Distribution_ordermoney');
			$order['status_0'] = $db->where(array('token'=>$token,'mid'=>$id,'status'=>0))->sum('offerMoney');//未付款
			$order['status_1'] = $db->where(array('token'=>$token,'mid'=>$id,'status'=>1))->sum('offerMoney');//已付款
			$order['status_2'] = $db->where(array('token'=>$token,'mid'=>$id,'status'=>2))->sum('offerMoney');//未收货
			$order['status_3'] = $db->where(array('token'=>$token,'mid'=>$id,'status'=>3))->sum('offerMoney');//已收货
			$order['status_-1'] = $db->where(array('token'=>$token,'mid'=>$id,'status'=>-1))->sum('offerMoney');//已退款
			$order['status_4'] = $db->where(array('token'=>$token,'mid'=>$id,'status'=>4))->sum('offerMoney');//已审核
			$where['fid'] = $id;
			$where['sid'] = $id;
			$where['tid'] = $id;
			$where['_logic'] = 'or';
			$childmember = M('Distribution_member')->where($where)->select();
			$distriCount = M('Distribution_member')->where(array('token'=>$token,'bindmid'=>$id,'distritime'=>array('neq',0)))->count();
			$totalMoney = $db->where(array('token'=>$token,'mid'=>$id,'status'=>array('gt',0)))->sum('orderMoney');//累计销售
			$totalOfferMoney = $db->where(array('token'=>$token,'mid'=>$id,'status'=>array('gt',0)))->sum('offerMoney');//累计佣金
			$orderNums = $db->where(array('token'=>$token,'mid'=>$id,'status'=>array('gt',0)))->count();
			$totalScore = M('product_cart')->where(array('token'=>$token,'wecha_id'=>$member['wecha_id'],'paid'=>1,'returnMoney'=>0))->sum('price');
			$this->assign('totalScore',$totalScore);
			$this->assign('orderNums',$orderNums);
			$this->assign('totalMoney',$totalMoney);
			$this->assign('totalOfferMoney',$totalOfferMoney);
			$this->assign('distriCount',$distriCount);
            $this->assign('childmember',$childmember);
			$this->assign('order',$order);
			$this->assign('from_user',$from_user);
			$this->assign('member',$member);
			$this->display('memberDetail');
		}else{
			$db = D('Distribution_member');
			if($this->_post('name')!=''){
				$where['name|nickname'] = array('like','%'.$this->_post('name').'%');
			}
			$where['token'] = session('token');
			$count=$db->where($where)->count();
			$page=new Page($count,25);
			$member = $db->relation(true)->where($where)->limit($page->firstRow.','.$page->listRows)->order('id desc')->select();
			$this->assign('page',$page->show());
			$this->assign('member',$member);
			$this->display();
		}
	}
	public function delMember(){
		$db = M('Distribution_member');
		$id = $this->_get('id');
		if($id){
			$wecha_id = $db->where(array('id'=>$id,'token'=>session('token')))->getField('wecha_id');
			if($db->where(array('id'=>$id,'token'=>session('token')))->delete()){
				if($wecha_id){
					M('Membercode')->where(array('wecha_id'=>$wecha_id,'token'=>session('token')))->delete();
				}
				$this->success('删除成功');
			}else{
				$this->error('删除失败');
			}
		}else{
			$this->error('异常操作');
		}
	}
	public function memberDetail(){
		$db = M('Distribution_member');
		$id = $this->_get('id');
		$token = session('token');
		if($id){
			$member = $db->where('id='.$id)->find();
			$from_user = $db->where('id='.$member['bindmid'])->find();
			$db = M('Distribution_ordermoney');
			$order['status_0'] = $db->where(array('token'=>$token,'mid'=>$id,'status'=>0))->sum('offerMoney');//未付款
			$order['status_1'] = $db->where(array('token'=>$token,'mid'=>$id,'status'=>1))->sum('offerMoney');//已付款
			$order['status_2'] = $db->where(array('token'=>$token,'mid'=>$id,'status'=>2))->sum('offerMoney');//未收货
			$order['status_3'] = $db->where(array('token'=>$token,'mid'=>$id,'status'=>3))->sum('offerMoney');//已收货
			$order['status_-1'] = $db->where(array('token'=>$token,'mid'=>$id,'status'=>-1))->sum('offerMoney');//已退款
			$order['status_4'] = $db->where(array('token'=>$token,'mid'=>$id,'status'=>4))->sum('offerMoney');//已审核
			$where['fid'] = $id;
			$where['sid'] = $id;
			$where['tid'] = $id;
			$where['_logic'] = 'or';
			$count=M('Distribution_member')->where($where)->count();
			$page=new Page($count,25);
			$childmember = M('Distribution_member')->where($where)->limit($page->firstRow.','.$page->listRows)->order('id desc')->select();
			$this->assign('page',$page->show());
			$distriCount = M('Distribution_member')->where(array('token'=>$token,'bindmid'=>$id,'distritime'=>array('neq',0)))->count();
			$totalMoney = $db->where(array('token'=>$token,'mid'=>$id,'status'=>array('gt',0)))->sum('orderMoney');//累计销售
			$totalOfferMoney = $db->where(array('token'=>$token,'mid'=>$id,'status'=>array('gt',0)))->sum('offerMoney');//累计佣金
			$orderNums = $db->where(array('token'=>$token,'mid'=>$id,'status'=>array('gt',0)))->count();
			$totalScore = M('product_cart')->where(array('token'=>$token,'wecha_id'=>$member['wecha_id'],'handled'=>1))->sum('price');
			$this->assign('totalScore',$totalScore);
			$this->assign('orderNums',$orderNums);
			$this->assign('totalMoney',$totalMoney);
			$this->assign('totalOfferMoney',$totalOfferMoney);
			$this->assign('distriCount',$distriCount);
            $this->assign('childmember',$childmember);
			$this->assign('order',$order);
			$this->assign('from_user',$from_user);
			$this->assign('member',$member);
			$this->display();
		}else{
			$this->error('异常访问');
		}
	}
	//账号列表
	public function account(){
		$db = D('Account');
		$aid = $this->_get('id');
		$type = $this->_get('type');
		//详情页
		if($aid && $type == 'detail'){
			$account = $db->where(array('id'=>$aid))->relation(true)->find();
			//创建者
			if($account['createtype'] != 1){
				$account['creater'] = M('Distribution_member')->where(array('wecha_id'=>$account['wecha_id']))->getField('nickname');
			}else{
				$account['creater'] = $account['wecha_id'];
			}
			//登陆者
			$account['loginer'] ='';
			if($account['mid'] != 0){
				$account['loginer'] = $account['member']['nickname'];
			}
			if($account['ip']){
				$account['loginer'] = $account['ip'];
			}
			//上级账号
			if($account['bindaid'] != 0){
				$account['bindac'] = M('Distribution_account')->where(array('id'=>$account['bindaid']))->getField('username');
			}else{
				$account['bindac'] = '';
			}
			
			$data = array(
				'green' => $this->statistical('green',$account['id']),
				'red' => $this->statistical('red',$account['id']),
				'black' => $this->statistical('black',$account['id']),
				'shoporders' => $this->statistical('shoporders',$account['id']),		//购买订单总数
			);
			$account['detail'] = $data;
			//子账号
			$account['childs'] = $db->where(array('bindaid'=>$account['id'],'delete'=>0))->relation(true)->select();
			$this->assign('info',$account);
			$this->display('accountDetail');
			exit();
		}
		//充值金币页面
		if($aid && $type == 'topup'){
			$account = $db->where(array('id'=>$aid))->relation(true)->find();
			$account['green'] = $this->statistical('green',$account['id']);
			$this->assign('info',$account);
			$this->display('greenTopup');
		}
		//账号列表
		if(!$aid && !$type){
			$where['delete'] = 0;

			if($this->_post('name')!=''){
				$where['truename|username'] = array('like','%'.$this->_post('name').'%');
			}
			//列表页
			$count=$db->where($where)->count();
			$page=new Page($count,25);

			$list = $db->where($where)->relation(true)->limit($page->firstRow.','.$page->listRows)->order('addtime desc')->select();
			foreach ($list as $k => $v) {
				$list[$k]['creater'] = M('Distribution_member')->where(array('wecha_id'=>$v['wecha_id']))->getField('nickname');
				$list[$k]['bindac'] = M('Distribution_account')->field('id,truename')->where(array('id'=>$v['bindaid']))->find();
			}
			$this->assign('page',$page->show());
			$this->assign('list',$list);
			$this->display();
		}
	}
	//账号收入明细
	public function accountEarnDetails(){
		$type = $this->_get('type');
		$aid = $this->_get('id');
		$db = D('Earn');
		$where['aid'] = $aid;
		switch ($type) {
			case 'green':
				$where['green'] = array('neq',0);
				break;
			
			case 'red':
				$where['red'] = array('neq',0);
				break;
		}
		$count = $db->where($where)->count();
		$page = new Page($count,25);
		$list = $db->where($where)->limit($page->firstRow.','.$page->listRows)->relation(true)->select();

		//账号名
		$username = D('Account')->where(array('id'=>$aid))->getField('username');

		$this->assign('page',$page->show());
		$this->assign('list',$list);
		$this->assign('username',$username);
		switch ($type) {
			case 'green':
				$this->display('accountGreenDetails');
				break;
			
			case 'red':
				$this->display('accountRedDetails');
				break;
		}
	}
	//修改账号信息
	public function accountDetailEdit(){
		$aid = $this->_get('id');
		$name = $this->_get('name');
		$edit_con = $this->_get('con');
		$edit_con = $name == 'password' ? md5($edit_con) : $edit_con;
		$data[$name] = $edit_con;
		$data['updatetime'] = time();
		$re = D('Account')->where('id='.$aid)->save($data);
		if($re){
			D('Account')->where('id='.$aid)->setField('changpwd',1);
			$this->ajaxReturn('','修改成功',1);
		}else{
			$this->ajaxReturn('','修改失败',2);
		}
	}
	//删除账号
	public function deleteAccount(){
		$id = $this->_get('id');
		//$re = D('Account')->where('id='.$id)->setField('delete',1);
		$re = D('Account')->where('id='.$id)->delete();
		if($re){
			$this->success('删除成功',U('Distribution/account'));
		}else{
			$this->error('删除成功',U('Distribution/account'));
		}
	}
	public function greenTopup(){
		$aid = $this->_post('aid');
		$green = $this->_post('green');
		$set = M('Distribution_set')->find();

		//插入充值订单表
		$orderid = substr($this->token, -1, 4) . date("YmdHis");
		$price = $green;
		$_POST['orderid'] = $orderid;
		$_POST['price'] = $price;
		$_POST['aid'] = $aid;
		$_POST['bindaid'] = 0;
		$_POST['paid'] = 1;
		$_POST['back'] = 1;
		$_POST['integral'] = $green * 0.5;
		$_POST['needred'] = $green * $set['inback']/100;
		$db = D('LevelOrders');
		if ($db->create() === false) {
		    $this->error($db->getError());
		} else {
		    $result = $db->add();
		}

		$re = $this->earnRecord($aid,$orderid,0,$green,0,0,14,$_SERVER['SERVER_ADDR'],0,$aid);

		$account = D('Account')->where('id='.$aid)->find();
		$integral = $green;
		if($account['bindaid']){
		    $this->earnRecord($account['bindaid'],0,0,0,$integral * $set['firstPer']/100,0,1,$_SERVER['SERVER_ADDR'],0,$aid);
		    //返还上上级咪豆

		    $upupaid = D('Account')->where('id='.$account['bindaid'])->getField('bindaid');
		    if($upupaid){
		        $this->earnRecord($upupaid,0,0,0,$integral * $set['secondPer']/100,0,2,$_SERVER['SERVER_ADDR'],0,$aid);
		    }else{//如果没有上上级 将分红返还给公司
		        $this->earnRecord(-1,0,0,0,$integral * $set['secondPer']/100,0,13,$_SERVER['SERVER_ADDR'],0,$aid);
		    }
		}else{//如果没有上级 将 两份返红给公司
		    $this->earnRecord(-1,0,0,0,$integral * ($set['firstPer'] + $set['secondPer'])/100,0,12,$_SERVER['SERVER_ADDR'],0,$aid);
		}
		//返还代理点咪豆

		if($account['agent']){
		    $this->earnRecord(0,0,0,0,$integral * $set['thirdPer']/100,0,3,$_SERVER['SERVER_ADDR'],$account['agent'],$aid);
		}
		//返还公司咪豆

		$this->earnRecord(-1,0,0,0,$integral * $set['comPer']/100,0,4,$_SERVER['SERVER_ADDR'],0,$aid);

		if($re){
			$this->success('充值成功',U('Distribution/account',array('id'=>$aid,'type'=>'topup')));
		}
	}
	//代理点列表
	public function agent(){
		$db = M('Distribution_agent');
		$type = $this->_get('type');
		$id = $this->_get('id');
		if($type == 'register'){
			$this->display('agentRegister');
			exit();
		}
		if($type == 'edit'){
			$agent = $db->where('id='.$id)->find();
			$this->assign('info',$agent);
			$this->display('agentEdit');
			exit();
		}
		if($type == 'delete'){
			$this->del_id('Distribution_agent','Distribution/agent');
			exit();
		}
		if($type == 'accountdetails'){
			$agent = $db->where('id='.$id)->find();
			$where['agent'] = $id;
			$count= D('Account')->where($where)->count();
			$page=new Page($count,25);

			//账号筛选
			if($this->_post('name')!=''){
				$where['username'] = array('like','%'.$this->_post('name').'%');
			}
			//时间
			$starttime = $this->_post('starttime')!='' ? $this->_post('starttime'): $this->_request('starttime');
			$endtime = $this->_post('endtime')!='' ? $this->_post('endtime'): $this->_request('endtime');

			if($starttime && $endtime){
				$starttime=date(strtotime($starttime));
				$endtime=date(strtotime($endtime))+86400;
				$where['addtime'] = array(array('gt',$starttime),array('lt',$endtime),'and');
			}
			$accounts = D('Account')->where($where)->limit($page->firstRow.','.$page->listRows)->select();
			
			$this->assign('agent',$agent);
			$this->assign('accounts',$accounts);
			$this->assign('page',$page->show());
			$this->display('agentDetails');
			exit();
		}
		if($type == 'loweraccount'){
			$account = D('Account')->where('id='.$id)->find();

			$where['bindaid'] = $id;

			$count= D('Account')->where($where)->count();
			$page=new Page($count,25);

			$accounts = D('Account')->where($where)->limit($page->firstRow.','.$page->listRows)->select();
			$this->assign('account',$account);
			$this->assign('accounts',$accounts);
			$this->assign('page',$page->show());
			$this->assign('page',$page->show());
			$this->display('loweraccount');
			exit();
		}
		if($type == 'earndetails'){
			$agent = $db->where('id='.$id)->find();
			//时间
			$starttime = $this->_post('starttime')!='' ? $this->_post('starttime'): $this->_request('starttime');
			$endtime = $this->_post('endtime')!='' ? $this->_post('endtime'): $this->_request('endtime');
			//时间筛选
			if($starttime && $endtime){
				$starttime=date(strtotime($starttime));
				$endtime=date(strtotime($endtime))+86400;
				$where['addtime'] = array(array('gt',$starttime),array('lt',$endtime),'and');
			}
			//账号筛选
			if($this->_post('name')!=''){
				$condition['username'] = array('like','%'.$this->_post('name').'%');
				$aid = D('Account')->where($condition)->getField('id');
				if($aid){
					$where['aid'] = $aid;
				}
			}

			//代理点红色积分
			unset($where['agent']);
			$where['gid'] = $id;
			$red = M('Distribution_earning')->where(array('gid'=>$id,'aid'=>array("neq",-1)))->sum('red');

			//分页
			$count= D('Account')->where($where)->count();
			$page=new Page($count,25);

			//收入详细
			$earns = D('Earn')->where($where)->relation(true)->limit($page->firstRow.','.$page->listRows)->order('addtime desc')->select();

			$this->assign('red',$red);
			$this->assign('agent',$agent);
			$this->assign('earns',$earns);
			$this->assign('page',$page->show());
			$this->display('agentEarnDetails');
			exit();
		}
		if($type == 'transferpage'){
			$agent = M('Distribution_agent')->where('id='.$id)->find();
			$red =M('Distribution_earning')->where(array('gid'=>$id,'aid'=>array("neq",-1)))->sum('red');
			$agent['red'] = $red;
			$this->assign('agent',$agent);
			$this->display('transferPage');
			exit();
		}
		if($type == 'transfer'){
			$db = D('Transfer');
			$red= trim($this->_post('red'));
			if(!is_numeric($red)){
				$this->error('输入额度有误',U('Distribution/agent',array('type'=>'transferpage','id'=>$id)));
			}
			//判断代理点红色咪豆
			$agent_red = M('Distribution_earning')->where(array('gid'=>$id,'aid'=>array("neq",-1)))->sum('red');
			if($red > $agent_red){
				$this->error('代理点咪豆不足',U('Distribution/agent',array('type'=>'transferpage','id'=>$id)));
			}
			$code = $this->_post('code');
			$type = $this->_post('type');
			//转账给账号
			if($type == 1){
				//判断推荐码是否正确
				$account = D('Account')->where(array('recommend'=>$code))->find();
				if(!$account){
					$this->error('该推荐码账号不存在',U('Distribution/agent',array('type'=>'transferpage','id'=>$id)));
				}
				$remark = $this->_post('remark');
				$data = array(
					'gid' => $id,
					'intoId' => $account['id'],
					'red' => $red,
					'remark' => $remark,
					'ip' => $_SERVER['SERVER_ADDR'],
					'addtime' => time(),
					'year' => date('Y',time()),
					'month' => date('m',time()),
					'day' => date('d',time()),
				);
				$re = $db->add($data);
				$this->earnRecord($account['id'],0,0,0,$red,0,15,$_SERVER['SERVER_ADDR'],0,0,$id,$remark);
				$this->earnRecord(0,0,0,0,-$red,0,15,$_SERVER['SERVER_ADDR'],$id);
			}
			//转账给代理
			if($type == 2){
				//判断推荐码是否正确
				$agent = M('Distribution_agent')->where(array('code'=>$code))->find();
				if(!$agent){
					$this->error('该代理点不存在');
				}
				$remark = $this->_post('remark');
				$data = array(
					'fromgid' => $id,
					'gid' => $agent['id'],
					'red' => $red,
					'remark' => $remark,
					'ip' => $_SERVER['SERVER_ADDR'],
					'addtime' => time(),
					'year' => date('Y',time()),
					'month' => date('m',time()),
					'day' => date('d',time()),
				);
				$re = $db->add($data);
				//相应代理点增加红色咪豆
				$this->earnRecord(0,0,0,0,$red,0,16,$_SERVER['SERVER_ADDR'],$agent['id'],0,$id,$remark);
				//代理点减红色积分
				$re = $this->earnRecord(0,0,0,0,-$red,0,16,$_SERVER['SERVER_ADDR'],$id,0,0,$remark);
			}
			if($re){
				$this->success('转账成功',U('Distribution/agent',array('type'=>'transferpage','id'=>$id)));
			}else{
				$this->error('转账失败',U('Distribution/agent',array('type'=>'transferpage','id'=>$id)));
			}
			exit();
		}
		if($type == 'transferDetails'){
			$db = D('Transfer');
			$condition = array(
				'gid' => $id,
				'fromgid' => $id,
				'_logic' => 'OR',
			);
			$list = $db->where(array('gid'=>$id))->relation(true)->order('addtime desc')->select();
			$this->assign('list',$list);
			$this->display('transferDetails');
			exit();
		}

		if($this->_post('name')!=''){
			$where['name'] = array('like','%'.$this->_post('name').'%');
		}

		//列表页
		$count=$db->where($where)->count();
		$page=new Page($count,25);

		$list = $db->where($where)->limit($page->firstRow.','.$page->listRows)->select();
		foreach ($list as $k => $v) {
			$list[$k]['red'] = $this->statistical('agentred',$v['id']);
		}
		$this->assign('page',$page->show());
		$this->assign('list',$list);
		$this->display();
	}
	public function agent_register(){
		//判断重复
		$name = rtrim($_POST['name']);
		$username = rtrim($_POST['username']);
		$password = rtrim($_POST['password']);
		if(!$name || !$username || !$password){
			$this->error('信息不能为空');
		}
		$condition = array(
			'name' => $name,
			'username' => $username,
			'_logic' => 'OR',
		);
		$agent = M('Distribution_agent')->where($condition)->find();
		if($agent){
			$this->error('代理点名或账号已经存在');
			exit();
		}
		$_POST['code'] = String::randString(6,3);
		$_POST['addtime'] = time();
		$_POST['year'] = date('Y',time());
		$_POST['month'] = date('m',time());
		$_POST['day'] = date('d',time());
		$_POST['password'] = md5($password);
		$this->insert('Distribution_agent','/agent');
	}
	public function agent_edit(){
		$password = rtrim($_POST['password']);
		$_POST['password'] = md5($password);
		$this->save('Distribution_agent','/agent');
	}
	//会员充值记录列表
	public function topupRecord(){
		$db = D('LevelOrders');
		$type = $this->_get('type');
		$id = $this->_get('id');
		$cbid = $this->_get('cbid');
		$branch_db = M('Company_branch');
		$branch = $branch_db->where(array('id'=>$cbid))->find();
		if($type == 'return'){
			$order = $db->where(array('id'=>$id))->find();
			$account = D('Account')->where('id='.$order['aid'])->find();
			$data['username'] = $account['username'];
			$data['price'] = $order['price'];
			$data['topup_integral'] = $order['topup'];
			$data['need_integral'] = $order['needred'] - $data['topup_integral'];
			$data['oid'] = $order['id'];
			$data['aid'] = $account['id'];
			$this->assign('info',$data);
			$this->assign('cbid',$cbid);
			$this->display('topup');
			exit();
		}
		if($type == 'details'){
			$list = M('Distribution_earning')->where(array('status'=>5,'oid'=>$id,'aid'=>array('gt',0)))->select();
			$this->assign('list',$list);
			$this->display('topupdetails');
			exit();
		}
		if($type == 'list' && $cbid && $branch){
			$count = $db->where(array('paid'=>1))->count();
			$page = new Page($count,25);

			$records = $db->where(array('paid'=>1))->relation(true)->order('addtime asc')->limit($page->firstRow.','.$page->listRows)->select();
			foreach ($records as $k => $v) {
				if($v['back'] == 1){
					$records[$k]['member']['nickname'] = $v['ip'];
				}
				$records[$k]['need_integral'] = $v['price'] * $this->set['inback']/100 - $v['topup'];
			}
			$this->assign('cbid',$cbid);
			$this->assign('list',$records);
			$this->assign('page',$page->show());
			$this->display();
			exit();
		}
		$branch_list = $branch_db->select();
		$this->assign('branch_list',$branch_list);
		$this->display('companyBranch');
	}
	//改变分支占比
	public function changeBranchProportion(){
		$p = $this->_get('p');
		$cbid = $this->_get('cbid');
		$p = rtrim($p);
		$db = M('Company_branch');
		$re = '';
		if($cbid && $p){
			$re = $db->where('id='.$cbid)->setField('proportion',$p);
		}
		if($re){
			$this->ajaxReturn('','修改成功',1);
		}else{
			$this->ajaxReturn('','修改失败',2);
		}
	}
	//会员转账记录
	public function transferRecord(){
		$db = D('Transfer');

		$count=$db->count();
		$page=new Page($count,25);
		$list = $db->limit($page->firstRow.','.$page->listRows)->order('id desc')->relation(true)->select();
		
		$this->assign('page',$page->show());
		$this->assign('list',$list);
		$this->display();
	}
	//后台给账号返红色咪豆
	public function accountTopup(){
		$aid = $this->_post('aid');
		$oid = $this->_post('oid');
		$cbid = $this->_post('cbid');
		$gold = $this->_post('gold');
		if(!is_numeric($gold) || $gold<=0){
			$this->error('咪豆输入有误');
		}
		//判断公司剩余红色咪豆
		$branch_db = M('Company_branch');
		$branch = $branch_db->where(array('id'=>$cbid))->find();
		if($branch['red'] < $gold){
			$this->error('公司咪豆不足');
		}
		//判断充值金币是够超出所需咪豆

		// $order = D('LevelOrders')->field('price,topup')->where('id='.$oid)->find();
		// $need_integral = $order['price'] * $this->set['inback']/100 - $order['topup'];
		// if($gold > $need_integral){
		// 	$this->error('超出所需咪豆');
		// }

		if(is_numeric($gold)){
			//如果超出就直接冲满
			$order = D('LevelOrders')->field('price,topup,finish,needred')->where('id='.$oid)->find();
			//判断该订单是否充值完毕
			if($order['finish'] == 1){
				$this->error('该订单已经充值完毕');
			}else{
				$need_integral = $order['needred'];
				if($gold >= $need_integral){
					$gold = $need_integral;
					$finish = 1;
				}
				D('LevelOrders')->where('id='.$oid)->setInc('topup',$gold);
				//返账号红色咪豆
				$this->earnRecord($aid,$oid,0,0,$gold,0,5,$_SERVER['SERVER_ADDR']);
				//公司账号减红色咪豆
				$r = $this->earnRecord(-1,$oid,0,0,-$gold,0,5,$_SERVER['SERVER_ADDR']);
				//对分支操作
				$branch_records_db = M('Company_branch_records');
				$data = array(
					'cbid' => $cbid,
					'aid' => $aid,
					'red' => -$gold,
					'status' => 2,
					'addtime' => time(),
					'year' => date('Y',time()),
					'month' => date('m',time()),
					'day' => date('d',time()),
				);
				$branch_records_db->add($data);
				$branch_db->where(array('id'=>$cbid))->setDec('red',$gold);
				if($r){
					if($finish == 1){
						D('LevelOrders')->where('id='.$oid)->setField('finish',1);
					}
					$this->success('充值成功',U('Distribution/topupRecord',array('id'=>$oid,'type'=>'return','cbid'=>$cbid)));
				}
			}
		}else{
			$this->error('充值金额有误');
		}
	}
	public function bank(){
		$db = M('Distribution_bank');
		if($this->_post('name')!=''){
			$where['name|bankName'] = array('like','%'.$this->_post('name').'%');
		}
		$where['token'] = session('token');
		$count=$db->where($where)->count();
		$page=new Page($count,25);
		$list = $db->where($where)->limit($page->firstRow.','.$page->listRows)->order('id desc')->select();
		foreach($list as $key=>$value){
			$member = M('Distribution_member')->where(array('wecha_id'=>$value['wecha_id'],'token'=>$value['token']))->find();
			$list[$key]['nickname'] = $member['nickname'];
			$list[$key]['headimgurl'] = $member['headimgurl'];
			//所属区域
			if($value['classid']){
				import ( "@.Org.TypeFile" );
				$tid = $value['classid'];
				$TypeFile = new TypeFile ( 'ClassCity' ); //实例化分类类
				$result = $TypeFile->getPathName ( $tid ); //获取分类路径
				$list[$key]['typeNumArr']= $result ;
			}
		}
		$this->assign('page',$page->show());
		$this->assign('list',$list);
		$this->display();
	}
	public function moneylist(){
		$db = M('Distribution_applystore');
		if($this->_post('name')!=''){
			$where['name|bankName'] = array('like','%'.$this->_post('name').'%');
		}
		$where['token'] = session('token');
		$count=$db->where($where)->count();
		$page=new Page($count,25);
		$list = $db->where($where)->limit($page->firstRow.','.$page->listRows)->order('id desc')->select();
		foreach($list as $key=>$value){
			$member = M('Distribution_member')->where(array('id'=>$value['mid']))->find();
			$list[$key]['memberid'] = $member['id'];
			$list[$key]['nickname'] = $member['nickname'];
			$list[$key]['headimgurl'] = $member['headimgurl'];
			//所属区域
			if($value['classid']){
				import ( "@.Org.TypeFile" );
				$tid = $value['classid'];
				$TypeFile = new TypeFile ( 'ClassCity' ); //实例化分类类
				$result = $TypeFile->getPathName ( $tid ); //获取分类路径
				$list[$key]['typeNumArr']= $result ;
			}
		}
		$this->assign('page',$page->show());
		$this->assign('list',$list);
		$this->display();
	}
	public function changeStatus(){
		$db = M('Distribution_applystore');
		$id = $this->_get('id');
		$status = $this->_get('status');
		if($status!=1&&$status!=2){
			$this->error('异常操作');
		}
		$data['status'] = $status;
		if($db->where('id='.$id)->save($data)){
			$this->success('处理成功');
		}else{
			$this->error('处理失败');
		}
	}
	public function sendMoney(){
		$id = $this->_get('id');
		if(!$id){
			$this->error('非法操作！');
			exit();
		}
		$result = $this->TXhongbao($id);
		if($result['return_code']=='SUCCESS'){
			M('Distribution_applystore')->where(array('token'=>session('token'),'id'=>$id,'status'=>0))->setField('status',1);
			$this->success('发放成功！');
		}else{
			$this->error($result['return_msg']);
		}
	}
	public function address(){
		$db = D('Address');
		if($this->_post('name')!=''){
			$maps['username'] = array('like','%'.$this->_post('name').'%');
			$accounts = D('Account')->where($maps)->select();
			foreach ($accounts as $k => $v) {
				$astr .= $v['id'].",";
			}
			$astr = rtrim($astr,',');
			$where['aid'] = array('in',$astr);
		}

		$where['choose'] = 1 ;
		$count=$db->where($where)->count();
		$page=new Page($count,25);
		$list = $db->where($where)->limit($page->firstRow.','.$page->listRows)->order('id desc')->relation(true)->select();

		$this->assign('page',$page->show());
		$this->assign('list',$list);
		$this->display();
	}
	public function collection(){
		$db = M('Product_collection');
		$where['token'] = session('token');
		$count=$db->where($where)->count();
		$page=new Page($count,25);
		$list = $db->where($where)->limit($page->firstRow.','.$page->listRows)->order('id desc')->select();
		$memberModel = M('Distribution_member');
		$productModel = M('Product');
		foreach($list as $key=>$value){
			$member = $memberModel->where(array('token'=>$value['token'],'wecha_id'=>$value['wecha_id']))->field('nickname,headimgurl')->find();
			$list[$key]['nickname'] = $member['nickname'];
			$list[$key]['headimgurl'] = $member['headimgurl'];
			$product = $productModel->where('id='.$value['productid'])->field('name')->find();
			$list[$key]['productname'] = $product['name'];
		}
		$this->assign('page',$page->show());
		$this->assign('list',$list);
		$this->display();
	}
	public function beDistribution(){
		$db = M('Distribution_member');
		$id = $this->_get('id');
		if($id){
			$data['distritime'] = time();
			if($db->where(array('id'=>$id,'token'=>session('token')))->save($data)){
				$this->success('设为分销成功');
			}else{
				$this->error('设为分销失败');
			}
		}else{
			$this->error('异常操作');
		}
	}
	private function TXhongbao($id)
	{
		//读取微信支付配置
		$payConfig = M('Alipay_config')->where(array('token'=>session('token')))->find();
		//读取商家信息
		$company = M('company')->where(array('token'=>session('token')))->find();
		//读取提现申请
		$apply = M('Distribution_applystore')->where(array('token'=>session('token'),'id'=>$id,'status'=>0))->find();
		if(!$apply){
			$this->error('异常操作！');
			exit();
		}
		$url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack';
		$key = $payConfig['paysignkey'];//API密钥
		$mch_id = $payConfig['mchid'];//商户号
		$sub_mch_id = '';//子商户号
		$wxappid = $payConfig['appid'];//appid
		$nick_name = '佣金提现';//提供方名称
		$send_name = '佣金提现';//商户名称
		$re_openid = $apply['wecha_id'];//用户openid
		$total_amount = $apply['money'];//付款金额
		$min_value = $apply['money'];//最小红包金额
		$max_value = $apply['money'];//最大红包金额
		$total_num = 1;//红包发放总人数
		$wishing = '恭喜您，提现成功啦';//红包祝福语
		$client_ip = $payConfig['ip'];//Ip地址
		$act_name = '佣金提现';//活动名称
		$remark = '备注';//备注
		$logo_imgurl = $company['logourl'];//商户logo的url

		$data = array();
		$data['nonce_str'] = md5(rand(10000000,99999999));
		$data['mch_billno'] = $mch_id.date('Ymd').rand(1000000000,9999999999);
		$data['mch_id'] = $mch_id;
		$data['sub_mch_id'] = $sub_mch_id;
		$data['wxappid'] = $wxappid;
		$data['nick_name'] = $nick_name;
		$data['send_name'] = $send_name;
		$data['re_openid'] = $re_openid;
		$data['total_amount'] = $total_amount;
		$data['min_value'] = $min_value;
		$data['max_value'] = $max_value;
		$data['total_num'] = $total_num;
		$data['wishing'] = $wishing;
		$data['client_ip'] = $client_ip;
		$data['act_name'] = $act_name;
		$data['remark'] = $remark;
		$data['logo_imgurl'] = $logo_imgurl;
		
		$data['sign'] = $this->signValue($data,$key);
		$xml = new SimpleXMLElement('<xml></xml>');
        $this->data2xml($xml, $data);
        $postXML = $xml->asXML();
		$result = $this->api_notice_increment_xml($url,$postXML);
		return $this->xmlToArray($result);
	}
	private function data2xml($xml, $data, $item = 'item')
    {
        foreach ($data as $key => $value) {
            is_numeric($key) && $key = $item;
            if (is_array($value) || is_object($value)) {
                $child = $xml->addChild($key);
                $this->data2xml($child, $value, $item);
            } else {
                if (is_numeric($value)) {
                    $child = $xml->addChild($key, $value);
                } else {
                    $child = $xml->addChild($key);
                    $node  = dom_import_simplexml($child);
                    $node->appendChild($node->ownerDocument->createCDATASection($value));
                }
            }
        }
    }
	private function signValue($data,$keyStr)
    {
		$str = '';
		ksort($data,SORT_STRING);
		foreach($data as $key=>$value){
			if($value!=''){
				$str .= $key.'='.$value.'&';
			}
		}
		$str .= 'key='.$keyStr;
		$sign = strtoupper(MD5($str));
		return $sign;
    }
	private	function api_notice_increment_xml($url, $data){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);

		//因为微信红包在使用过程中需要验证服务器和域名，故需要设置下面两行
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // 只信任CA颁布的证书 
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // 检查证书中是否设置域名，并且是否与提供的主机名匹配


		curl_setopt($ch, CURLOPT_SSLCERT,CONF_PATH.'hongbao/apiclient_cert.pem');
		curl_setopt($ch, CURLOPT_SSLKEY,CONF_PATH.'hongbao/apiclient_key.pem');
		curl_setopt($ch, CURLOPT_CAINFO,CONF_PATH.'hongbao/rootca.pem'); // CA根证书（用来验证的网站证书是否是CA颁布）


		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$res = curl_exec($ch);
		curl_close($ch);
		return $res;
	}
	/**
	 * 作用：将xml转为array
	 */
	public function xmlToArray($xml)
	{       
	   //将XML转为array        
	   $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);       
	   return $array_data;
	}
}


?>