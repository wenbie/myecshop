<?php 
namespace Admin\Model;

/**
* 商品扩展分类模型
*/
class GoodsCateModel extends CommonModel
{
	//将商品的扩展分类写入数据库
	public function insertExtCate($ext_cate_id,$goods_id)
	{
		//对提交的数据进行去重
		$ext_cate_id = array_unique($ext_cate_id);
		foreach ($ext_cate_id as $key => $value) {
			if($value !=0){
				$list[]=array('goods_id'=>$goods_id,'cate_id'=>$value);
			}
		}
		//批量写入数据  注意 对于写入数据的数组要求索引从0开始并且是数字小标
		$this->addAll($list);
	}
}