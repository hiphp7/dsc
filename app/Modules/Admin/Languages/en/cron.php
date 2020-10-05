<?php

$_LANG = array(
    'cron_name' => 'Planned task name',
    'cron_code' => 'This planned task',
    'if_open' => 'open',
    'version' => 'version',
    'cron_desc' => 'Plan task description',
    'cron_author' => 'The plugin author',
    'cron_time' => 'Plan task execution time',
    'cron_next' => 'Next execution time',
    'cron_this' => 'Last execution time',
    'cron_allow_ip' => 'The server IP that is allowed to execute',
    'cron_run_once' => 'Post execution close',
    'cron_alow_files' => 'Allow page execution',
    'notice_alow_files' => 'The foreground triggers the page that the plan runs on, leaving blank means that it fires on all the pages',
    'notice_alow_ip' => 'Allow the IP of the scheduled task server to run. Separate multiple ips with a half-corner comma',
    'notice_minute' => 'Use a half-corner comma to separate multiple minutes',
    'notice_run_once' => 'After checking the box, the scheduled task can no longer be executed. By default, it can be executed in a loop without checking the box',
    'cron_do' => 'perform',
    'do_ok' => 'Execute successfully',
    'cron_month' => 'A month',
    'cron_day' => 'day',
    'cron_week' => 'Once a week',
    'cron_thatday' => 'In the day',
    'cron_hour' => 'hours',
    'cron_minute' => 'minutes',
    'cron_unlimit' => 'daily',
    'cron_advance' => 'Advanced options',
    'cron_show_advance' => 'Display advanced options',
    'install_ok' => 'Successful installation',
    'edit_ok' => 'Edit success',
    'week' =>
        array(
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ),
    'uninstall_ok' => 'Uninstall the success',
    'cron_not_available' => 'The scheduled task does not exist or has not been installed',
    'back_list' => 'Returns the scheduled task list',
    'name_is_null' => 'You did not enter a scheduled task name!',
    'js_languages' =>
        array(
            'lang_removeconfirm' => 'Are you sure you want to uninstall this scheduled task?',
        ),
    'page' =>
        array(
            'index' => 'Home page',
            'user' => 'The user center',
            'pick_out' => 'Center of choose and buy',
            'flow' => 'The shopping cart',
            'group_buy' => 'A bulk goods',
            'snatch' => 'Raiders of the lost ark',
            'tag_cloud' => 'A tag cloud',
            'category' => 'Product list page',
            'goods' => 'Commodity page',
            'article_cat' => 'Article list page',
            'article' => 'The article page',
            'brand' => 'Brand zone',
            'search' => 'Search results page',
        ),
    'tutorials_bonus_list_one' => 'Mall planning task operation instructions',
    'operation_prompt_content' =>
        array(
            'list' =>
                array(
                    0 => 'Displays information such as scheduled task list, scheduled task description, version, plugin reporter, etc.',
                    1 => 'Scheduled tasks can be turned on or off. Scheduled tasks can be installed, uninstalled, edited, etc.',
                    2 => 'Plan task execution: access domain /cron.php',
                ),
            'info' =>
                array(
                    0 => 'You can edit the installed scheduled tasks, edit the scheduled task name, content, execution time, and other information.',
                ),
        ),
);


return $_LANG;
