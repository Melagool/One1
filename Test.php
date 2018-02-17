<?php

include_once "VK_Api\Api.php";

use VK_Api\Api;

$token = "ec4e222dc467f648388ec1e76d5aed862e309e11f44181f9548ac84593562fd64cb743db1937b84aee0b7"; //Токен аккаунта
Api::setToken ($token);
$startId = 472094338; //Начальный id
$checkCount = 2; //Сколько пользователей проверить
for ($i = $startId; $i < $startId + $checkCount; $i++)
  {
   print ("ID ".$i."\n");

   //Проверки API методов
   $user = Api::getUser ($i);
   var_dump ($user -> getDateOfRegistration ());
   var_dump ($user -> getDateOfLastEditing ());
   var_dump ($user -> getRelationPartnerId ());

   print ("---------------------------------------------------------------------------\n");
  }
//var_dump ($user -> getObjectData ()); //Вывод всех данных пользователя целиком
while (true);