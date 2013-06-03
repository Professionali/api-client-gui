<!doctype html>
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
        <?endif;?>
        <?if(isset($error_message)):?>
            <p>Ошибка: <strong><?=$error_message?></strong></p>
        <?endif;?>
        <?if(isset($dialogue)):?>
            <p>
                <h3>Результат запроса</h3>
                <?p($dialogue->toArray())?>
            </p>
        <?endif;?>
        <?if(isset($user)):?>
            <form method="post" action="">
            <h3>Сборщик запроса</h3>
            <strong>Метод Api</strong><br />
            <?=Pro_Api_Client::API_HOST?>/
            <select name="method">
            <?foreach($methods as $section => $methods):?>
                <optgroup label="<?=ucwords($section)?>">
                <?foreach ($methods as $method):?>
                    <? $path = $section.'/'.$method['method']?>
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