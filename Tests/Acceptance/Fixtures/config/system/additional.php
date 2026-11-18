<?php

declare(strict_types=1);

// Configuration for the TYPO3 installation of the acceptance tests,
// see Build/Testing/AcceptanceTestsBootstrap.php
$GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] = '.*';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors'] = 1;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = '*';
