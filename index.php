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

$app_id     = 'zsg7ldlsnloiqr9d';
$app_secret = 'xu8qo1aljwtp15qjj4yq11t1yir9fut8';
$client_url = 'http://'.$_SERVER['HTTP_HOST'].'/';

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
function collectParams($params) {
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
	$api = new Pro_Api_Client($app_id, $app_secret, $_SESSION['token'], $_SESSION['expires']);

	// если хотели разавторизироваться
	if (isset($_GET['exit'], $_SESSION['token'])) {
		$api->logout($_SESSION['token']);
		unset($_SESSION['user']);
		unset($_SESSION['token']);
		unset($_SESSION['expires']);
		// Редиректим на себя же, чтоб убрать код из GET параметра
		header("Location: ".$client_url ,true, 301);
	}

	// если пришел ответ $redirect_uri с кодом, получаем token и сохраняем его в сессию
	if (isset($_GET['code'])) {
		$api->getAccessTokenFromCode($_GET['code'], $client_url);
		// Получаем данные о пользователе
		$_SESSION['user'] = $api->getCurrentUser();
		// Редиректим на себя же, чтоб убрать код из GET параметра
		header("Location: ".$client_url, true, 301);
	}

	// Проверям есль ли у нас пользователь
	if (!empty($_SESSION['user']) && $api->getAccessToken() && !$api->isExpiresAccessToken()) {
		$user = $_SESSION['user'];
		$exit_uri = $client_url.'?exit';
		// Обновляем данные
		$_SESSION['token'] = $api->getAccessToken();
		$_SESSION['expires'] = $api->getExpires();
	} else {
		$auth_url = $api->getAuthenticationUrl($client_url);
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

?><!doctype html>
<html>
	<head>
		<meta charset="utf-8">
		<title>API клиент professionali.ru</title>
	</head>
	<body>
		<h1>API клиент professionali.ru</h1>
		<?if(isset($user)):?>
			<h3>Текущий пользователь</h3>
			<p>
				<img src="<?=$user['avatar_big']?>" alt="<?=$user['name']?>"/><br/>
				<a href="<?=$user['link']?>"><?=$user['name']?></a><br/>
				<a href="<?=$exit_uri?>">Выход</a>
			</p>
			<?if(isset($error_message)):?>
				<p>Ошибка: <strong><?=$error_message?></strong></p>
			<?endif;?>
			<?if(isset($dialogue)):?>
				<p>
					<h3>Результат запроса</h3>
					<?p($dialogue->toArray())?>
				</p>
			<?endif;?>
			<form method="post" action="">
			<h3>Сборщик запроса</h3>
			<strong>Метод Api</strong><br />
			<?=Pro_Api_Client::API_HOST?>/
			<select name="method">
			<?foreach($methods as $section => $methods):?>
				<optgroup label="<?=ucwords($section)?>">
				<?foreach ($methods as $method):?>
					<? $path = (isset($method['version']) ? 'v'.$method['version'].'/' : '').$section.'/'.$method['method']?>
					<option<?if(isset($_POST['method']) && $_POST['method'] == $path):?> selected="selected"<?endif;?> value="<?=$path?>">
						<?=$method['method']?><?if(isset($method['version'])):?> (v<?=$method['version']?>)<?endif;?>
					</option>
				<?endforeach;?>
				</optgroup>
			<?endforeach;?>
			</select>
			.json<br />
			<strong>Токен</strong>
			<input type="text" value="<?=$_SESSION['token']?>" size="32" /><br />
			<strong>Подписать запрос</strong>
			<input type="checkbox" name="subscribe" <?if(!empty($_POST['subscribe'])):?> checked="checked"<?endif?> value="yes" /><br />
			<strong>Get параметры</strong><br />
			<textarea name="get" cols="60" rows="7"><?=isset($_POST['get']) ? $_POST['get'] : ''?></textarea><br />
			<strong>Post параметры</strong><br />
			<textarea name="post" cols="60" rows="7"><?=isset($_POST['post']) ? $_POST['post'] : ''?></textarea>
			<p><strong>Пример перечисления параметров:</strong><br />
			<small>
				<strong>Ключ значение:</strong><br />
				key=value<br />
				<strong>Список значений:</strong><br />
				key[]=value1<br />
				key[]=value2<br />
				<strong>Хэш значений:</strong><br />
				key[subkey]=value</p>
			</small>
			<button type="submit">Отправить</button>
			</form>
		<?elseif(isset($auth_url)):?>
			<p><a href="<?=$auth_url?>">Авторизироваться</a></p>
		<?endif;?>
	</body>
</html>
