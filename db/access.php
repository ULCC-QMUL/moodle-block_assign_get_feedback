<?php
defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

global $CFG;

$capabilities = [
    'block/assign_get_feedback:addinstance' => [
        'riskbitmask' => RISK_PERSONAL | RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'user' => CAP_PREVENT,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ]
        , 'clonepermissionsfrom' => 'moodle/course:manageblocks'
    ],
    'block/assign_get_feedback:myaddinstance' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'user' => CAP_PREVENT,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ],

        'clonepermissionsfrom' => 'moodle/my:manageblocks'
    ],
];