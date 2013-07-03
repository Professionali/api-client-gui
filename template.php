<?
/**
 * @param array|null            $user
 * @param string|null           $error_message
 * @param Pro_Api_Dialogue|null $dialogue
 * @param string|null           $auth_url
 */

$collector = array_merge(array(
        'path'       => '',
        'method'     => Pro_Api_Client::HTTP_GET,
        'token'      => $_SESSION['token'],
        'subscribe'  => false,
        'parameters' => '',
    ),
    !empty($_POST['collector']) ? $_POST['collector'] : array()
);
?><!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>API клиент Professionali.ru</title>
<style type="text/css">
body {
    line-height: 15px;
    font: 400 normal 13px 'Trebuchet MS',Arial,Helvetica,sans-serif;
    color: #333;
    margin: 10px 15px;
}
a {
    color: #1A62A9;
    text-decoration: none;
}
a:hover {
    text-decoration: underline;
}
label {
    font-weight: bold;
}
h1, h3 {
    margin: 0 0 10px 0;
}
h3 {
    font: 700 normal 16px 'Trebuchet MS',Arial,Helvetica,sans-serif;
}
pre {
    overflow: auto;
    padding: 10px;
    margin: 0 0 10px;
    font-family: monospace;
    font-size: 11px;
    color: #000;
    background: #F5F5F5;
    border: 1px #DDD solid;
}
textarea,
input[type=text] {
    border: 1px #333 solid;
    width: 500px;
}
#user {
    position: absolute;
    top: 10px;
    right: 15px;
    width: 185px;
    text-align: center;
}
#error, #result {
    clear: both;
    margin-top: 15px;
}
#collector {
    margin-right: 190px;
    padding-bottom: 36px;
    border-bottom: 1px #DDD solid;
}
.row {
    clear: both;
}
.col {
    float: left;
    min-height: 26px;
    margin-top: 10px;
}
.col:first-child {
    width: 150px;
}
.row:last-child .col {
    margin-top: 0;
}
</style>
</head>
<body>
<h1>API клиент <a href="http://professionali.ru">Professionali.ru</a></h1>
<?if(isset($user)):?>
    <div id="user">
        <h3>Текущий пользователь</h3>
        <p>
            <img src="<?=$user['avatar_big']?>" alt="<?=$user['name']?>"/><br/>
            <a href="<?=$user['link']?>"><?=$user['name']?></a><br/>
            <a href="<?=$exit_uri?>">Выход</a>
        </p>
    </div>
<?endif;?>
<?if(isset($user)):?>
    <div id="collector">
        <form method="post" action="" name="collector">
            <h3>Сборщик запроса</h3>
            <div class="row">
                <div class="col">
                    <label for="collector-path">Путь к API методу</label><br />
                    <small><?=Pro_Api_Client::API_HOST?></small>
                </div>
                <div class="col">
                    <input type="text" name="collector[path]" id="collector-path" value="<?=$collector['path']?>" placeholder="/v6/users/get.json" />
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <label for="collector-token">Токен</label>
                </div>
                <div class="col">
                    <input type="text" name="collector[token]" id="collector-token" value="<?=$collector['token']?>" size="32" />
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <label for="collector-subscribe">Подписать запрос</label>
                </div>
                <div class="col">
                    <input type="checkbox" name="collector[subscribe]" id="collector-subscribe"<?if(!empty($collector['subscribe'])):?> checked="checked"<?endif?> value="yes" />
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <label for="collector-method">HTTP метод</label>
                </div>
                <div class="col">
                    <select name="collector[method]" id="collector-method">
                        <option<?if($collector['method'] == Pro_Api_Client::HTTP_GET):?> selected="selected"<?endif?>><?=Pro_Api_Client::HTTP_GET?></option>
                        <option<?if($collector['method'] == Pro_Api_Client::HTTP_POST):?> selected="selected"<?endif?>><?=Pro_Api_Client::HTTP_POST?></option>
                        <option<?if($collector['method'] == Pro_Api_Client::HTTP_PUT):?> selected="selected"<?endif?>><?=Pro_Api_Client::HTTP_PUT?></option>
                        <option<?if($collector['method'] == Pro_Api_Client::HTTP_DELETE):?> selected="selected"<?endif?>><?=Pro_Api_Client::HTTP_DELETE?></option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <label for="collector-parameters">Параметры зпроса</label><br />
                    <small>
                        <strong>Ключ значение:</strong><br />
                        key=value<br />
                        <strong>Список значений:</strong><br />
                        key[]=value1<br />
                        key[]=value2<br />
                        <strong>Хэш значений:</strong><br />
                        key[subkey]=value
                    </small>
                </div>
                <div class="col">
                    <textarea name="collector[parameters]" id="collector-parameters" cols="60" rows="7"><?=$collector['parameters']?></textarea><br />
                </div>
            </div>
            <div class="row">
                <div class="col"></div>
                <div class="col">
                    <button type="submit">Отправить</button>
                </div>
            </div>
        </form>
    </div>
<?elseif(isset($auth_url)):?>
    <p><a href="<?=$auth_url?>">Авторизироваться</a></p>
<?endif;?>
<?if(isset($error_message)):?>
    <div id="error">
        <h3>Ошибка</h3>
        <p><?=$error_message?></p>
    </div>
<?endif;?>
<?if(!empty($dialogue)):?>
    <div id="result">
        <h3>Результат диалога</h3>
        <?if($dialogue->getRequest()):?>
            Запрос <pre><?=implode("\n", $dialogue->getRequest())?></pre>
        <?endif?>
        <?if($dialogue->getResponse()):?>
            Ответ <pre><?=implode("\n", $dialogue->getResponse())?></pre>
        <?endif?>
        Тело ответа
        <?if($dialogue->getJsonDecode()):?>
            <pre><?=str_replace('\\\\', '\\', json_encode(
                    $dialogue->getJsonDecode(),
                    JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_NUMERIC_CHECK
                ))?></pre>
        <?else:?>
            <pre><?=$dialogue->getBody()?></pre>
        <?endif?>
    </div>
<?endif;?>
</body>
</html>