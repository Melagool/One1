<?php

declare (strict_types = 1);

namespace VK_Api;

include_once "VK_Api\objects\VKObject.php";
include_once "VK_Api\objects\User.php";
include_once "VK_Api\Account.php";

use VK_Api\Account;
use VK_Api\objects\User;

class Api
 {
  public const LATEST_VK_API_VERSION = "5.71";

  private static $_instance;
  private static $_token;

  /*
   * @param VKObject $object
   * @param string $function
   * @param string $neededField
   * @param bool $neededToken
   *
   * Дэбаг функция. Проверяет есть ли у обьекта достаточно прав для выполнения запроса и в случае ошибки - выводит её
   */
  public static function checkPermission ($object, string $function, string $neededField, bool $neededToken = \false)
   {
    if (($neededField != "") && ($object -> getFields () != "") && (\strpos ($neededField, $object -> getFields ()) === \false)) print ("[Функция \"".$function."\" в классе \"".get_class ($object)."\"] Требует field \"".$neededField."\".\n");
    if (($neededToken) && (Api::$_token == "")) print ("[Функция \"".$function."\" в классе \"".get_class ($object)."\"] Требует авторизацию.\n");
   }

  /*
   * @param VKObject | string $object
   * @param string $function
   * @param array $answer
   *
   * Дэбаг функция. Проверяет ответ VK API на корректность и в случае ошибки - выводит её.
   * $object - содержит либо строку с названием класса, либо указатель на класс.
   */
  public static function checkAnswerErrors ($object, string $function, array $answer)
   {
    if (isset ($answer ["error"])) 
    {
     $class = (\is_string ($object)) ? $object : get_class ($object);
     print ("[Функция \"".$function."\" в классе \"".$class."\"] Ошибка запроса № ".$answer ["error"]["error_code"].". ");
     if (isset ($answer ["error"]["error_msg"])) print ($answer ["error"]["error_msg"].". ");
     if (isset ($answer ["error"]["error_text"])) print ($answer ["error"]["error_text"].". ");
     print ("\n");
     var_dump ($answer);
    }
   }

  /*
   * @param string $url
   *
   * @return string
   *
   * Получить содержимое WEB страницы
   */
  public static function fileGetContents (string $url) : string
   {
    return \file_get_contents ($url, \false, \stream_context_create (["ssl" => ["verify_peer" => \false, "verify_peer_name" => \false]/*, "http" => ["proxy" => "46.163.186.9:3129", "request_fulluri" => \true]*/]));
   }

  /*
   * @param string $request
   *
   * @return array
   *
   * Отправить запрос VK API напрямую
   */
  public static function sendRequest (string $request) : array
   {
    $request = "https://api.vk.com/method/".$request;
    if (\strpos ($request, "&v=") === \false) $request .= "&v=".self::LATEST_VK_API_VERSION;
    if (Api::$_token != "") $request .= "&access_token=".Api::$_token;
    $settings = ["ssl" => ["verify_peer" => \false, "verify_peer_name" => \false], "http" => ["proxy" => "46.163.186.9:3129", "request_fulluri" => \true]];
    return \json_decode (self::fileGetContents ($request), \true);
   }

  /*
   * @return bool
   *
   * Проверка, задан ли токен
   */
  public static function hasToken () : bool
   {
    return Api::$_token != "";
   }

  /*
   * @param string $token
   *
   * Установить токен
   */
  public static function setToken (string $token = "")
   {
    $newToken = ((Api::$_token != $token) && ($token != ""));
    Api::$_token = $token;
    if ($newToken) Account::getInstance () -> update (); //Если введен новый токен и ($token != "") - обновляем инфу Account
    else Account::getInstance (); //Удаляем Account если ($token == "")
   }

  /*
   * @param int $id
   * @param string $fields
   *
   * @return User
   *
   * Получить обьект User с определенными данными
   */
  public static function getUser (int $id, string $fields = "") : User
   {
    return new User ($id, $fields);
   }


  /*
   * @param array $ids
   * @param string $fields
   *
   * @return array
   *
   * Получить массив обьектов User с определенными данными
   */
  public static function getUsers (array $ids, string $fields = "") : array
   {
    $request = "users.get?user_ids=".\join (",", $ids)."&fields=";
    if ($fields == "")
     {
      $request .=(Api::hasToken ()) ? User::FULL_FIELDS_WITH_TOKEN : $request .= User::FULL_FIELDS_NO_TOKEN;
     } else $request .= $fields;
    $container = Api::sendRequest ($request);
    Api::checkAnswerErrors ("", __FUNCTION__, $container);
    $result = [];
    foreach ($container ["response"] as $user) $result [] = new User ($user ["id"], $fields, $user);
    return $result;
   }


  /*
   * @return Account | null
   *
   * Получить обьект Account - владельца токена
   */
  public static function getAccount ()
   {
    return Account::getInstance ();
   }
 }