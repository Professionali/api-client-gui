<?php

/**
 * Api для professionali.ru
 */
class Pro_Api_Client {

	/**
	 * HTTP Методы
	 */
	const HTTP_GET    = 'GET';
	const HTTP_POST   = 'POST';

	/**
	 * Хост для апи
	 */
	const API_HOST = 'http://api.professionali.ru';

	/**
	 * Методы API
	 */
	const POINT_AUTHORIZATION = '/oauth/authorize.html';
	const POINT_GET_TOKEN     = '/oauth/getToken.json';
	const POINT_REFRESH_TOKEN = '/oauth/refreshToken.json';
	const POINT_LOGOUT        = '/oauth/logout.json';

	const POINT_GET_CURRENT   = '/users/get.json?ids[]=me&fields[]=id&fields[]=name&fields[]=link&fields[]=avatar_big';

	/**
	 * Имена ключей
	 */
	const NAME_ACCESS_TOKEN = 'access_token';
	const NAME_EXPIRES_IN = 'expires_in';

	/**
	 * Вид отображения окна авторизации
	 */
	const DISPLAY_PAGE  = 'page';
	const DISPLAY_POPUP = 'popup';
	const DISPLAY_TOUCH = 'touch';
	const DISPLAY_WAP   = 'wap';


	/**
	 * Индификатор приложения
	 *
	 * @var string
	 */
	protected $app_id = null;

	/**
	 * Секретный код приложения
	 *
	 * @var string
	 */
	protected $app_secret = null;

	/**
	 * Token доступа
	 *
	 * @var string
	 */
	protected $access_token = null;

	/**
	 * Время устаревания токена
	 *
	 * @var integer
	 */
	protected $access_token_expires = null;


	/**
	 * Конструктор
	 *
	 * @param string|null $app_id     Индификатор приложения
	 * @param string|null $app_secret Секретный код приложения
	 */
	public function __construct($app_id, $app_secret, &$access_token = null, &$access_token_expires = null) {
		if (!extension_loaded('curl')) {
			throw new Exception('Нет расширения curl');
		}
		$this->app_id     = $app_id;
		$this->app_secret = $app_secret;
		$this->access_token = &$access_token;
		$this->access_token_expires = &$access_token_expires;
	}

	/**
	 * Получение ссылки на автаризацию
	 *
	 * @param string $redirect_uri Адрес редиректа после авторизации
	 * @param string $display      Внешний вид диалога
	 *
	 * @return string
	 */
	public function getAuthenticationUrl($redirect_uri, $display=self::DISPLAY_PAGE) {
		$parameters = array(
			'response_type' => 'code',
			'client_id'     => $this->app_id,
			'redirect_uri'  => $redirect_uri,
			'display'       => $display
		);
		return Pro_Api_Client::API_HOST.Pro_Api_Client::POINT_AUTHORIZATION . '?' . http_build_query($parameters, null, '&');
	}

	/**
	 * Получение токена доступа
	 *
	 * @param string $code
	 * @param string $redirect_uri
	 *
	 * @return array
	 */
	public function getAccessTokenFromCode($code, $redirect_uri) {
		$parameters = array(
			'code'          => $code,
			'redirect_uri'  => $redirect_uri,
			'client_id'     => $this->app_id,
			'client_secret' => $this->app_secret,
		);
		$response = $this->executeRequest(Pro_Api_Client::API_HOST.Pro_Api_Client::POINT_GET_TOKEN, $parameters, self::HTTP_POST);
		if (isset($response['result'][self::NAME_ACCESS_TOKEN])) {
			$this->setAccessToken($response['result'][self::NAME_ACCESS_TOKEN]);
			$this->access_token_expires = strtotime($response['result'][self::NAME_EXPIRES_IN]);
		}
		return $response['result'];
	}

	/**
	 * Проверить устарел ли токен доступа
	 *
	 * @return boolean
	 */
	public function isExpiresAccessToken() {
		return $this->access_token_expires - time() < 0;
	}

	/**
	 * Получение текущего токена доступа
	 *
	 * @return array
	 */
	public function getAccessToken() {
		return $this->access_token;
	}

	/**
	 * Время устаревания токена
	 *
	 * @return array
	 */
	public function getExpires() {
		return $this->access_token_expires;
	}

	/**
	 * Установить токен доступа
	 *
	 * @param string $token Токен доступа
	 */
	public function setAccessToken($token) {
		$this->access_token = $token;
	}

	/**
	 * Выполнить запрос
	 *
	 * @param string $ressource_url адрес защищенного ресурса
	 * @param array  $parameters    Параметры запроса
	 * @param string $method        Метод запроса
	 *
	 * @return array
	 */
	public function fetch($resource_url, array $parameters = array(), $method = self::HTTP_GET) {
		// добавление токена в параметры запроса
		if ($this->access_token) {
			if ($method == self::HTTP_GET) {
				$parameters[self::NAME_ACCESS_TOKEN] = $this->access_token;
			} elseif (strpos($resource_url, self::NAME_ACCESS_TOKEN) === false){
				$resource_url .= (strpos($resource_url, '?')!==false ? '&' : '?').self::NAME_ACCESS_TOKEN.'='.$this->access_token;
			}
		}
		// Проверяем чтоб есть ключ не устарел, если устарел обновляем его
		if($this->getAccessToken() && $this->isExpiresAccessToken()) {
			$this->refreshAccessToken();
		}
		return $this->executeRequest($resource_url, $parameters, $method);
	}

