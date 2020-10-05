<?php

$_LANG = array(
    'create_region_initial' => 'Generates locale initials',
    'region_id' => 'Area number',
    'region_name' => 'In the name of the',
    'region_type' => 'Area type',
    'region_hierarchy' => 'In the hierarchy',
    'region_belonged' => 'Each district',
    'city_region_id' => 'Municipal district ID',
    'city_region_name' => 'Municipal district name',
    'city_region_initial' => 'The first letter',
    'area' => 'region',
    'area_next' => 'The following',
    'country' => 'Level 1 area',
    'province' => 'The secondary region',
    'city' => 'Level 3 areas',
    'cantonal' => 'Level 4 regions',
    'street' => 'Five areas',
    'back_page' => 'Return to upper level',
    'manage_area' => 'management',
    'region_name_empty' => 'The zone name cannot be empty!',
    'add_country' => 'Additional tier 1 districts',
    'add_province' => 'New secondary areas',
    'add_city' => 'Add three tier areas',
    'add_cantonal' => 'Add four districts',
    'restore_default_set' => 'Restore default Settings',
    'region_name_placeholder' => 'Please enter a district name',
    'add_region' => 'The new area',
    'confirm_set' => 'Are you sure you want to restore the default Settings?',
    'js_languages' =>
        array(
            'region_name_empty' => 'You must enter the name of the locale!',
            'option_name_empty' => 'You must enter a survey option name!',
            'drop_confirm' => 'Are you sure you want to delete this record?',
            'drop' => 'delete',
            'country' => 'Level 1 area',
            'province' => 'The secondary region',
            'city' => 'Level 3 areas',
            'cantonal' => 'Level 4 regions',
        ),
    'add_area_error' => 'Failed to add new locale!',
    'region_name_exist' => 'The same locale name already exists!',
    'parent_id_exist' => 'There are other sub-regions under this area, which cannot be deleted!',
    'form_notic' => 'Click to view sub-regions',
    'area_drop_confirm' => 'If the following locale is used in the order or the user\'s default shipping mode, the locale information will appear empty. Are you sure you want to delete this record?',
    'restore_region' => 'Restore default locale',
    'restore_success' => 'Restore default locale successfully',
    'restore_failure' => 'Failed to restore default locale',
    'operation_prompt_content' =>
        array(
            'initial' =>
                array(
                    0 => 'The district initials are the letters generated for all secondary urban areas.',
                    1 => 'Classify each city by initial letter, which is convenient for the front desk to find; Note that the initial letter for the generated area is that the city does not appear at the county level.',
                ),
            'list' =>
                array(
                    0 => 'Click "management" to enter the next level to delete and edit.',
                    1 => 'The area is used for the positioning of the mall, please carefully set it according to the actual situation of the mall.',
                    2 => 'The region initials are generated to facilitate the search of corresponding regions according to the region initials.',
                    3 => 'The hierarchy of the region must be China → province/municipality directly under the central government → city → county. The support of the region is not shown after the support of the four-tier region, and it is not supported abroad for the time being.',
                ),
        ),
);


return $_LANG;
