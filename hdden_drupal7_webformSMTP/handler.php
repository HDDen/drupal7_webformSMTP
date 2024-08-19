<?php

define( 'HDDEN_WFSMTP__PLUGIN_DIR', rtrim( dirname( __FILE__ ), '\\/' ) );
define( 'HDDEN_WFSMTP__PLUGIN_URL', str_replace($_SERVER['DOCUMENT_ROOT'], '', dirname( __FILE__ )));

include_once HDDEN_WFSMTP__PLUGIN_DIR . '/inc/SendMailSmtpClass.php';
include_once HDDEN_WFSMTP__PLUGIN_DIR . '/inc/HDDenLogger.inc';
include_once HDDEN_WFSMTP__PLUGIN_DIR . '/config.php';

function hdden_webformSMTP($params = array()){

    // подключаем лог
    $logger = false;
    if (class_exists('hdden_drupal7_webformSMTP__HDDenLogger')) $logger = new hdden_drupal7_webformSMTP__HDDenLogger($GLOBALS['hdden_drupal7_webformSMTP_opts']['HDDEN_WFSMTP__LOG']);

    // Получаем значения
    $values = [];
    if (isset($_POST)){

        $name = htmlspecialchars(iconv("UTF-8", "windows-1251", @$_POST['name']));
        $email = htmlspecialchars(@$_POST['email']); if ($email=='undefined') {unset($email);}
        $phone = htmlspecialchars(@$_POST['phone']);
        $ques = htmlspecialchars(iconv("UTF-8", "windows-1251", @$_POST['ques'])); if ($ques=='undefined') {unset($ques);}
        $subject = htmlspecialchars(iconv("UTF-8", "windows-1251", @$_POST['formname']));
        
        $logger ? $logger->write('***********************************************************'
        ) : '';

        if (empty($phone) && empty($email)) {
            $logger ? $logger->write(
            __METHOD__.': '.'Получено пустое обращение'
            ) : '';

            return false;
        }

        $logger ? $logger->write(
        __METHOD__.': '.'Получено обращение'
        ) : '';

        $result = false;
        if (class_exists('hdden_SendMailSmtpClass')){
            try {
                $mailSMTP = new hdden_SendMailSmtpClass(
                    $GLOBALS['hdden_drupal7_webformSMTP_opts']['HDDEN_WFSMTP__LOGIN'], 
                    $GLOBALS['hdden_drupal7_webformSMTP_opts']['HDDEN_WFSMTP__PWD'], 
                    $GLOBALS['hdden_drupal7_webformSMTP_opts']['HDDEN_WFSMTP__HOST'], 
                    $GLOBALS['hdden_drupal7_webformSMTP_opts']['HDDEN_WFSMTP__PORT'], 
                    $GLOBALS['hdden_drupal7_webformSMTP_opts']['HDDEN_WFSMTP__CHARSET']
                );

                $from = array(
                    $GLOBALS['hdden_drupal7_webformSMTP_opts']['HDDEN_WFSMTP__FROMNAME'], // Имя отправителя
                    $GLOBALS['hdden_drupal7_webformSMTP_opts']['HDDEN_WFSMTP__FROMMAIL'] // почта отправителя
                );

                $mail_body = '';
                $mail_body .= 'Отправка с сайта: '.$_SERVER['HTTP_HOST'].'<br>'.'<br>';
                $mail_body .= 'Полученные значения: '.'<br>';

                if (!empty($name)) $mail_body .= 'Имя: '.$name.';<br> '; 
                if (!empty($email)) $mail_body .= 'E-mail: '.$email.';<br> '; 
                if (!empty($phone)) $mail_body .= 'Телефон: '.$phone.';<br> '; 
                if (!empty($ques)) $mail_body .= 'Вопрос: '.$ques.';<br> '; 
                if (!empty($subject)) $mail_body .= 'Тема: '.$subject.';<br> '; 

                $mail_body .= '<br>'.'<br>'.'Дополнительная информация о пользователе:'.'<br>';
                $mail_body .= 'IP-адрес: '.(!empty($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : (!empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''))).'<br>';
                $mail_body .= 'User Agent: '.(!empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '').'<br>';
                $mail_body .= 'Дата и время: '.date('Y-m-d H:i:s').'<br>';
                $mail_body .= 'Реферер: '.(!empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');

                $logger ? $logger->write(__METHOD__.': '.
                'Начинаем отправку: '.PHP_EOL.var_export($mail_body, true)
                ) : '';

                // нарезаем получателей (не хочет отправляться группе)
                $to = !empty($GLOBALS['hdden_drupal7_webformSMTP_opts']['HDDEN_WFSMTP__TO']) ? explode(',', $GLOBALS['hdden_drupal7_webformSMTP_opts']['HDDEN_WFSMTP__TO']) : '';
                foreach ($to as $recipient_index => $recipient) {
                    $recipient = trim($recipient);
                    if (!$recipient){
                        unset($to[$recipient_index]);
                    } else {
                        $to[$recipient_index] = $recipient;
                    }
                }

                // отправка
                if (!empty($to)){

                    foreach ($to as $recipient_index => $recipient) {
                        try {
                            $result = $mailSMTP->send(
                                $recipient, 
                                $GLOBALS['hdden_drupal7_webformSMTP_opts']['HDDEN_WFSMTP__SUBJECT'],  
                                $mail_body,
                                $from
                            );

                            $logger ? $logger->write(__METHOD__.': '.
                            $recipient.': результат '.var_export($result, true)
                            ) : '';
                        } catch (\Throwable $th) {
                            $logger ? $logger->write(__METHOD__.': '.
                            'Для реципиента '.$recipient.' не удалось отправить письмо: '.PHP_EOL.var_export($mail_body, true)
                            ) : '';
                        }

                        if (!empty($GLOBALS['hdden_drupal7_webformSMTP_opts']['HDDEN_WFSMTP__DELAY']) && intval($GLOBALS['hdden_drupal7_webformSMTP_opts']['HDDEN_WFSMTP__DELAY']) < 31){
                            sleep(intval($GLOBALS['hdden_drupal7_webformSMTP_opts']['HDDEN_WFSMTP__DELAY']));
                        }
                    }
                } else {
                    $logger ? $logger->write(__METHOD__.': '.
                    'Отправлять некому, массив $to пуст'
                    ) : '';
                }
                
            } catch (\Throwable $th) {
                $result = false;

                $logger ? $logger->write(__METHOD__.': '.
                'Произошла ошибка отправки, значения: '.PHP_EOL.var_export($mail_body, true)
                ) : '';
            }
        }
    }

}