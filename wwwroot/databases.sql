#创建数据库
create database jxshop;

#创建分类数据表
CREATE TABLE `jx_category` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `cname` char(50) NOT NULL DEFAULT '' COMMENT '分类名称',
  `parent_id` smallint(6) NOT NULL DEFAULT '0' COMMENT '分类的父分类ID',
  `isrec` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否推荐 0表示不推荐1表示推荐',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#创建商品数据表
CREATE TABLE `jx_goods` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '商品ID',
  `goods_name` varchar(255) NOT NULL DEFAULT '' COMMENT '商品名称',
  `goods_sn` char(30) NOT NULL DEFAULT '' COMMENT '商品货号',
  `cate_id` smallint(6) NOT NULL DEFAULT '0' COMMENT '商品分类ID 对于category表中的ID字段',
  `market_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '市场售价',
  `shop_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '本店售价',
  `goods_img` varchar(255) NOT NULL DEFAULT '' COMMENT '商品缩略图',
  `goods_thumb` varchar(255) NOT NULL DEFAULT '' COMMENT '商品缩略小图',
  `goods_body` text COMMENT '商品描述',
  `is_hot` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否热卖 1表示热卖 0表示不是',
  `is_rec` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否推荐 1表示推荐 0表示不推荐',
  `is_new` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否热卖 1表示新品 0表示不是',
  `addtime` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  `isdel` tinyint(4) NOT NULL DEFAULT '1' COMMENT '表示商品是否删除 1正常 0删除状态',
  `is_sale` tinyint(4) NOT NULL DEFAULT '1' COMMENT '商品是否销售 1销售 0下架状态',
  PRIMARY KEY (`id`),
  UNIQUE KEY `goods_sn` (`goods_sn`) USING BTREE,
  KEY `goods_name` (`goods_name`) USING BTREE,
  KEY `isdel` (`isdel`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#创建商品扩展分类的中间表
CREATE TABLE jx_goods_cate(
  id int(11) NOT NULL AUTO_INCREMENT,
  goods_id int(11) NOT NULL DEFAULT 0 COMMENT '商品ID标识',
  cate_id smallint(6) NOT NULL DEFAULT 0 COMMENT '分类ID标识',
  PRIMARY KEY(id)
)ENGINE= InnoDB DEFAULT CHARSET=utf8;