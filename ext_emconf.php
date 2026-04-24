<?php
/*
 * This file is part of the package k3n/tonictypes.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * Many thanks to Auth: B. Zagar / Maint: J. Pietschmann for sharing this extension – TYPO3 inspiring people to share!
 * Contact: support@tonictypes.com
 *
 */
$EM_CONF[$_EXTKEY] = [
	'title' => 'Tonictypes Core',
	'description' => 'Build easy and intuitive TCA-records (e.g. News, Jobs, Events and more) on the fly without developing a new extension. Only templating is needed; includes many plugins for all kinds of usage!',
	'category' => 'plugin',
	'shy' => 0,
	'version' => '1.0.0',
	'dependencies' => 'cms,extbase,fluid',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'stable',
	'uploadfolder' => 0,
	'modify_tables' => '',
	'clearcacheonload' => 1,
	'lockType' => '',
	'author' => 'Auth: B. Zagar / Maint: J. Pietschmann',
	'author_email' => 'support@tonictypes.com',
	'author_company' => 'tonictypes.com',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => [
		'depends' => [
			'php' => '8.1.0-8.3.99',
			'typo3' => '12.4.0-13.4.99',
			'extbase' => '',
			'fluid' => '',
		],
		'conflicts' => [
		],
		'suggests' => [
		],
	],
	'_md5_values_when_last_written' => 'a:0:{}',
	'suggests' => [
	],
    'autoload' => [
        'classmap' => [
            'Classes',
        ]
    ]
];
