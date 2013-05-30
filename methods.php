<?php
return array(
    // авторизация
    'oauth' => array(
        array('method' => 'getToken'),
        array('method' => 'refreshToken'),
        array('method' => 'logout'),
    ),
    // пример ошибки
    'errors' => array(
        array('version' => 1, 'method' => 'not_found'),
    ),
    // пользователи
    'users' => array(
        array('version' => 1, 'method' => 'get'),
        array('version' => 1, 'method' => 'positions'),
        array('version' => 1, 'method' => 'schools'),
        array('version' => 1, 'method' => 'contacts'),
        array('version' => 1, 'method' => 'visitors'),
        array('version' => 1, 'method' => 'setAction'),
        array('version' => 1, 'method' => 'isConnected'),
        array('version' => 1, 'method' => 'getActivity'),
        array('version' => 1, 'method' => 'activity'),
        array('version' => 2, 'method' => 'isAdmin'),
    ),
    // сообщения
    'messages' => array(
        array('version' => 1, 'method' => 'get'),
        array('version' => 1, 'method' => 'new'),
        array('version' => 1, 'method' => 'read'),
        array('version' => 1, 'method' => 'unread'),
        array('version' => 1, 'method' => 'trash'),
        array('version' => 1, 'method' => 'untrash'),
        array('version' => 1, 'method' => 'delete'),
        array('version' => 1, 'method' => 'faved'),
        array('version' => 1, 'method' => 'unfaved'),
        array('version' => 3, 'method' => 'count'),
    ),
    // инвайты
    'invites' => array(
        array('version' => 1, 'method' => 'get'),
        array('version' => 1, 'method' => 'new'),
        array('version' => 1, 'method' => 'read'),
        array('version' => 1, 'method' => 'unread'),
        array('version' => 1, 'method' => 'trash'),
        array('version' => 1, 'method' => 'untrash'),
        array('version' => 1, 'method' => 'delete'),
        array('version' => 1, 'method' => 'faved'),
        array('version' => 1, 'method' => 'unfaved'),
        array('version' => 1, 'method' => 'accept'),
        array('version' => 1, 'method' => 'deny'),
        array('version' => 3, 'method' => 'getCanContactInvite'),
        array('version' => 3, 'method' => 'getCanAppInvite'),
        array('version' => 3, 'method' => 'count'),
    ),
    // сообщества
    'groups' => array(
        array('version' => 1, 'method' => 'getRandom'),
        array('version' => 3, 'method' => 'get'),
        array('version' => 3, 'method' => 'getTopic'),
        array('version' => 4, 'method' => 'getMyGroups'),
        array('version' => 4, 'method' => 'getGroupsCatalog'),
        array('version' => 4, 'method' => 'getNewTopics'),
        array('version' => 4, 'method' => 'getComments'),
        array('version' => 4, 'method' => 'getGroupAdmins'),
        array('version' => 4, 'method' => 'getGroupTopics'),
        array('version' => 4, 'method' => 'getTopicLikers'),
        array('version' => 4, 'method' => 'fave'),
        array('version' => 4, 'method' => 'groupApply'),
        array('version' => 4, 'method' => 'groupLeave'),
        array('version' => 4, 'method' => 'groupInvite'),
        array('version' => 4, 'method' => 'getGroupUsers'),
        array('version' => 4, 'method' => 'addComment'),
        array('version' => 4, 'method' => 'topicSubscribe'),
        array('version' => 4, 'method' => 'getFavedTopics'),
        array('version' => 4, 'method' => 'getRights'),
        array('version' => 4, 'method' => 'likeTopic'),
        array('version' => 4, 'method' => 'getTop100Topics'),
        array('version' => 4, 'method' => 'addTopicView'),
    ),
    // лента событий
    'tape' => array(
        array('version' => 1, 'method' => 'get'),
        array('version' => 2, 'method' => 'get'),
        array('version' => 3, 'method' => 'get'),
        array('version' => 3, 'method' => 'new'),
        array('version' => 6, 'method' => 'notifyMyself'),
        array('version' => 6, 'method' => 'notifyWatchers'),
        array('version' => 6, 'method' => 'notifyAppSubscribers'),
    ),
    // поиск
    'search' => array(
        array('version' => 1, 'method' => 'people'),
    ),
    // нотификации
    'notify' => array(
        array('version' => 2, 'method' => 'get'),
        array('version' => 3, 'method' => 'get'),
        array('version' => 4, 'method' => 'get'),
        array('version' => 2, 'method' => 'new'),
    ),
    // хранилище
    'storage' => array(
        array('version' => 2, 'method' => 'get'),
        array('version' => 2, 'method' => 'set'),
    ),
    // push notify
    'push_notify' => array(
        array('version' => 2, 'method' => 'subscribe'),
        array('version' => 2, 'method' => 'unsubscribe'),
        array('version' => 2, 'method' => 'isSubscribe'),
        array('version' => 2, 'method' => 'test'),
    ),
    // компании
    'enterprises' => array(
        array('version' => 5, 'method' => 'staff'),
        array('version' => 5, 'method' => 'positions'),
        array('version' => 5, 'method' => 'get'),
    ),
);