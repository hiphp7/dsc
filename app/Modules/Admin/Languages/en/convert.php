<?php

$_LANG = array(
    'confirm_convert' => 'Note: executing the conversion procedure will cause the existing data to be lost, please think twice!!!',
    'backup_data' => 'If the existing data may be valuable to you, please backup it first.',
    'backup' => 'Go back up now',
    'select_system' => 'Please select the system you want to convert:',
    'note_select_system' => 'If your system is not in the list on the left, you can go to & NBSP; <a href="http://www.ecmoban.com" class="red" target="_blank"> our website </a>& NBSP; Ask for help)',
    'select_charset' => 'Please select the character set for the system you want to convert:',
    'note_select_charset' => '(if your system does not use the utf-8 character set, the conversion may take longer)',
    'dir_notes' => 'Note that the path to the root directory of the original mall is relative to the admin directory. <br /> for example: if the directory of the original mall is in the root directory of shop, and dscmall is in the root directory, then the path is.. / shop',
    'your_config' => 'Please set the configuration information of the original system:',
    'your_host' => 'Host name or address:',
    'your_user' => 'Login account:',
    'your_pass' => 'Login password:',
    'your_db' => 'Database name:',
    'your_prefix' => 'Database table prefix:',
    'your_path' => 'Original store root directory:',
    'convert' => 'Transform the data',
    'remark' => 'Remark:',
    'remark_info' => 'For special items, you need to edit the original price (the store price) and promotion period; </li><li> please reset the watermark; </li><li> please reset the advertisement; </li><li> please reset the delivery mode; </li><li> please reset the payment method; </li><li> please transfer the goods which are not in the final category to the final category; </li> </ul>',
    'connect_db_error' => 'Unable to connect to database, check configuration information.',
    'table_error' => 'The required table %s is missing. Please check the configuration information.',
    'dir_error' => 'Missing the required directory %s, please check the configuration information.',
    'dir_not_readable' => 'The directory is not readable %s',
    'dir_not_writable' => 'The directory cannot write %s',
    'file_not_readable' => 'The file is not readable %s',
    'js_languages' =>
        array(
            'check_your_db' => 'Checking your system\'s database...',
            'act_ok' => 'Congratulations! Successful operation!',
            'no_system' => 'No conversion program is available',
            'host_not_null' => 'Host name or address cannot be empty',
            'db_not_null' => 'The database name cannot be empty',
            'user_not_null' => 'The login account cannot be empty',
            'path_not_null' => 'The original mall root directory cannot be empty',
        ),
);


return $_LANG;
