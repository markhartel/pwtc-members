<?php

if ( function_exists('acf_add_local_field_group') ) {
    acf_add_local_field_group(array (
        'key' => 'group_membership_options',
        'title' => 'Membership Options',
        'fields' => array (
            array (
                'key' => 'field_send_membership_email',
                'label' => 'Send Membership Email',
                'name' => 'send_membership_email',
                'type' => 'true_false',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array (
                    'width' => 50,
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => 0,
            ),
            array (
                'key' => 'field_bcc_membership_secretary',
                'label' => 'Bcc Membership Secretary',
                'name' => 'bcc_membership_secretary',
                'type' => 'true_false',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array (
                    'width' => 50,
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => 0,
            ),
            array (
                'key' => 'field_membership_email_subject',
                'label' => 'Membership Email Subject',
                'name' => 'membership_email_subject',
                'type' => 'text',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array (
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'placeholder' => '',
            ),
            array (
                'key' => 'field_individual_membership_email',
                'label' => 'Individual Membership Email',
                'name' => 'individual_membership_email',
                'type' => 'wysiwyg',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array (
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
            ),
            array (
                'key' => 'field_family_membership_email',
                'label' => 'Family Membership Email',
                'name' => 'family_membership_email',
                'type' => 'wysiwyg',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array (
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
            ),
       ),
        'location' => array (
            array (
                array (
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => 'theme-general-settings',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => 1,
        'description' => '',
    ));
}
