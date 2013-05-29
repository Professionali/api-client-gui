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
	const API_HOST = 'https://api.professionali.ru';

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
	const NAME_SIGNATURE = 'signature';

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
		$result = $this->executeRequest(
			Pro_Api_Client::API_HOST.Pro_Api_Client::POINT_GET_TOKEN,
			array(
				'code'          => $code,
				'redirect_uri'  => $redirect_uri,
				'client_id'     => $this->app_id,
				'client_secret' => $this->app_secret,
			),
			self::HTTP_POST
		)->getJsonDecode();

		if (isset($result[self::NAME_ACCESS_TOKEN])) {
			$this->setAccessToken($result[self::NAME_ACCESS_TOKEN]);
			$this->access_token_expires = time()+$result[self::NAME_EXPIRES_IN];
		}
		return $result;
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
	 * @param string  $ressource_url адрес защищенного ресурса
	 * @param array   $parameters    Параметры запроса
	 * @param string  $method        Метод запроса
	 * @param boolean $subscribe     Подписать запорс
	 *
	 * @return Pro_Api_Dialogue
	 */
	public function fetch($resource_url, array $parameters = array(), $method = self::HTTP_GET, $subscribe = false) {
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
		// подписываем запрос при необходимости
		if ($subscribe) {
			$parameters = array_merge($parameters, array(self::NAME_SIGNATURE => $this->getSignature($resource_url, $parameters)));
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
	 * @return Pro_Api_Dialogue
	 */
	private function executeRequest($url, array $parameters = array(), $method = self::HTTP_GET) {
		$curl_options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_CUSTOMREQUEST  => $method,
			CURLOPT_FOLLOWLOCATION => true,
			CURLINFO_HEADER_OUT => true,
			CURLOPT_HEADER => true,
		);

		switch($method) {
			case self::HTTP_POST:
				$curl_options[CURLOPT_POST] = true;
				$curl_options[CURLOPT_POSTFIELDS] = http_build_query($parameters);
				$post = $parameters;
				break;
			case self::HTTP_GET:
				$url .= (strpos($url, '?')!==false ? '&' : '?') . http_build_query($parameters);
				$post = array();
				break;
		}
		$curl_options[CURLOPT_URL] = $url;

		$ch = curl_init();
		curl_setopt_array($ch, $curl_options);
		$dialogue = new Pro_Api_Dialogue(curl_exec($ch), $ch, $url, $post);
		curl_close($ch);

		$json_decode = $dialogue->getJsonDecode();

		if ($dialogue->getHttpCode() != 200) {
			$code = $dialogue->getHttpCode();
			$desc = 'Неизвестная ошибка';
			if (isset($json_decode['error'], $json_decode['description'])) {
				$code = $json_decode['error'];
				$desc = $json_decode['description'];
				// токен устарел
				if ($code == 'invalid_token') {
					$this->refreshAccessToken();
					switch($method) {
						case self::HTTP_POST: {
							$url = preg_replace('/('.self::NAME_ACCESS_TOKEN.'=)[a-z\d]{32}/', '$1'.$token, $url);
							break;
						}
						case self::HTTP_GET: {
							$parameters[self::NAME_ACCESS_TOKEN] = $token;
							break;
						}
					}
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
			throw new Pro_Api_Exception($code, $desc, $dialogue);
		}

		return $dialogue;
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
		$result = $this->executeRequest(
			self::API_HOST.self::POINT_REFRESH_TOKEN,
			array(self::NAME_ACCESS_TOKEN => $this->access_token),
			self::HTTP_GET
		)->getJsonDecode();
		if (isset($result[self::NAME_ACCESS_TOKEN])) {
			$this->setAccessToken($result[self::NAME_ACCESS_TOKEN]);
			$this->access_token_expires = strtotime($result[self::NAME_EXPIRES_IN]);
		}
		return $result;
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

	/**
	 * Строит сигнатуру для ссылки с POST параметрами
	 *
	 * @param string $url  Ссылка
	 * @param array  $post POST параметры
	 *
	 * @return string
	 */
	private function getSignature($url, array $post = array()) {
		$and = (strpos($url, '?') === false) ? '?' : '&';
		$parsed = parse_url($url.$and.http_build_query($post));

		// параметры запроса
		if (isset($parsed['query'])) {
			parse_str($parsed['query'], $parsed['query']);
		} else {
			$parsed['query'] = array();
		}

		$url_hash = '';
		if (!empty($parsed['query'])) {
			unset($parsed['query'][self::NAME_ACCESS_TOKEN], $parsed['query'][self::NAME_SIGNATURE]);
			ksort($parsed['query']);
			$url_hash .= implode('', array_keys($parsed['query']));
			$url_hash .= implode('', array_values($parsed['query']));
		}
		unset($parsed['query']);
		ksort($parsed);
		$url_hash .= implode('', array_values($parsed));

		// хэш url с секретным кодом приложения
		return md5(md5($url_hash).$this->app_secret);
	}

}
/**
 * Диалог между клиентом и сервером API
 */
class Pro_Api_Dialogue {

	/**
	 * URL запроса
	 *
	 * @var string
	 */
	private $url;

	/**
	 * POST параметры запорса
	 *
	 * @var array
	 */
	private $post;

	/**
	 * HTTP код ответа
	 *
	 * @var integer
	 */
	private $http_code;

	/**
	 * Content-Type ответа
	 *
	 * @var string
	 */
	private $content_type;

	/**
	 * HTTP заголовки запроса
	 *
	 * @var array
	 */
	private $request;

	/**
	 * HTTP заголовки ответа
	 *
	 * @var array
	 */
	private $response;

	/**
	 * Тело ответа
	 *
	 * @var array
	 */
	private $body;

	/**
	 * Декодированный JSON тела ответа
	 *
	 * @var mixed
	 */
	private $json_decode;


	/**
	 * Конструктор
	 *
	 * @param string   $response Результат запорса
	 * @param resource $ch       CURL хендлер
	 * @param string   $url      URL запроса
	 * @param array    $post     POST параметры запорса
	 *
	 * @param array $post
	 */
	public function __construct($response, $ch, $url, array $post = array()) {
		$this->url = $url;
		$this->post = $post;
		$this->http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$this->content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

		// разбор запроса к серверу
		$this->request = str_replace("\r\n", "\n", curl_getinfo($ch, CURLINFO_HEADER_OUT));
		list($this->request, ) = explode("\n\n", $this->request);
		$this->request = explode("\n", $this->request);

		// разбор ответа от сервера
		$response = explode("\n\n", str_replace("\r\n", "\n", $response));
		$this->body = array_pop($response);
		$this->response = explode("\n", implode("\n", $response));
		$this->json_decode = json_decode($this->body, true);
	}

	/**
	 * Возвращает URL запроса
	 *
	 * @return string
	 */
	public function getUrl() {
		return $this->url;
	}

	/**
	 * Возвращает POST параметры запорса
	 *
	 * @return array
	 */
	public function getPost() {
		return $this->post;
	}

	/**
	 * Возвращает HTTP код ответа
	 *
	 * @return integer
	 */
	public function getHttpCode() {
		return $this->http_code;
	}

	/**
	 * Возвращает Content-Type ответа
	 *
	 * @return string
	 */
	public function getContentType() {
		return $this->content_type;
	}

	/**
	 * Возвращает HTTP заголовки запроса
	 *
	 * @return array
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * Возвращает HTTP заголовки ответа
	 *
	 * @return array
	 */
	public function getResponse() {
		return $this->response;
	}

	/**
	 * Возвращает тело ответа
	 *
	 * @return array
	 */
	public function getBody() {
		return $this->body;
	}

	/**
	 * Возвращает декодированный JSON тела ответа
	 *
	 * @return array
	 */
	public function getJsonDecode() {
		return $this->json_decode;
	}

	/**
	 * Возвращает параметры запроса в виде массива
	 *
	 * @return array
	 */
	public function toArray() {
		return array(
			'url'          => $this->url,
			'post'         => $this->post,
			'request'      => $this->request,
			'http_code'    => $this->http_code,
			'content_type' => $this->content_type,
			'response'     => $this->response,
			'body'         => $this->body,
			'json_decode'  => $this->json_decode,
		);
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
	 * Диалог
	 *
	 * @var Pro_Api_Dialogue
	 */
	private $dialogue;


	/**
	 * Конструктор
	 *
	 * @param string           $error       Ошибка
	 * @param string           $description Описание ошибки
	 * @param Pro_Api_Dialogue $dialogue    Диалог
	 */
	public function __construct($error, $description, Pro_Api_Dialogue $dialogue) {
		$this->error       = $error;
		$this->dialogue    = $dialogue;
		$this->description = $description;
		parent::__construct($error, $dialogue->getHttpCode());
	}

	/**
	 * Возвращает ошибку
	 *
	 * @return string
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * Возвращает описание ошибки
	 *
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * Возвращает диалог
	 *
	 * @return string
	 */
	public function getDialogue() {
		return $this->dialogue;
	}

}