<?php 
namespace Admin\Model;

/**
* 分类模型
*/
class GoodsModel extends CommonModel
{

	//自定义字段
	protected $fields=array('id','goods_name','goods_sn','cate_id','market_price','shop_price','goods_img','goods_thumb','goods_body','is_hot','is_rec','is_new','addtime','isdel','is_sale');
	//自定义自动验证
	protected $_validate=array(
		array('goods_name','require','商品名称必须填写',1),
		array('cate_id','checkCategory','分类必须填写',1,'callback'),
		array('market_price','currency','市场价格格式不对'),
		array('shop_price','currency','本店价格格式不对'),
	);
	//对分类进行验证
	public function checkCategory($cate_id)
	{
		$cate_id = intval($cate_id);
		if($cate_id>0){
			return true;
		}
		return false;
	}


	//实现商品信息修改
	public function update($data)
	{
		$goods_id = $data['id'];

		//解决商品的货号问题
		$goods_sn =$data['goods_sn'];
		if(!$goods_sn){
			//没有提交货号
			$data['goods_sn'] = 'JX'.uniqid();
		}else{
			//用户有提交货号 检查货号是否重复 并且需要将自己以前的货号排除在外
			$res = $this->where("goods_sn = '$goods_sn' AND id != $goods_id")->find();
			if($res){
				$this->error = '货号错误';
				return false;
			}
		}

		//解决扩展分类的问题
		//删除之前的扩展分类
		$extCateModel= D('GoodsCate');
		$extCateModel->where("goods_id = $goods_id")->delete();
		//将最新的扩展分类写入数据
		//接受提交的扩展分类
		$ext_cate_id = I('post.ext_cate_id');	
		$extCateModel->insertExtCate($ext_cate_id,$goods_id);

		//图片上传
		$res = $this->uploadImg();
		if($res){
			$data['goods_img'] = $res['goods_img'];
			$data['goods_thumb'] =  $res['goods_thumb'];
		}
		return $this->save($data);
	}

	//使用TP的钩子函数
	public function _before_insert(&$data)
	{
		//添加时间
		$data['addtime']=time();

		//处理货号
		if(!$data['goods_sn']){
			//没有题号货号自动生成
			$data['goods_sn'] = 'JX'.uniqid();
		}else{
			//有提交货号
			$info = $this->where('goods_sn='.$data['goods_sn'])->find();
			if($info){
				$this->error='货号重复';
				return false;
			}
		}

		//图片上传
		$res = $this->uploadImg();
		if($res){
			$data['goods_img'] = $res['goods_img'];
			$data['goods_thumb'] =  $res['goods_thumb'];
		}
	}

	//是在add完成之后自动执行
	public function _after_insert($data)
	{
		$goods_id=$data['id'];
		//接受提交的扩展分类
		$ext_cate_id = I('post.ext_cate_id');	
		D('GoodsCate')->insertExtCate($ext_cate_id,$goods_id);
	}

	//获取商品信息
	public function listData($isdel=1)
	{
		//1、定义每页显示的数据条数
		$pagesize = 3;
		//2、获取数据总数
		$where = 'isdel='.$isdel;
		//接受提交的分类ID
		$cate_id = intval(I('get.cate_id'));
		if($cate_id){
			//拼接where子句
			//1、根据提交的分类ID表示查询出商品表中cate_id值等于该ID标识的即可
			//2、根据提交的分类ID 先查询出当前分类下的所有的子分类。然后在将提交的分类ID与该分类所对应的子分类进行组合作为条件进行查询。此时可以使用MySQL中的in语法进行查询
			//3、查询出商品的扩展分类的ID等于当前分类或者是当前分类所对应的子分类。此时能够得到商品ID 然后在根据商品ID获取对应的商品信息
			
			//1、根据当前的分类ID获取子分类
			$cateModel= D('Category');
			$tree = $cateModel->getChildren($cate_id);
			//将提交的当前分类的ID追加到数组中 $tree记录了提交的商品分类ID及子分类ID
			$tree[]=$cate_id;
			//将$tree转换为字符串格式
			$children = implode(',', $tree);
			
			//获取扩展分类的商品ID
			$ext_goods_ids = M('GoodsCate')->group('goods_id')->where("cate_id in ($children)")->select();
			if($ext_goods_ids){
				foreach ($ext_goods_ids as $key => $value) {
					$goods_ids[]=$value['goods_id'];
				}
				//将数组转换为字符串格式
				$goods_ids=implode(',',$goods_ids);
			}
			//组合where子句
			if(!$goods_ids){
				//没有商品的扩展分类满足条件
				$where .= " AND cate_id in ($children)";
			}else{
				$where .= " AND (cate_id in ($children) OR id in ($goods_ids))";
			}
		}

		//接受提交的推荐状态
		$intro_type = I('get.intro_type');
		if($intro_type){
			//限制只能使用此三个推荐作为条件
			if($intro_type == 'is_new' || $intro_type =='is_rec' || $intro_type=='is_hot'){
				$where .= " AND $intro_type = 1";
			}
		}

		//接受上下架
		$is_sale = intval(I('get.is_sale'));
		if($is_sale==1){
			//表单提交的1表示上架
			$where .= " AND is_sale =1";
		}elseif ($is_sale==2) {
			//表示表单提交的是下架状态
			$where .= " AND is_sale =0";
		}
		//接受关键词
		$keyword = I('get.keyword');
		if($keyword){
			$where .= " AND goods_name like '%$keyword%'";
		}
		
		$count = $this->where($where)->count();
		//3、计算出分页导航
		$page = new \Think\Page($count,$pagesize);
		$show = $page->show();
		//5、获取当前的页码
		$p = intval(I('get.p'));
		//6、获取具体的数据
		$data = $this->where($where)->page($p,$pagesize)->select();
		//返回数据时需要将数据及分页的导航数据都返回
		return  array('pageStr'=>$show,'data'=>$data);
	}

	public function setStatus($goods_id,$isdel=0)
	{
		return $this->where("id=$goods_id")->setField('isdel',$isdel);
	}

	public function uploadImg()
	{
				//判断是否有图片上传
		if(!isset($_FILES['goods_img']) || $_FILES['goods_img']['error']!=0){
			return false;
		}
		//实现图片上传
		$upload = new \Think\Upload();
		$info = $upload->uploadOne($_FILES['goods_img']);
		if(!$info){
			$this->error=$upload->getError();
		}
		//在代码中图片的地址使用不用使用/ 在html代码中显示图片时必须使用/ 表示域名对应的根目录
		//上传之后的图片地址
		$goods_img = 'Uploads/'.$info['savepath'].$info['savename'];
		//实现缩略图的制作
		$img = new \Think\Image();
		//打开图片
		$img->open($goods_img);
		//制作缩略图
		$goods_thumb = 'Uploads/'.$info['savepath'].'thumb_'.$info['savename'];
		$img->thumb(450,450)->save($goods_thumb);
		//返回图片地址
		return array('goods_img'=>$goods_img,'goods_thumb'=>$goods_thumb);
	}

	//商品彻底删除
	public function remove($goods_id)
	{
		//1、删除商品的图片
		$goods_info = $this->findOneById($goods_id);
		if(!$goods_info){
			return false;
		}
		unlink($goods_info['goods_img']);
		unlink($goods_info['goods_thumb']);
		//2、删除商品的扩展分类
		D('GoodsCate')->where("goods_id = $goods_id")->delete();
		//3、删除商品的基本信息
		$this->where("id = $goods_id")->delete();
		return true;
	}

}