<?php

$_LANG = array(
    'select_method' => 'How to choose products:',
    'by_cat' => 'According to commodity classification and brand',
    'by_sn' => 'According to the article number',
    'select_cat' => 'Select commodity classification',
    'please_select_cat' => 'Please select category',
    'select_brand' => 'Choose brand',
    'goods_list' => 'List of goods',
    'src_list' => 'List of alternatives',
    'dest_list' => 'The selected list',
    'input_sn' => 'Enter item number (only one item number per row)',
    'edit_method' => 'Editing mode:',
    'edit_each' => 'edit',
    'edit_all' => 'Unified edit',
    'go_edit' => 'Enter the edit',
    'notice_edit' => 'The membership price of -1 means that the membership price will be calculated according to the discount proportion of the membership level',
    'goods_class' => 'Commodity categories',
    'g_class' =>
        array(
            1 => 'Physical commodity',
            0 => 'Virtual card',
        ),
    'goods_sn' => 'The article number',
    'goods_name' => 'Name of commodity',
    'market_price' => 'The market price',
    'shop_price' => 'Our price',
    'integral' => 'Integral to buy',
    'give_integral' => 'Present integral',
    'goods_number' => 'inventory',
    'brand' => 'brand',
    'attribute' => 'attribute',
    'batch_edit_ok' => 'Batch modification successful',
    'export_format' => 'The data format',
    'export_dscmall' => 'Dscmall supports data formats',
    'export_taobao' => 'Taobao assistant support data format',
    'export_taobao46' => 'Taobao assistant 4.6 supports data format',
    'export_paipai' => 'Pat assistant supports data format',
    'export_paipai3' => 'Patpats assistant 3.0 supports data formats',
    'goods_cat' => 'Classification:',
    'csv_file' => 'Upload bulk CSV file:',
    'notice_file' => '(the quantity of commodities to be uploaded in CSV file should not exceed 40, and the size of CSV file should not exceed 500K.)',
    'file_charset' => 'File code:',
    'download_file' => 'Download bulk CSV file (%s)',
    'use_help' => '<li>According to usage habits, download CSV files in corresponding languages. For example, mainland Chinese users download files in simplified Chinese language and Hong Kong and Taiwan users download files in traditional Chinese language. </li>
  <li> fill CSV file, you can use excel or text editor to open CSV file; <br/ > whether "boutique", fill in the Numbers 0 or 1, 0 on behalf of the "no", 1 on behalf of "is"; <br/> commodity pictures and commodity thumbnails please fill in the file name of the picture with the path, where the path is relative to [root]/images/, for example, the path is [root]/images/200610/abc.jpg, just fill in 200610/abc.jpg; <br/ > <font style = "color: FE596A;" If > is taobao assistant format, please ensure that the CVS code is the code in the website. If the code is incorrect, you can use editing software to convert the code. </font> <br/> <font style = "color: FE596A;" > please upload the commodity picture and commodity thumbnail first and then upload the CSV file, otherwise the picture cannot be processed. </font> </li>',
    'js_languages' =>
        array(
            'please_select_goods' => 'Please choose the goods',
            'please_input_sn' => 'Please enter the item number',
            'goods_cat_not_leaf' => 'Please select the bottom category',
            'please_select_cat' => 'Please select your category',
            'please_upload_file' => 'Please upload bulk CSV files',
        ),
    'upload_goods' =>
        array(
            'goods_name' => 'Name of commodity',
            'goods_sn' => 'Commodity item no',
            'brand_name' => 'Commodity brand',
            'market_price' => 'The market price',
            'shop_price' => 'This shop sells',
            'integral' => 'Point purchase limit',
            'original_img' => 'Commodity original drawing',
            'goods_img' => 'Commodity images',
            'goods_thumb' => 'Commodity thumbnail',
            'keywords' => 'Key words of goods',
            'goods_brief' => 'A brief description',
            'goods_desc' => 'A detailed description',
            'goods_weight' => 'Commodity weight (kg)',
            'goods_number' => 'Inventory quantity',
            'warn_number' => 'Inventory warning quantity',
            'is_best' => 'Whether the high-quality goods',
            'is_new' => 'Whether the new product',
            'is_hot' => 'If sell like hot cakes',
            'is_on_sale' => 'Whether the shelf',
            'is_alone_sale' => 'Can be sold as ordinary goods',
            'is_real' => 'Whether it is a physical commodity',
        ),
    'batch_upload_ok' => 'Batch upload successful',
    'goods_upload_confirm' => 'Batch upload confirmation',
    'upload_goods_lib' =>
        array(
            'goods_name' => 'Name of commodity',
            'goods_sn' => 'Commodity item no',
            'brand_name' => 'Commodity brand',
            'market_price' => 'The market price',
            'shop_price' => 'This shop sells',
            'original_img' => 'Commodity original drawing',
            'goods_img' => 'Commodity images',
            'goods_thumb' => 'Commodity thumbnail',
            'keywords' => 'Key words of goods',
            'goods_brief' => 'A brief description',
            'goods_desc' => 'A detailed description',
            'goods_weight' => 'Commodity weight (kg)',
            'is_on_sale' => 'Whether the shelf',
            'is_real' => 'Whether it is a physical commodity',
        ),
    'operation_prompt_content' =>
        array(
            'confirm' =>
                array(
                    0 => 'First, download the CSV file, open the excel spreadsheet, and add multiple product information fields.',
                    1 => 'Upload edited CSV files, select data format, classification and coding to upload files.',
                ),
            'select' =>
                array(
                    0 => 'Search for items by category, brand, or item number, and select items that need to be bulk edited from the selected list.',
                    1 => 'Select edit individually or uniformly, and click edit to start editing.',
                    2 => 'Edit one by one in the selected product edit list, such as modifying market price, local price, bonus points, inventory and other information.',
                    3 => 'Unified editing is to select the commodities that need unified information for editing.',
                ),
            'edit' =>
                array(
                    0 => 'You can manually add a new member from the management platform and fill in the relevant information.',
                    1 => 'After the new member, the data can be found from the member list and edited again, but the name of the member cannot be changed.',
                ),
        ),
);


return $_LANG;
