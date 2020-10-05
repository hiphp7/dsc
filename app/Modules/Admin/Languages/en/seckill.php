<?php

$_LANG = array(
    'seckill_name' => 'Name of seckill activity',
    'seckill_list' => 'List of seckill activities',
    'seckill_add' => 'Add seckill activity',
    'seckill_edit' => 'Edit seckill activities',
    'acti_goods_attr' => 'Commodity attribute',
    'back_list' => 'Back to list page',
    'end_lt_begin' => 'The end time is less than the start time',
    'end_lt_next_end' => 'The end time is greater than the end time of the next period',
    'acti_title' => 'Activity title',
    'acti_state' => 'Active state',
    'begin_time' => 'The start time',
    'end_time' => 'The end of time',
    'start_end_time' => 'Activity start and end time',
    'is_putaway' => 'Online/offline',
    'add_goods' => 'Add the goods',
    'begin_endtime' => 'End date',
    'seckill_note' => '(note: operation time is set to end time from the current time to this time)',
    'title_exist' => 'SEC activity title %s already exists',
    'search_result_empty' => 'No items found, please research',
    'seckill_time_bucket' => 'List of kill times',
    'daily_begin_time' => 'Daily start time',
    'batch_drop_ok' => 'Batch deletion successful',
    'goods_price' => 'Commodity prices',
    'seckill_price' => 'Down the price',
    'seckill_number' => 'Seconds kill number',
    'xiangou_number' => 'The amount for purchasing',
    'activity_end' => 'End of the activity',
    'activity_have_hand' => 'Activity in progress',
    'activity_not_started' => 'Activity not started',
    'categroy_dialog_title' => 'Edit product information',
    'seckill_adopt_status_success' => 'SEC audit status set successfully',
    'time_bucket_add' => 'Add seckill time',
    'time_bucket_title' => 'Name of seckill period',
    'daily_end_time' => 'End of day',
    'begin_time_notice' => 'Time format: 00:00:00',
    'end_time_notice' => 'End time must be greater than start time (time format: 00:00:00 use English ":")',
    'set_seckill_goods' => 'Set second kill merchandise',
    'seckill_goods_info' => 'Add product details',
    'on_sale' => 'shelves',
    'not_on_sale' => 'The shelves',
    'js_languages' =>
        array(
            'acti_title_not_null' => 'The activity title cannot be empty',
            'time_title_not_null' => 'The time period name cannot be empty',
            'begin_hour_required' => 'The daily start time cannot be empty',
            'begin_minute_required' => 'The daily start time (minutes) cannot be empty',
            'begin_second_required' => 'The daily start time (seconds) cannot be empty',
            'end_hour_required' => 'The end of each day cannot be empty',
            'end_minute_required' => 'End of day (minutes) cannot be empty',
            'end_second_required' => 'The daily end time (seconds) cannot be empty',
            'digits_prompt' => 'Must be a number',
            'is_min_0' => 'It can\'t be less than 0',
            'is_max_23' => 'No more than 23',
            'is_max_59' => 'It can\'t be more than 59',
            'jl_set_goods' => 'Set the goods',
        ),
    'tutorials_bonus_list_one' => 'Mall seckill activity description',
    'operation_prompt_content' =>
        array(
            'info' =>
                array(
                    0 => 'Seckilling activities only need to set the end time, and multiple activities can be carried out at the same time. Recommended headings are classified for ease of administration.',
                    1 => 'The seconds are active from 0 to 0.',
                ),
            'list' =>
                array(
                    0 => 'Please set the seckill time first.',
                    1 => 'The seckill activity list allows you to edit/delete/set the item action for the activity.',
                    2 => 'Multiple activities can be held at the same time.',
                    3 => 'The seconds are active from 0 to 0.',
                ),
            'set' =>
                array(
                    0 => 'Set the time period set to display the list of seckill commodities. Add/delete the active commodities in this period.',
                ),
            'set_info' =>
                array(
                    0 => 'Set the list of goods in a certain period of seckill activity, add/delete the goods in this period, modify the information operation such as seckill price, quantity, limited purchase quantity, etc.',
                ),
            'time' =>
                array(
                    0 => 'The list of seconds can be added/edited/deleted.',
                    1 => 'It is recommended to set four to five time periods (foreground display).',
                ),
            'time_info' =>
                array(
                    0 => 'The start time can be modified for the first time, and the default is one second after the end time of the previous period.',
                    1 => 'The name of the SEC session will now appear within the duration of the SEC list page.',
                    2 => 'When you edit the end of a SEC, it will not affect the start time of the next SEC. The end time must be no less than the start time of the current SEC & no more than the end time of the next SEC.',
                ),
        ),
);


return $_LANG;
