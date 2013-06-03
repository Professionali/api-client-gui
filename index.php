<?php
header('Content-type=text/html;charset=utf-8');
session_start();

/**
 * Функция отладки, обычно определяется во внешней библиотеке debuger
 */
if (!function_exists('p')) {
    function p($var, $is_return = false) {
        $result = htmlspecialchars(print_r($var, true), ENT_QUOTES, 'UTF-8');
        $result = '<pre>'.$result.'</pre>';
        if ($is_return) {
            return $result;
        }
        print $result;
    }
}

require 'client/Pro/Api/Client.php';
require 'client/Pro/Api/Dialogue.php';
require 'client/Pro/Api/Exception.php';

define('APP_CODE', 'zsg7ldlsnloiqr9d');
define('APP_SECRET', 'xu8qo1aljwtp15qjj4yq11t1yir9fut8');
define('CLIENT_URL', 'http://'.$_SERVER['HTTP_HOST'].'/');

/**
 * Рзибрает строку со списком параметров превращае ее в массив
 *
 * Пример перечисления параметров:
 *   key1=value1
 *   key2[]=value2
 *   key3[subkey]=value3
 *
 * @param string $params Список параметров
 */
function collectParams($params)
{
    $result = array();
    foreach (explode(PHP_EOL, $params) as $i => $param) {
        if (strpos($param, '=')!==false) {
            list($key, $value) = explode('=', trim($param), 2);
            $result[str_replace('[]', '['.$i.']', $key)] = $value;
        }
    }
    return $result;
}

try {

    // Создаем API клиента
    $api = new Pro_Api_Client(APP_CODE, APP_SECRET, $_SESSION['token'], $_SESSION['expires']);
    $api->setDebugMode(true);

    // если хотели разавторизироваться
    if (isset($_GET['exit'], $_SESSION['token'])) {
        $api->logout($_SESSION['token']);
        unset($_SESSION['user']);
        unset($_SESSION['token']);
        unset($_SESSION['expires']);
        // Редиректим на себя же, чтоб убрать код из GET параметра
        header("Location: ".CLIENT_URL ,true, 301);
    }

    // если пришел ответ $redirect_uri с кодом, получаем token и сохраняем его в сессию
    if (isset($_GET['code'])) {
        $api->getAccessTokenFromCode($_GET['code'], CLIENT_URL);
        // Получаем данные о пользователе
        $_SESSION['user'] = $api->getCurrentUser();
        // Редиректим на себя же, чтоб убрать код из GET параметра
        header("Location: ".CLIENT_URL, true, 301);
    }

    // Проверям есль ли у нас пользователь
    if (!empty($_SESSION['user']) && $api->getAccessToken() && !$api->isExpiresAccessToken()) {
        $user = $_SESSION['user'];
        $exit_uri = CLIENT_URL.'?exit';
        // Обновляем данные
        $_SESSION['token'] = $api->getAccessToken();
        $_SESSION['expires'] = $api->getExpires();
    } else {
        $auth_url = $api->getAuthenticationUrl(CLIENT_URL);
    }


    if (@$_SESSION['user']) {
        // список методов API
        $methods = require 'methods.php';
        //sort($methods);

        // отправка тестового запроса
        if (!empty($_POST['method']) && isset($_POST['get'], $_POST['post'])) {
            $url = Pro_Api_Client::API_HOST.'/'.$_POST['method'].'.json';
            // собираем get и post параметры
            $get  = collectParams($_POST['get']);
            $post = collectParams($_POST['post']);
            // отправляем запрос
            if ($post) {
                if ($get) {
                    $url .= (strpos($url, '?') !== false) ? '&' : '?';
                    $url .= http_build_query($get, null, '&');
                }
                $dialogue = $api->fetch($url, $post, Pro_Api_Client::HTTP_POST, !empty($_POST['subscribe']));
            } else {
                $dialogue = $api->fetch($url, $get, Pro_Api_Client::HTTP_GET, !empty($_POST['subscribe']));
            }
        }
    }

} catch (Pro_Api_Exception $e) {
    $dialogue = $e->getDialogue();
    if ($error_message = $e->getError()) {
        $error_message .= $e->getDescription() ? ' ('.$e->getDescription().')' : '';
    } else {
        $error_message = $e->getDescription();
    }
}

// отображение страници
require 'template.php';