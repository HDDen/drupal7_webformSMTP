<?php
if (empty($GLOBALS['hdden_drupal7_webformSMTP_opts'])){
    $GLOBALS['hdden_drupal7_webformSMTP_opts'] = array(
        'HDDEN_WFSMTP__LOGIN' => '',
        'HDDEN_WFSMTP__PWD' => '',
        'HDDEN_WFSMTP__HOST' => '',
        'HDDEN_WFSMTP__PORT' => '',
        'HDDEN_WFSMTP__CHARSET' => 'UTF-8',
        'HDDEN_WFSMTP__FROMNAME' => $_SERVER['HTTP_HOST'],
        'HDDEN_WFSMTP__FROMMAIL' => '',
        'HDDEN_WFSMTP__TO' => '',
        'HDDEN_WFSMTP__SUBJECT' => 'New form submission, '.$_SERVER['HTTP_HOST'],
        'HDDEN_WFSMTP__DELAY' => 0,
        'HDDEN_WFSMTP__LOG' => __DIR__.'/logs/_log-'.$_SERVER['HTTP_HOST'].'.txt',
    );
}
