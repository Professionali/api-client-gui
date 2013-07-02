<?
/**
 * @param array|null            $user
 * @param string|null           $error_message
 * @param Pro_Api_Dialogue|null $dialogue
 * @param string|null           $auth_url
 */

$collector = array_merge(array(
        'path'      => '',
        'method'    => Pro_Api_Client::HTTP_GET,
        'token'     => $_SESSION['token'],
        'subscribe' => false,
        'params'    => '',
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
#user {
    position: absolute;
    top: 10px;
    right: 15px;
    width: 180px;
    text-align: center;
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
<?if(isset($error_message)):?>
    <div id="error">Ошибка: <strong><?=$error_message?></strong></div>
<?endif;?>
<?if(!empty($dialogue)):?>
    <div id="result">
        <h3>Результат запроса</h3>
        <?p($dialogue->toArray())?>
    </div>
<?endif;?>
<?if(isset($user)):?>
    <form method="post" action="" name="collector">
        <h3>Сборщик запроса</h3>
        <div class="row">
            <div class="col">
                <label for="collector-path">Путь к API методу</label><br />
                <small><?=Pro_Api_Client::API_HOST?></small>
            </div>
            <div class="col">
                <input type="text" name="collector[path]" id="collector-path" value="<?=$collector['path']?>" placeholder="/users/me/" />
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
                <label for="collector-params">Параметры зпроса</label><br />
                <small>
                    <strong>Ключ значение:</strong><br />
                    key=value<br />
                    <strong>Список значений:</strong><br />
                    key[]=value1<br />
                    key[]=value2<br />
                    <strong>Хэш значений:</strong><br />
                    key[subkey]=value</p>
                </small>
            </div>
            <div class="col">
                <textarea name="collector[params]" id="collector-params" cols="60" rows="7"><?=$collector['params']?></textarea><br />
            </div>
        </div>
        <div class="row">
            <div class="col"></div>
            <div class="col">
                <button type="submit">Отправить</button>
            </div>
        </div>
    </form>
<?elseif(isset($auth_url)):?>
    <p><a href="<?=$auth_url?>">Авторизироваться</a></p>
<?endif;?>
</body>
</html>