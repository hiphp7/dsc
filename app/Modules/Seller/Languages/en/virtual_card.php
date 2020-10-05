<?php

$_LANG = array(
    'virtual_card_list' => 'Virtual goods list',
    'lab_goods_name' => 'Name of commodity',
    'replenish' => 'replenishment',
    'lab_card_id' => 'Serial number',
    'lab_card_sn' => 'Card number',
    'lab_card_password' => 'Card password',
    'lab_end_date' => 'Up to date',
    'lab_is_saled' => 'Sold or not',
    'lab_order_sn' => 'The order number',
    'action_success' => 'Operation is successful',
    'action_fail' => 'The operation failure',
    'card' => 'Card list',
    'virtual_card' => 'Virtual card',
    'batch_card_add' => 'Add replenishment in bulk',
    'download_file' => 'Download bulk CSV files',
    'separator' => 'The separator',
    'uploadfile' => 'Upload a file',
    'sql_error' => 'Error message in article %s: <br />',
    'notice_file' => '(the quantity of commodities to be uploaded in CSV file should not exceed 40, and the size of CSV file should not exceed 500K.)',
    'replenish_no_goods_id' => 'The item ID parameter is missing, so the replenishment operation cannot be carried out',
    'replenish_no_get_goods_name' => 'The item ID parameter is incorrect, the item name cannot be obtained',
    'drop_card_success' => 'The record was deleted successfully',
    'batch_drop' => 'Batch delete',
    'drop_card_confirm' => 'Are you sure you want to delete the record?',
    'card_sn_exist' => 'The card number %s already exists. Please reenter it',
    'go_list' => 'Returns the replenishment list',
    'continue_add' => 'Continue to replenishment',
    'uploadfile_fail' => 'File upload failed',
    'batch_card_add_ok' => 'The replenishment information of %s bar has been successfully added',
    'js_languages' =>
        array(
            'no_card_sn' => 'Both the card number and the card password cannot be empty.',
            'separator_not_null' => 'The separator cannot be empty.',
            'uploadfile_not_null' => 'Please select the file to upload.',
            'card_sn_null' => 'Please enter the card number.',
            'card_password_null' => 'Please enter the card number and password.',
            'updating_info' => '<strong> is being updated </strong> (100 records at a time)',
            'updated_info' => '<strong> updated </strong> <span id=\\"updated\\">0</span> records.',
        ),
    'use_help' => '<ul><li> upload file should be CSV file <br />CSV file first column card number; Second column card password; The third column is used up to date. <br />(use EXCEL to create CSV file method: fill in data in EXCEL according to card number, card password, as of date, and directly save as CSV file after completion)</li><li> password, and as of date can be empty, as of date format is 2006-11-6 or 2006/11/6</li><li> card number, card password, as of date do not use Chinese </li></ul>',
    'virtual_card_change' => 'Change encryption string',
    'user_guide' => '<h4> instructions: </h4><ul><li> encryption string is used to encrypt the card number and password of the virtual card commodity </li><li> encryption string saved in the file data/config.php, the corresponding constant is AUTH_KEY</li><li> if you want to change the encryption string in the text box below input the original encryption string and new encryption string, click \'ok\' button after </li></ul>',
    'label_old_string' => 'The original encrypted string',
    'label_new_string' => 'A new encrypted string',
    'invalid_old_string' => 'The original encryption string is incorrect',
    'invalid_new_string' => 'The new encrypted string is incorrect',
    'change_key_ok' => 'Changed encryption string successfully',
    'same_string' => 'The new encryption string is the same as the original encryption string',
    'update_log' => 'Update record',
    'old_stat' => 'There are %s records in total. %s records were encrypted with a new string, %s records were encrypted with an original string (to be updated), and %s records were encrypted with an unknown string.',
    'new_stat' => '<strong> updated </strong>, now use new string encryption records %s, use unknown string encryption records %s.',
    'update_error' => 'Error during update: %s',
    'operation_prompt_content' =>
        array(
            'batch_confirm' =>
                array(
                    0 => 'Upload file format must be suffixed with.csv file.',
                ),
            'replenish' =>
                array(
                    0 => 'Please fill in the replenishment information carefully.',
                ),
            'replenish_list' =>
                array(
                    0 => 'Virtual goods virtual card list information page, showing all the virtual goods virtual card number information.',
                ),
        ),
);


return $_LANG;
