<?php

declare (strict_types = 1);

namespace VK_Api;

use VK_Api\API;
use VK_Api\objects\User;

class Account extends User
 {
  private static $_instance;

  private function __construct ()
   {
    parent::__construct (0, "empty");
   }

  protected function __clone () {}

  /*
   * @return Account | null
   *
   * Получить экземпляр Account. Если не существует - создает пустой
   */
  static public function getInstance ()
   {
    if (!Api::hasToken ()) //Удаляем обьект, если он существует и (токен == "")
     {
      if (!\is_null (self::$_instance))
       {
        self::$_instance -> _id = 0;
        self::$_instance -> _fields = "empty";
        self::$_instance -> _FOAFloaded = \false;
        self::$_instance -> _dateCreated = \null;
        self::$_instance -> _dateModified = \null;
        unset (self::$_instance -> _container);
        self::$_instance = \null;
       }
     }
    elseif (\is_null (self::$_instance)) self::$_instance = new self ();
    return self::$_instance;
   }

  /*
   * @return int | null
   *
   * Получить временную зону аккаунта
   */
  public function getTimezone ()
   {
    if (\is_null (self::$_instance)) return \null;
    Api::getInstance () -> checkPermission ($this, __FUNCTION__, "timezone", \true);
    return (isset ($this -> container ["timezone"])) ? $this -> container ["timezone"] : \null;
   }
 }