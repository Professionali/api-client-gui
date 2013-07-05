<?php
header('Content-type=text/html;charset=utf-8');
session_start();

require 'client/Pro/Api/Client.php';
require 'client/Pro/Api/Dialogue.php';
require 'client/Pro/Api/Exception.php';

define('APP_CODE', 'zsg7ldlsnloiqr9d');
define('APP_SECRET', 'xu8qo1aljwtp15qjj4yq11t1yir9fut8');
define('CLIENT_URL', 'http://'.$_SERVER['HTTP_HOST'].'/');

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
        // редиректим на себя же, чтоб убрать код из GET параметра
        header('Location: '.CLIENT_URL, true, 301);
        exit;
    }

    // если пришел ответ $redirect_uri с кодом, получаем token и сохраняем его в сессию
    if (isset($_GET['code'])) {
        $api->getAccessTokenFromCode($_GET['code']);
        // Получаем данные о пользователе
        $_SESSION['user'] = $api->getCurrentUser();
        // Редиректим на себя же, чтоб убрать код из GET параметра
        header('Location: '.CLIENT_URL, true, 301);
        exit;
    }

    // проверка наличия пользователя
    if (!empty($_SESSION['user']) && $api->getAccessToken() && !$api->isExpiresAccessToken()) {
        $user = $_SESSION['user'];
        $exit_uri = CLIENT_URL.'?exit';
        // Обновляем данные
        $_SESSION['token'] = $api->getAccessToken();
        $_SESSION['expires'] = $api->getExpires();
    } else {
        $auth_url = $api->getAuthenticationUrl(CLIENT_URL);
    }


    // отправка тестового запроса
    if (!empty($_SESSION['user']) && !empty($_POST['collector'])) {
        $params = str_replace(array("\r\n", "\n"), '&', $_POST['collector']['parameters']);
        parse_str($params, $params);
        $dialogue = $api->fetch(
            Pro_Api_Client::API_HOST.$_POST['collector']['path'],
            $params,
            $_POST['collector']['method'],
            !empty($_POST['collector']['subscribe'])
        );
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