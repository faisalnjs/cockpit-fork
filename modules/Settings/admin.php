<?php

// Register Helpers
$this->helpers['settings'] = 'Settings\\Helper\\Settings';

// Register routes
$this->bindClass('Settings\\Controller\\Api', '/settings/api');
$this->bindClass('Settings\\Controller\\Locales', '/settings/locales');
$this->bindClass('Settings\\Controller\\Users\\Roles', '/settings/users/roles');
$this->bindClass('Settings\\Controller\\Users', '/settings/users');
$this->bindClass('Settings\\Controller\\Settings', '/settings');

$this->helper('menus')->addLink('modules', [
    'label'  => 'Api',
    'icon'   => 'settings:assets/icons/api.svg',
    'route'  => '/settings/api',
    'active' => false
]);


$this->on('app.permissions.collect', function (ArrayObject $permissions) {

    $permissions['Locales'] = [
        'app.locales.manage' => 'Manage locales',
    ];

    $permissions['Api & Security'] = [
        'app.api.manage' => 'Manage Api access',
    ];

});