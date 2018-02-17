<?php

declare (strict_types = 1);

namespace VK_Api\objects;

class VKObject
 {
  protected $_container;
  protected $_fields;

  /*
   * Обновить информацию обьекта.
   */
  public function update () {}

  /*
   * @return array
   *
   * Получить информацию обьекта в виде контейнера (массива данных)
   */
  public function getObjectData () : array
   {
    return $this -> _container;
   }

  /*
   * @return string
   *
   * Получить список получаемых полей обьекта
   */
  public function getFields () : string
   {
    return $this -> _fields;
   }

  /*
   * @param string $fields
   *
   * Установить список получаемых полей обьекта
   */
  public function setFields (string $fields) : string
   {
    $this -> _fields = $fields;
   }
 }