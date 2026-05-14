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
	'title' => 'tonictypes - Rapid TCA & Advanced Plugins',
	'description' => 'Maximize development speed with tonictypes, the evolution of the proven typotonic extension (over 1,000 downloads). Build on a foundation of success and create custom TCA records like news, jobs, or events on the fly directly in the TYPO3 backend—no PHP extension coding required. This successor features powerful new list and detail plugins alongside adjustable backend filters for total control. Only Fluid templating is needed to match any design. Stop managing overhead and start building faster.',
	'category' => 'plugin',
	'shy' => 0,
	'version' => '2.0.0',
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
	'author_company' => 'Keeen GmbH',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => [
		'depends' => [
            'php' => '8.1.0-8.3.99',
			'typo3' => '12.4.0-14.3.99',
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
