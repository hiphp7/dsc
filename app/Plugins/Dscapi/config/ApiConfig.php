<?php

namespace App\Plugins\Dscapi\config;

class ApiConfig
{
    public function getConfig()
    {
        $api_data['zh-CN'] = array(
            array(
                'name' => '用户',
                'cat' => 'user',
                'list' => array(
                    array(
                        'name' => '获取会员列表',
                        'val' => 'dsc.user.list.get'
                    ),
                    array(
                        'name' => '获取单条会员信息',
                        'val' => 'dsc.user.info.get'
                    ),
                    array(
                        'name' => '插入会员信息',
                        'val' => 'dsc.user.insert.post'
                    ),
                    array(
                        'name' => '更新会员信息',
                        'val' => 'dsc.user.update.post'
                    ),
                    array(
                        'name' => '删除会员信息',
                        'val' => 'dsc.user.del.post'
                    ),
                    array(
                        'name' => '获取会员等级列表',
                        'val' => 'dsc.user.rank.list.get'
                    ),
                    array(
                        'name' => '获取单条会员等级信息',
                        'val' => 'dsc.user.rank.info.get'
                    ),
                    array(
                        'name' => '插入会员等级信息',
                        'val' => 'dsc.user.rank.insert.post'
                    ),
                    array(
                        'name' => '更新会员等级信息',
                        'val' => 'dsc.user.rank.update.post'
                    ),
                    array(
                        'name' => '删除会员等级信息',
                        'val' => 'dsc.user.rank.del.post'
                    )
                )
            ),
            array(
                'name' => '类目',
                'cat' => 'category',
                'list' => array(
                    array(
                        'name' => '获取分类列表',
                        'val' => 'dsc.category.list.get'
                    ),
                    array(
                        'name' => '获取单条分类信息',
                        'val' => 'dsc.category.info.get'
                    ),
                    array(
                        'name' => '插入分类信息',
                        'val' => 'dsc.category.insert.post'
                    ),
                    array(
                        'name' => '更新分类信息',
                        'val' => 'dsc.category.update.post'
                    ),
                    array(
                        'name' => '删除分类信息',
                        'val' => 'dsc.category.del.post'
                    ),
                    array(
                        'name' => '获取商家分类列表',
                        'val' => 'dsc.category.seller.list.get'
                    ),
                    array(
                        'name' => '获取单条商家分类信息',
                        'val' => 'dsc.category.seller.info.get'
                    ),
                    array(
                        'name' => '插入商家分类信息',
                        'val' => 'dsc.category.seller.insert.post'
                    ),
                    array(
                        'name' => '更新商家分类信息',
                        'val' => 'dsc.category.seller.update.post'
                    ),
                    array(
                        'name' => '删除商家分类信息',
                        'val' => 'dsc.category.seller.del.post'
                    )
                )
            ), array(
                'name' => '商品',
                'cat' => 'goods',
                'list' => array(
                    array(
                        'name' => '获取商品列表',
                        'val' => 'dsc.goods.list.get'
                    ),
                    array(
                        'name' => '获取单条商品信息',
                        'val' => 'dsc.goods.info.get'
                    ),
                    array(
                        'name' => '插入商品信息',
                        'val' => 'dsc.goods.insert.post'
                    ),
                    array(
                        'name' => '更新商品信息',
                        'val' => 'dsc.goods.update.post'
                    ),
                    array(
                        'name' => '删除商品信息',
                        'val' => 'dsc.goods.del.post'
                    ),
                    array(
                        'name' => '获取商品仓库列表',
                        'val' => 'dsc.goods.warehouse.list.get'
                    ),
                    array(
                        'name' => '获取单条商品仓库信息',
                        'val' => 'dsc.goods.warehouse.info.get'
                    ),
                    array(
                        'name' => '插入商品仓库信息',
                        'val' => 'dsc.goods.warehouse.insert.post'
                    ),
                    array(
                        'name' => '更新商品仓库信息',
                        'val' => 'dsc.goods.warehouse.update.post'
                    ),
                    array(
                        'name' => '删除商品仓库信息',
                        'val' => 'dsc.goods.warehouse.del.post'
                    ),
                    array(
                        'name' => '获取商品地区列表',
                        'val' => 'dsc.goods.area.list.get'
                    ),
                    array(
                        'name' => '获取单条商品地区信息',
                        'val' => 'dsc.goods.area.info.get'
                    ),
                    array(
                        'name' => '插入商品地区信息',
                        'val' => 'dsc.goods.area.insert.post'
                    ),
                    array(
                        'name' => '更新商品地区信息',
                        'val' => 'dsc.goods.area.update.post'
                    ),
                    array(
                        'name' => '删除商品地区信息',
                        'val' => 'dsc.goods.area.del.post'
                    ),
                    array(
                        'name' => '获取商品相册列表',
                        'val' => 'dsc.goods.gallery.list.get'
                    ),
                    array(
                        'name' => '获取单条商品相册信息',
                        'val' => 'dsc.goods.gallery.info.get'
                    ),
                    array(
                        'name' => '插入商品相册信息',
                        'val' => 'dsc.goods.gallery.insert.post'
                    ),
                    array(
                        'name' => '更新商品相册信息',
                        'val' => 'dsc.goods.gallery.update.post'
                    ),
                    array(
                        'name' => '删除商品相册信息',
                        'val' => 'dsc.goods.gallery.del.post'
                    ),
                    array(
                        'name' => '获取商品属性列表',
                        'val' => 'dsc.goods.attr.list.get'
                    ),
                    array(
                        'name' => '获取单条商品属性信息',
                        'val' => 'dsc.goods.attr.info.get'
                    ),
                    array(
                        'name' => '插入商品属性信息',
                        'val' => 'dsc.goods.attr.insert.post'
                    ),
                    array(
                        'name' => '更新商品属性信息',
                        'val' => 'dsc.goods.attr.update.post'
                    ),
                    array(
                        'name' => '删除商品属性信息',
                        'val' => 'dsc.goods.attr.del.post'
                    ),
                    array(
                        'name' => '获取商品运费模板列表',
                        'val' => 'dsc.goods.freight.list.get'
                    ),
                    array(
                        'name' => '获取单条商品运费模板信息',
                        'val' => 'dsc.goods.freight.info.get'
                    ),
                    array(
                        'name' => '插入商品运费模板信息',
                        'val' => 'dsc.goods.freight.insert.post'
                    ),
                    array(
                        'name' => '更新商品运费模板信息',
                        'val' => 'dsc.goods.freight.update.post'
                    ),
                    array(
                        'name' => '删除商品运费模板信息',
                        'val' => 'dsc.goods.freight.del.post'
                    ),
                )
            ),
            array(
                'name' => '品牌',
                'cat' => 'brand',
                'list' => array(
                    array(
                        'name' => '获取品牌列表',
                        'val' => 'dsc.brand.list.get'
                    ),
                    array(
                        'name' => '获取单条品牌信息',
                        'val' => 'dsc.brand.info.get'
                    ),
                    array(
                        'name' => '插入品牌信息',
                        'val' => 'dsc.brand.insert.post'
                    ),
                    array(
                        'name' => '更新品牌信息',
                        'val' => 'dsc.brand.update.post'
                    ),
                    array(
                        'name' => '删除品牌信息',
                        'val' => 'dsc.brand.del.post'
                    )
                )
            ),
            array(
                'name' => '交易',
                'cat' => 'order',
                'list' => array(
                    array(
                        'name' => '获取订单列表',
                        'val' => 'dsc.order.list.get'
                    ),
                    array(
                        'name' => '获取单条订单信息',
                        'val' => 'dsc.order.info.get'
                    ),
                    array(
                        'name' => '插入订单信息',
                        'val' => 'dsc.order.insert.post'
                    ),
                    array(
                        'name' => '更新订单信息',
                        'val' => 'dsc.order.update.post'
                    ),
                    array(
                        'name' => '删除订单信息',
                        'val' => 'dsc.order.del.post'
                    ),
                    array(
                        'name' => '获取订单商品列表',
                        'val' => 'dsc.order.goods.list.get'
                    ),
                    array(
                        'name' => '获取单条订单商品信息',
                        'val' => 'dsc.order.goods.info.get'
                    ),
                    array(
                        'name' => '插入订单商品信息',
                        'val' => 'dsc.order.goods.insert.post'
                    ),
                    array(
                        'name' => '更新订单商品信息',
                        'val' => 'dsc.order.goods.update.post'
                    ),
                    array(
                        'name' => '删除订单商品信息',
                        'val' => 'dsc.order.goods.del.post'
                    )
                )
            ),
            array(
                'name' => '属性类型',
                'cat' => 'goodstype',
                'list' => array(
                    array(
                        'name' => '获取属性类型列表',
                        'val' => 'dsc.goodstype.list.get'
                    ),
                    array(
                        'name' => '获取单条属性类型信息',
                        'val' => 'dsc.goodstype.info.get'
                    ),
                    array(
                        'name' => '插入属性类型信息',
                        'val' => 'dsc.goodstype.insert.post'
                    ),
                    array(
                        'name' => '更新属性类型信息',
                        'val' => 'dsc.goodstype.update.post'
                    ),
                    array(
                        'name' => '删除属性类型信息',
                        'val' => 'dsc.goodstype.del.post'
                    ),
                    array(
                        'name' => '获取属性列表',
                        'val' => 'dsc.attribute.list.get'
                    ),
                    array(
                        'name' => '获取单条属性信息',
                        'val' => 'dsc.attribute.info.get'
                    ),
                    array(
                        'name' => '插入属性信息',
                        'val' => 'dsc.attribute.insert.post'
                    ),
                    array(
                        'name' => '更新属性信息',
                        'val' => 'dsc.attribute.update.post'
                    ),
                    array(
                        'name' => '删除属性信息',
                        'val' => 'dsc.attribute.del.post'
                    )
                )
            ),
            array(
                'name' => '地区',
                'cat' => 'region',
                'list' => array(
                    array(
                        'name' => '获取地区列表',
                        'val' => 'dsc.region.list.get'
                    ),
                    array(
                        'name' => '获取单条地区信息',
                        'val' => 'dsc.region.info.get'
                    ),
                    array(
                        'name' => '插入地区信息',
                        'val' => 'dsc.region.insert.post'
                    ),
                    array(
                        'name' => '更新地区信息',
                        'val' => 'dsc.region.update.post'
                    ),
                    array(
                        'name' => '删除地区信息',
                        'val' => 'dsc.region.del.post'
                    )
                )
            ),
            array(
                'name' => '仓库地区',
                'cat' => 'warehouse',
                'list' => array(
                    array(
                        'name' => '获取仓库地区列表',
                        'val' => 'dsc.warehouse.list.get'
                    ),
                    array(
                        'name' => '获取单条仓库地区信息',
                        'val' => 'dsc.warehouse.info.get'
                    ),
                    array(
                        'name' => '插入仓库地区信息',
                        'val' => 'dsc.warehouse.insert.post'
                    ),
                    array(
                        'name' => '更新仓库地区信息',
                        'val' => 'dsc.warehouse.update.post'
                    ),
                    array(
                        'name' => '删除仓库地区信息',
                        'val' => 'dsc.warehouse.del.post'
                    )
                )
            ),
        );

        $api_data['en'] = array(
            array(
                'name' => 'user',
                'cat' => 'user',
                'list' => array(
                    array(
                        'name' => 'Get membership list',
                        'val' => 'dsc.user.list.get'
                    ),
                    array(
                        'name' => 'Get individual member ',
                        'val' => 'dsc.user.info.get'
                    ),
                    array(
                        'name' => 'Insert member ',
                        'val' => 'dsc.user.insert.post'
                    ),
                    array(
                        'name' => 'Update member',
                        'val' => 'dsc.user.update.post'
                    ),
                    array(
                        'name' => 'Delete member',
                        'val' => 'dsc.user.del.post'
                    ),
                    array(
                        'name' => 'Get membership rank list',
                        'val' => 'dsc.user.rank.list.get'
                    ),
                    array(
                        'name' => 'Get individual membership level ',
                        'val' => 'dsc.user.rank.info.get'
                    ),
                    array(
                        'name' => 'Insert membership rank',
                        'val' => 'dsc.user.rank.insert.post'
                    ),
                    array(
                        'name' => 'Update membership',
                        'val' => 'dsc.user.rank.update.post'
                    ),
                    array(
                        'name' => 'Delete membership rank',
                        'val' => 'dsc.user.rank.del.post'
                    )
                )
            ),
            array(
                'name' => 'category',
                'cat' => 'category',
                'list' => array(
                    array(
                        'name' => 'Get the category list',
                        'val' => 'dsc.category.list.get'
                    ),
                    array(
                        'name' => 'Get single category ',
                        'val' => 'dsc.category.info.get'
                    ),
                    array(
                        'name' => 'Insert classification',
                        'val' => 'dsc.category.insert.post'
                    ),
                    array(
                        'name' => 'Update classification',
                        'val' => 'dsc.category.update.post'
                    ),
                    array(
                        'name' => 'Delete categorization',
                        'val' => 'dsc.category.del.post'
                    ),
                    array(
                        'name' => 'Get the merchant category list',
                        'val' => 'dsc.category.seller.list.get'
                    ),
                    array(
                        'name' => 'Get a single merchant classification',
                        'val' => 'dsc.category.seller.info.get'
                    ),
                    array(
                        'name' => 'Insert merchant classification',
                        'val' => 'dsc.category.seller.insert.post'
                    ),
                    array(
                        'name' => 'Update merchant classification',
                        'val' => 'dsc.category.seller.update.post'
                    ),
                    array(
                        'name' => 'Delete merchant classification',
                        'val' => 'dsc.category.seller.del.post'
                    )
                )
            ), array(
                'name' => 'goods',
                'cat' => 'goods',
                'list' => array(
                    array(
                        'name' => 'Get the list of items',
                        'val' => 'dsc.goods.list.get'
                    ),
                    array(
                        'name' => 'Get individual item ',
                        'val' => 'dsc.goods.info.get'
                    ),
                    array(
                        'name' => 'Insert product',
                        'val' => 'dsc.goods.insert.post'
                    ),
                    array(
                        'name' => 'Update product',
                        'val' => 'dsc.goods.update.post'
                    ),
                    array(
                        'name' => 'Delete product',
                        'val' => 'dsc.goods.del.post'
                    ),
                    array(
                        'name' => 'Get the goods warehouse',
                        'val' => 'dsc.goods.warehouse.list.get'
                    ),
                    array(
                        'name' => 'Get single item warehouse ',
                        'val' => 'dsc.goods.warehouse.info.get'
                    ),
                    array(
                        'name' => 'Insert merchandise warehouse',
                        'val' => 'dsc.goods.warehouse.insert.post'
                    ),
                    array(
                        'name' => 'Update warehouse ',
                        'val' => 'dsc.goods.warehouse.update.post'
                    ),
                    array(
                        'name' => 'Delete the goods warehouse',
                        'val' => 'dsc.goods.warehouse.del.post'
                    ),
                    array(
                        'name' => 'Get the commodity locale',
                        'val' => 'dsc.goods.area.list.get'
                    ),
                    array(
                        'name' => 'Get a single commodity location',
                        'val' => 'dsc.goods.area.info.get'
                    ),
                    array(
                        'name' => 'Insert commodity locale ',
                        'val' => 'dsc.goods.area.insert.post'
                    ),
                    array(
                        'name' => 'Update product area',
                        'val' => 'dsc.goods.area.update.post'
                    ),
                    array(
                        'name' => 'Delete product area',
                        'val' => 'dsc.goods.area.del.post'
                    ),
                    array(
                        'name' => 'Get the item album list',
                        'val' => 'dsc.goods.gallery.list.get'
                    ),
                    array(
                        'name' => 'Get single item album',
                        'val' => 'dsc.goods.gallery.info.get'
                    ),
                    array(
                        'name' => 'Insert product album',
                        'val' => 'dsc.goods.gallery.insert.post'
                    ),
                    array(
                        'name' => 'Update product album',
                        'val' => 'dsc.goods.gallery.update.post'
                    ),
                    array(
                        'name' => 'Delete product album',
                        'val' => 'dsc.goods.gallery.del.post'
                    ),
                    array(
                        'name' => 'Get  item property list',
                        'val' => 'dsc.goods.attr.list.get'
                    ),
                    array(
                        'name' => 'Get a single item attribute',
                        'val' => 'dsc.goods.attr.info.get'
                    ),
                    array(
                        'name' => 'Insert product attribute',
                        'val' => 'dsc.goods.attr.insert.post'
                    ),
                    array(
                        'name' => 'Update product attribute',
                        'val' => 'dsc.goods.attr.update.post'
                    ),
                    array(
                        'name' => 'Delete  product attribute',
                        'val' => 'dsc.goods.attr.del.post'
                    ),
                    array(
                        'name' => 'Get  item shipping template',
                        'val' => 'dsc.goods.freight.list.get'
                    ),
                    array(
                        'name' => 'Get shipping template for single item',
                        'val' => 'dsc.goods.freight.info.get'
                    ),
                    array(
                        'name' => 'Insert item shipping template',
                        'val' => 'dsc.goods.freight.insert.post'
                    ),
                    array(
                        'name' => 'Update item shipping template',
                        'val' => 'dsc.goods.freight.update.post'
                    ),
                    array(
                        'name' => 'Delete item shipping template',
                        'val' => 'dsc.goods.freight.del.post'
                    ),
                )
            ),
            array(
                'name' => 'brand',
                'cat' => 'brand',
                'list' => array(
                    array(
                        'name' => 'Get the brand list',
                        'val' => 'dsc.brand.list.get'
                    ),
                    array(
                        'name' => 'Get individual brand',
                        'val' => 'dsc.brand.info.get'
                    ),
                    array(
                        'name' => 'Insert brand',
                        'val' => 'dsc.brand.insert.post'
                    ),
                    array(
                        'name' => 'Update brand',
                        'val' => 'dsc.brand.update.post'
                    ),
                    array(
                        'name' => 'Delete brand',
                        'val' => 'dsc.brand.del.post'
                    )
                )
            ),
            array(
                'name' => 'order',
                'cat' => 'order',
                'list' => array(
                    array(
                        'name' => 'Get order list',
                        'val' => 'dsc.order.list.get'
                    ),
                    array(
                        'name' => 'Get single order',
                        'val' => 'dsc.order.info.get'
                    ),
                    array(
                        'name' => 'Insert order',
                        'val' => 'dsc.order.insert.post'
                    ),
                    array(
                        'name' => 'Update order',
                        'val' => 'dsc.order.update.post'
                    ),
                    array(
                        'name' => 'Delete order ',
                        'val' => 'dsc.order.del.post'
                    ),
                    array(
                        'name' => 'Get list of items for the order',
                        'val' => 'dsc.order.goods.list.get'
                    ),
                    array(
                        'name' => 'Get item for a single order',
                        'val' => 'dsc.order.goods.info.get'
                    ),
                    array(
                        'name' => 'Insert the order item',
                        'val' => 'dsc.order.goods.insert.post'
                    ),
                    array(
                        'name' => 'Update order',
                        'val' => 'dsc.order.goods.update.post'
                    ),
                    array(
                        'name' => 'Delete order item',
                        'val' => 'dsc.order.goods.del.post'
                    )
                )
            ),
            array(
                'name' => 'goodstype',
                'cat' => 'goodstype',
                'list' => array(
                    array(
                        'name' => 'Gets a list of property types',
                        'val' => 'dsc.goodstype.list.get'
                    ),
                    array(
                        'name' => 'Gets single attribute type',
                        'val' => 'dsc.goodstype.info.get'
                    ),
                    array(
                        'name' => 'Insert attribute type',
                        'val' => 'dsc.goodstype.insert.post'
                    ),
                    array(
                        'name' => 'Update property type',
                        'val' => 'dsc.goodstype.update.post'
                    ),
                    array(
                        'name' => 'Delete property type',
                        'val' => 'dsc.goodstype.del.post'
                    ),
                    array(
                        'name' => 'Get property list',
                        'val' => 'dsc.attribute.list.get'
                    ),
                    array(
                        'name' => 'Gets single attribute',
                        'val' => 'dsc.attribute.info.get'
                    ),
                    array(
                        'name' => 'Insert attribute ',
                        'val' => 'dsc.attribute.insert.post'
                    ),
                    array(
                        'name' => 'Update attribute',
                        'val' => 'dsc.attribute.update.post'
                    ),
                    array(
                        'name' => 'Delete attribute ',
                        'val' => 'dsc.attribute.del.post'
                    )
                )
            ),
            array(
                'name' => 'region',
                'cat' => 'region',
                'list' => array(
                    array(
                        'name' => 'Get the locale list',
                        'val' => 'dsc.region.list.get'
                    ),
                    array(
                        'name' => 'Get individual locale',
                        'val' => 'dsc.region.info.get'
                    ),
                    array(
                        'name' => 'Insert locale ',
                        'val' => 'dsc.region.insert.post'
                    ),
                    array(
                        'name' => 'Update regional ',
                        'val' => 'dsc.region.update.post'
                    ),
                    array(
                        'name' => 'Delete area ',
                        'val' => 'dsc.region.del.post'
                    )
                )
            ),
            array(
                'name' => 'warehouse',
                'cat' => 'warehouse',
                'list' => array(
                    array(
                        'name' => 'Gets list warehouse areas',
                        'val' => 'dsc.warehouse.list.get'
                    ),
                    array(
                        'name' => 'Get single warehouse area',
                        'val' => 'dsc.warehouse.info.get'
                    ),
                    array(
                        'name' => 'Insert warehouse area',
                        'val' => 'dsc.warehouse.insert.post'
                    ),
                    array(
                        'name' => 'Update warehouse area',
                        'val' => 'dsc.warehouse.update.post'
                    ),
                    array(
                        'name' => 'Delete the warehouse area',
                        'val' => 'dsc.warehouse.del.post'
                    )
                )
            ),
        );

        return $api_data;
    }
}
