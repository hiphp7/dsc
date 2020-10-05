<?php

$_LANG = array (
  'rank_name' => 'Name of membership level',
  'integral_min' => 'Growth value floor',
  'integral_min_notice' => 'After modifying the growth value required by the level, some customers will change their membership level because they cannot meet the growth value requirements',
  'integral_max' => 'Integral upper limit',
  'discount' => 'Initial discount rate',
  'add_user_rank' => 'Add membership levels',
  'special_rank' => 'Special membership group',
  'show_price' => 'The product price for this membership level is displayed on the product details page',
  'notice_special' => 'Members of a special membership group do not change with points.',
  'add_continue' => 'Continue to add membership levels',
  'back_list' => 'Returns a list of membership levels',
  'show_price_short' => 'According to the price',
  'notice_discount' => 'Please fill in an integer of 0-100. If you fill in 80, the initial discount rate will be 20%',
  'rank_name_exists' => 'The member level name %s already exists.',
  'add_rank_success' => 'Membership level has been added successfully.',
  'integral_min_exists' => 'There is already a membership level with a lower limit of %d',
  'integral_max_exists' => 'There is already a membership level with a maximum of %d',
  'full_max_exists' => 'Beyond the maximum score at the next level, please modify it and try again',
  'user_rank_set' => 'Growth value period',
  'is_open' => 'Whether to turn on:',
  'expiry_date' => 'The period of validity:',
  'user_rank_notice' => 'After the expiry date is opened, the growth value will be valid from the solstice period of validity, and the growth value beyond the expiry date will be automatically reset. After setting, the store cache will be cleared before taking effect.',
  'month' => 'month',
  'js_languages' => 
  array (
    'remove_confirm' => 'Are you sure you want to delete the selected membership levels?',
    'rank_name_empty' => 'You did not enter a membership level name.',
    'integral_min_invalid' => 'You did not enter the lower limit or the lower limit is not an integer.',
    'integral_max_invalid' => 'You did not enter the integral upper limit or the integral upper limit is not an integer.',
    'discount_invalid' => 'You did not enter a discount rate or the discount rate is invalid.',
    'integral_max_small' => 'The upper limit must be greater than the lower limit.',
    'lang_remove' => 'remove',
  ),
  'operation_prompt_content' => 
  array (
    'list' => 
    array (
      0 => 'This page displays information about all membership levels.',
      1 => 'You can add member levels, edit member levels, delete member levels.',
    ),
  ),
    'user_rights' => 'User rights',
    'view_users' => 'View users',
    'set_show' => 'Set show',
    'drop_confirm_rank' => 'When deleting this membership level, the membership rights and interests under this membership level will also be deleted. Are you sure you want to delete it?',

  'user' => 'User',
  'edit_user_rank' => 'Edit user rank',
  'user_rank' => 'User rank',
  'user_rank_has_user' => 'There are members under the current level, which cannot be deleted',
  'user_rank_rights_tips' => [
      'Commodity distribution / membership card distribution general membership level cannot be used. It must be used in conjunction with distribution equity card'
  ],
  'rights_close' => '[User rights close]',
);


return $_LANG;