	/**
	 * Выполнить запрос
	 *
	 * @param string $url        адрес
	 * @param mixed  $parameters параметры
	 * @param string $method     метод
	 *
	 * @return array
	 */
	private function executeRequest($url, array $parameters = array(), $method = self::HTTP_GET) {
		$curl_options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_CUSTOMREQUEST  => $method
		);

		switch($method) {
			case self::HTTP_POST:
				$curl_options[CURLOPT_POST] = true;
				$curl_options[CURLOPT_POSTFIELDS] = $this->buildParams($parameters);
				$post = $parameters;
				break;
			case self::HTTP_GET:
				$url .= (strpos($url, '?')!==false ? '&' : '?') . http_build_query($this->buildParams($parameters), null, '&');
				$post = array();
				break;
		}
		$curl_options[CURLOPT_URL] = $url;

		$ch = curl_init();
		curl_setopt_array($ch, $curl_options);
		$result = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
		curl_close($ch);
		$json_decode = json_decode($result, true);

		if ($http_code != 200) {
			$code = $http_code;
			$desc = 'Неизвестная ошибка';
			if (isset($json_decode['error'], $json_decode['description'])) {
				$code = $json_decode['error'];
				$desc = $json_decode['description'];
				// токен устарел
				if ($code == 'invalid_token') {
					$this->refreshAccessToken();
					return $this->executeRequest($url, $parameters, $method);
				}
				// токен не найден
				if ($code == 'undefined_token') {
					$this->access_token = null;
					$this->access_token_expires = null;
				}
			} elseif (isset($json_decode['code'], $json_decode['error'])) {
				$code = $json_decode['code'];
				$desc = $json_decode['error'];
			}
			throw new Pro_Api_Exception($code, $desc, $http_code);
		}

		return array(
			'result'       => $json_decode === null ? $result : $json_decode,
			'code'         => $http_code,
			'content_type' => $content_type,
			'url'          => $url,
			'post'         => $post
		);
	}

	/**
	 * Строитесльство списка параметров из многомерного массива
	 * 
	 * На входе:
	 * <code>
	 *  array(
	 *    'a1' => array(
	 *      'b1' => 33,
	 *      'b2' => array(
	 *        'c1' => array(44, 88),
	 *        'c2' => 66
	 *      )
	 *    ),
	 *    'a2' => 11
	 *  )
	 * </code>
	 * 
	 * На выходе:
	 * <code>
	 *  array(
	 *    'a1[b1]' => 33,
	 *    'a1[b2][c1][0]' => 44,
	 *    'a1[b2][c1][1]' => 88,
	 *    'a1[b2][c2]' => 66,
	 *    'a2' => 11
	 *  )
	 * </code>
	 * 
	 * @param array  $params Список параметров
	 * @param string $prefix Префикс имени
	 * 
	 * @return array
	 */
	private function buildParams(array $params, $prefix = '') {
		$result = array();
		foreach ($params as $name => $param) {
			$name = $prefix ? $prefix.'['.$name.']' : $name;
			if (is_array($param)) {
				$result = array_merge($result, $this->buildParams($param, $name));
			} else {
				$result[$name] = $param;
			}
		}
		return $result;
	}

	/**
	 * Выход
	 */
	public function logout($access_token) {
		$this->fetch(self::API_HOST.self::POINT_LOGOUT, array(self::NAME_ACCESS_TOKEN => $access_token), self::HTTP_GET);
		$this->access_token = null;
		$this->access_token_expires = null;
	}

	/**
	 * Получение токена доступа
	 *
	 * @param string $code
	 * @param string $redirect_uri
	 *
	 * @return array
	 */
	public function refreshAccessToken() {
		$response = $this->executeRequest(
			self::API_HOST.self::POINT_REFRESH_TOKEN,
			array(self::NAME_ACCESS_TOKEN => $this->access_token),
			self::HTTP_GET
		);
		if (isset($response['result'][self::NAME_ACCESS_TOKEN])) {
			$this->setAccessToken($response['result'][self::NAME_ACCESS_TOKEN]);
			$this->access_token_expires = strtotime($response['result'][self::NAME_EXPIRES_IN]);
		}
		return $response['result'];
	}

	/**
	 * Получение токена доступа
	 *
	 * @param string $code
	 * @param string $redirect_uri
	 *
	 * @return array
	 */
	public function getCurrentUser() {
		$response = $this->fetch(
			Pro_Api_Client::API_HOST.Pro_Api_Client::POINT_GET_CURRENT,
			array(self::NAME_ACCESS_TOKEN => $this->access_token),
			self::HTTP_GET
		);
		return $response['result'][0];
	}

}

/**
 * Исключение API
 */
class Pro_Api_Exception extends Exception {

	/**
	 * Ошибка
	 *
	 * @var string
	 */
	private $error;

	/**
	 * Описание ошибки
	 *
	 * @var string
	 */
	private $description;


	/**
	 * Конструктор
	 *
	 * @param string  $error
	 * @param string  $description
	 * @param integer $code
	 */
	public function __construct($error, $description, $code) {
		$this->error = $error;
		$this->description = $description;
		parent::__construct($error, $code);
	}

	/**
	 * Получить ошибку
	 *
	 * @return string
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * Получить описание
	 *
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}

}
