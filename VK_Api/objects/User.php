<?php

declare (strict_types = 1);

namespace VK_Api\objects;

use VK_Api\Api;
use VK_Api\objects\VKObject;

class User extends VKObject
 {
  public const FULL_FIELDS_NO_TOKEN = "about,activities,bdate,books,can_see_all_posts,can_see_audio,can_send_friend_request,can_write_private_message,career,city,connections,contacts,counters,country,crop_photo,domain,education,exports,first_name_gen,first_name_dat,first_name_acc,first_name_ins,first_name_abl,games,has_mobile,has_photo,home_town,interests,last_name_gen,last_name_dat,last_name_acc,last_name_ins,last_name_abl,last_seen,maiden_name,military,movies,music,nickname,occupation,online,online_mobile,online_app,personal,photo_50,photo_100,photo_200_orig,photo_200,photo_400_orig,photo_id,photo_max,photo_max_orig,quotes,relatives,relation,schools,sex,site,status,trending,tv,universities,verified";
  public const FULL_FIELDS_WITH_TOKEN = self::FULL_FIELDS_NO_TOKEN."blacklisted,blacklisted_by_me,can_post,friend_status,is_favorite,is_hidden_from_feed,timezone";

  protected $_id;
  protected $_FOAFloaded = \false;
  protected $_dateCreated;
  protected $_dateModified;

  /*
   * @param int $id
   * @param string $fields
   * @param array $container
   *
   * Создает обьект User. Если $fields не задан, получает максимальное кол-во информации о странице по умолчанию.
   * Если задан массив $container, то создает обьект с него
   */
  public function __construct (int $id, string $fields = "", array $container = \null)
   {
    $this -> _id = $id;
    $this -> _fields = $fields;
    (!\is_null ($container)) ? $this -> _container = $container : $this -> update (); //Создание обьекта с $container
   }

  /*
   * Обновить информацию обьекта
   */
  public function update ()
   {
    $request = "users.get?";
    if ($this -> _id > 0) $request .= "user_ids=".$this -> _id;
    $container = Api::sendRequest ($request);
    $this -> _id = $container ["response"][0]["id"];
    if ((!isset ($container ["response"][0]["deactivated"])) && ((Api::hasToken ()) || (!isset ($container ["response"][0]["hidden"]))))
     {
      if ($this -> _fields == "")
       {
        if (Api::hasToken ()) $request = "users.get?user_ids=".$this -> _id."&fields=".self::FULL_FIELDS_WITH_TOKEN;
        else $request = "users.get?user_ids=".$this -> _id."&fields=".self::FULL_FIELDS_NO_TOKEN;
       } else $request = "users.get?user_ids=".$this -> _id."&fields=".$this -> _fields;
      $container = Api::sendRequest ($request);
     }
    Api::checkAnswerErrors ($this, __FUNCTION__, $container);
    $this -> _container = $container ["response"][0];
    if ($this -> _FOAFloaded) $this -> loadFOAF ();
   }

  /*
   * Подключиться к VK FOAF и спарсить _dateCreated, _dateModified
   */
  protected function loadFOAF ()
   {
    $data = Api::fileGetContents ("https://vk.com/foaf.php?id=".$this -> _id);
    $this -> _FOAFloaded = \true;
    if (($pos = \strpos ($data, "<ya:created")) !== \false) $this -> _dateCreated = \substr ($data, $pos + 21, 25);
    if (($pos = \strpos ($data, "<ya:modified")) !== \false) $this -> _dateModified = \substr ($data, $pos + 22, 25);
   }

  /*
   * @return int
   *
   * Получить id пользователя
   */
  public function getId () : int
   {
    return $this -> _id;
   }

  /*
   * @return bool
   *
   * Проверка, деактивирована ли страница пользователя
   */
  public function isDeactivated () : bool
   {
    return isset ($this -> _container ["deactivated"]);
   }

  /*
   * @return "deleted" | "banned" | null
   *
   * Получить причину деактивации страницы
   */
  public function getDeactivationReason ()
   {
    return ($this -> isDeactivated ()) ? $this -> _container ["deactivated"] : \null;
   }

  /*
   * @return bool
   *
   * Проверка, установил ли пользователь опцию «Кому в интернете видна моя страница» — «Только пользователям ВКонтакте»
   */
  public function isHidden () : bool
   {
    return isset ($this -> _container ["hidden"]);
   }

  /*
   * @return bool | null
   *
   * Проверка, верифицирована ли страница пользователя
   */
  public function isVerified ()
   {
    Api::checkPermission ($this, __FUNCTION__, "verified");
    return (isset ($this -> _container ["verified"])) ? (bool)($this -> _container ["verified"]) : \null;
   }

  /*
   * @return bool | null
   *
   * Проверка, есть ли на странице пользователя «огонёк»
   */
  public function isTrended ()
   {
    Api::checkPermission ($this, __FUNCTION__, "trending");
    return (isset ($this -> _container ["trending"])) ? (bool)($this -> _container ["trending"]) : \null;
   }

  /*
   * @return bool
   *
   * Проверка, находится ли текущий аккаунт (Account::getInstance ()) в черном списке пользователя ($this)
   */
  public function isBlaclisted () : bool
   {
    Api::checkPermission ($this, __FUNCTION__, "blacklisted", \true);
    return ((isset ($this -> _container ["blacklisted"])) && ($this -> _container ["blacklisted"]));
   }

  /*
   * @return bool
   * 
   * Проверка, находится ли пользователь ($this) в черном списке у текущего аккаунта (Account::getInstance ())
   */
  public function isBlaclistedByMe () : bool
   {
    Api::checkPermission ($this, __FUNCTION__, "blacklisted_by_me", \true);
    return ((isset ($this -> _container ["blacklisted_by_me"])) && ($this -> _container ["blacklisted_by_me"]));
   }

  /*
   * @return bool | null
   *
   * Проверка, находится ли пользователь сейчас на сайте (онлайн)
   */
  public function isOnline ()
   {
    Api::checkPermission ($this, __FUNCTION__, "online");
    return (isset ($this -> _container ["online"])) ? (bool)($this -> _container ["online"]) : \null;
   }

  /*
   * @return int | null
   *
   * Получить id приложения, с которого сидит пользователь
   */
  public function getOnlineAppId ()
   {
    Api::checkPermission ($this, __FUNCTION__, "online");
    return (isset ($this -> _container ["online_app"])) ? (int)($this -> _container ["online_app"]) : \null;
   }

  /*
   * @return 0..3 | nul
   *
    0 — not a friend / не является другом
    1 — outcome request has been sent / отправлена заявка/подписка пользователю
    2 — incoming request has been sent / имеется входящая заявка/подписка от пользователя
    3 — friend / является другом
   *
   * Получить статус дружбы аккаунта (Account::getInstance ()) с пользователем ($this)
   */
  public function getFriendStatus ()
   {
    Api::checkPermission ($this, __FUNCTION__, "friend_status", \true);
    return (isset ($this -> _container ["friend_status"])) ? $this -> _container ["friend_status"] : \null;
   }

  /*
   * @param "nom" | "gen" | "dat" | "acc" | "ins" | "abl" $nameCase
   *
    nom — nominative / именительный
    gen — genitive / родительный
    dat — dative / дательный
    acc — accusative / винительный
    ins — instrumental / творительный
    abl — prepositional / предложный
   *
   * @return string
   *
   * Получить имя пользователя с склонением по падежу
   */
  public function getFirstName (string $nameCase = "nom") : string
   {
    if ($nameCase != "nom") Api::checkPermission ($this, __FUNCTION__, "first_name_".$nameCase);
    if (isset ($this -> _container ["first_name_".$nameCase])) return $this -> _container ["first_name_".$nameCase];
    elseif (isset ($this -> _container ["first_name"])) return $this -> _container ["first_name"];
   }

  /*
   * @param "nom" | "gen" | "dat" | "acc" | "ins" | "abl" $nameCase
   *
    nom — nominative / именительный
    gen — genitive / родительный
    dat — dative / дательный
    acc — accusative / винительный
    ins — instrumental / творительный
    abl — prepositional / предложный 
   *
   * @return string | null
   *
   * Получить фамилию пользователя с склонением по падежу
   */
  public function getLastName (string $nameCase = "nom") : string
   {
    if ($nameCase != "nom") Api::checkPermission ($this, __FUNCTION__, "last_name_".$nameCase);
    if (isset ($this -> _container ["last_name_".$nameCase])) return $this -> _container ["last_name_".$nameCase];
    elseif (isset ($this -> _container ["last_name"])) return $this -> _container ["last_name"];
    else return \null;
   }

  /*
   * @return string | null
   *
   * Получить девичью фамилию пользователя
   */
  public function getMaidenName ()
   {
    Api::checkPermission ($this, __FUNCTION__, "maiden_name");
    return (isset ($this -> _container ["maiden_name"])) ? $this -> _container ["maiden_name"] : \null;
   }

  /*
   * @return string | null
   *
   * Получить отчество (ник) пользователя
   */
  public function getNickname ()
   {
    Api::checkPermission ($this, __FUNCTION__, "nickname");
    return (isset ($this -> _container ["nickname"])) ? $this -> _container ["nickname"] : \null;
   }

  /*
   * @return 0..2 | null
   *
    0 — not specified / пол не указан
    1 — female / женский
    2 — male / мужской
   *
   * Получить пол пользователя
   */
  public function getSex ()
   {
    Api::checkPermission ($this, __FUNCTION__, "sex");
    return (isset ($this -> _container ["sex"])) ? $this -> _container ["sex"] : \null;
   }

  /*
   * @return string | null
   *
   * Получить дату рождения пользователя в формате D.M.YYYY или D.M
   */
  public function getDOB ()
   {
    Api::checkPermission ($this, __FUNCTION__, "bdate");
    return (isset ($this -> _container ["bdate"])) ? $this -> _container ["bdate"] : \null;
   }

  /*
   * @return int | null
   *
   * Получить id страны, указанную на странице пользователя в разделе «Контакты»
   */
  public function getCountryId ()
   {
    Api::checkPermission ($this, __FUNCTION__, "country");
    return (isset ($this -> _container ["country"])) ? $this -> _container ["country"] : \null;
   }

  /*
   * @return int | null
   *
   * Получить id города, указанного на странице пользователя в разделе «Контакты»
   */
  public function getCityId ()
   {
    Api::checkPermission ($this, __FUNCTION__, "city");
    return (isset ($this -> _container ["city"])) ? $this -> _container ["city"] : \null;
   }

  /*
   * @return string | null
   *
   * Получить название родного города
   */
  public function getHomeTown ()
   {
    Api::checkPermission ($this, __FUNCTION__, "home_town");
    return (isset ($this -> _container ["home_town"])) ? $this -> _container ["home_town"] : \null;
   }

  /*
   * @return string | null
   *
   * Получить текст статуса, расположенного в профиле под именем пользователя
   */
  public function getStatus ()
   {
    Api::checkPermission ($this, __FUNCTION__, "status");
    return (isset ($this -> _container ["status"])) ? $this -> _container ["status"] : \null;
   }

  /*
   * @return string | null
   *
   * Получить короткий адрес страницы пользователя
   */
  public function getDomainName ()
   {
    Api::checkPermission ($this, __FUNCTION__, "domain");
    return (isset ($this -> _container ["domain"])) ? $this -> _container ["domain"] : \null;
   }

  /*
   * @return int | null
   *
   * Получить время последнего посещения в формате Unixtime
   */
  public function getLastSeenTime ()
   {
    Api::checkPermission ($this, __FUNCTION__, "last_seen");
    return (isset ($this -> _container ["last_seen"]["time"])) ? $this -> _container ["last_seen"]["time"] : \null;
   }

  /*
   * @return string | null
   *
   * Получить дату и время создания страницы пользователя
   */
  public function getDateOfRegistration ()
   {
    if (!$this -> _FOAFloaded) $this -> loadFOAF ();
    return $this -> _dateCreated;
   }

  /*
   * @return string | null
   *
   * Получить дату и время последнего редактирования страницы пользователя
   */
  public function getDateOfLastEditing ()
   {
    $this -> loadFOAF ();
    return $this -> _dateModified;
   }

  /*
   * @return 1..8 | null
   *
    1 — m.vk.com
    2 — iPhone app
    3 — iPad app
    4 — Android app
    5 — Windows Phone app
    6 — Windows 8 app
    7 — web (vk.com)
    8 — VK Mobile
   *
   * Получить тип платформы последнего посещения пользователя
   */
  public function getLastSeenPlatform ()
   {
    Api::checkPermission ($this, __FUNCTION__, "last_seen");
    return (isset ($this -> _container ["last_seen"]["platform"])) ? $this -> _container ["last_seen"]["platform"] : \null;
   }

  /*
   * @return string | null
   *
   * Получить адрес сайта, указанный в профиле сайт пользователя
   */
  public function getSite ()
   {
    Api::checkPermission ($this, __FUNCTION__, "site");
    return (isset ($this -> _container ["site"])) ? $this -> _container ["site"] : \null;
   }

  /*
   * @return string | null
   *
   * Получить данные об указанном в профиле сервисе Skype
   */
  public function getSkype ()
   {
    Api::checkPermission ($this, __FUNCTION__, "connections");
    return (isset ($this -> _container ["skype"])) ? $this -> _container ["skype"] : \null;
   }

  /*
   * @return string | null
   *
   * Получить данные об указанном в профиле сервисе Facebook
   */
  public function getFacebook ()
   {
    Api::checkPermission ($this, __FUNCTION__, "connections");
    return (isset ($this -> _container ["facebook"])) ? $this -> _container ["facebook"] : \null;
   }

  /*
   * @return string | null
   *
   * Получить данные об указанном в профиле сервисе Twitter
   */
  public function getTwitter ()
   {
    Api::checkPermission ($this, __FUNCTION__, "connections");
    return (isset ($this -> _container ["twitter"])) ? $this -> _container ["twitter"] : \null;
   }

  /*
   * @return string | null
   *
   * Получить данные об указанном в профиле сервисе Livejournal
   */
  public function getLivejournal ()
   {
    Api::checkPermission ($this, __FUNCTION__, "connections");
    return (isset ($this -> _container ["livejournal"])) ? $this -> _container ["livejournal"] : \null;
   }

  /*
   * @return string | null
   *
   * Получить данные об указанном в профиле сервисе Instagram
   */
  public function getInstagram ()
   {
    Api::checkPermission ($this, __FUNCTION__, "connections");
    return (isset ($this -> _container ["instagram"])) ? $this -> _container ["instagram"] : \null;
   }

  /*
   * @return string | null
   *
   * Получить номер мобильного телефона пользователя
   */
  public function getMobilePhone ()
   {
    Api::checkPermission ($this, __FUNCTION__, "contacts", \true);
    return (isset ($this -> _container ["mobile_phone"])) ? $this -> _container ["mobile_phone"] : \null;
   }

  /*
   * @return string | null
   *
   * Получить дополнительный номер телефона пользователя
   */
  public function getHomePhone ()
   {
    Api::checkPermission ($this, __FUNCTION__, "contacts");
    return (isset ($this -> _container ["home_phone"])) ? $this -> _container ["home_phone"] : \null;
   }

  /*
   * @return string | null
   *
   * Получить содержимое поля «О себе» из профиля
   */
  public function getAbout ()
   {
    Api::checkPermission ($this, __FUNCTION__, "about");
    return (isset ($this -> _container ["about"])) ? $this -> _container ["about"] : \null;
   }

  /*
   * @return string | null
   *
   * Получить содержимое поля «Деятельность» из профиля
   */
  public function getActivities ()
   {
    Api::checkPermission ($this, __FUNCTION__, "activities");
    return (isset ($this -> _container ["activities"])) ? $this -> _container ["activities"] : \null;
   }

  /*
   * @return string | null
   *
   * Получить содержимое поля «Любимые книги» из профиля
   */
  public function getBooks ()
   {
    Api::checkPermission ($this, __FUNCTION__, "books");
    return (isset ($this -> _container ["books"])) ? $this -> _container ["books"] : \null;
   }

  /*
   * @return string | null
   *
   * Получить содержимое поля «Любимые игры» из профиля
   */
  public function getGames ()
   {
    Api::checkPermission ($this, __FUNCTION__, "games");
    return (isset ($this -> _container ["games"])) ? $this -> _container ["games"] : \null;
   }

  /*
   * @return string | null
   *
   * Получить содержимое поля «Интересы» из профиля
   */
  public function getInterests ()
   {
    Api::checkPermission ($this, __FUNCTION__, "interests");
    return (isset ($this -> _container ["interests"])) ? $this -> _container ["interests"] : \null;
   }

  /*
   * @return string | null
   *
   * Получить содержимое поля «Любимые фильмы» из профиля
   */
  public function getMovies ()
   {
    Api::checkPermission ($this, __FUNCTION__, "movies");
    return (isset ($this -> _container ["movies"])) ? $this -> _container ["movies"] : \null;
   }

  /*
   * @return string | null
   *
   * Получить содержимое поля «Любимая музыка» из профиля
   */
  public function getMusic ()
   {
    Api::checkPermission ($this, __FUNCTION__, "music");
    return (isset ($this -> _container ["music"])) ? $this -> _container ["music"] : \null;
   }

  /*
   * @return string | null
   *
   * Получить содержимое поля «Любимые цитаты» из профиля
   */
  public function getQuotes ()
   {
    Api::checkPermission ($this, __FUNCTION__, "quotes");
    return (isset ($this -> _container ["quotes"])) ? $this -> _container ["quotes"] : \null;
   }

  /*
   * @return string | null
   *
   * Получить содержимое поля «Любимые телешоу» из профиля
   */
  public function getTV ()
   {
    Api::checkPermission ($this, __FUNCTION__, "tv");
    return (isset ($this -> _container ["tv"])) ? $this -> _container ["tv"] : \null;
   }

  /*
   * @return 0..8 | null
   *
    0 — not specified / не указано
    1 – single / не женат/замужем
    2 – in a relationship / есть друг/подруга
    3 – engaged / помолвлен/помолвлена
    4 – married / женат/замужем
    5 – it's complicated / всё сложно
    6 – actively searching / в активном поиске
    7 – in love / влюблён/влюблена
    8 — in a civil union / в гражданском браке
   *
   * Получить семейное положение пользователя
   */
  public function getRelation ()
   {
    Api::checkPermission ($this, __FUNCTION__, "relation");
    return (isset ($this -> _container ["relation"])) ? $this -> _container ["relation"] : \null;
   }

  /*
   * @return string | null
   *
   * Получить id пользователя, указанного в семейном положении
   */
  public function getRelationPartnerId ()
   {
    Api::checkPermission ($this, __FUNCTION__, "relation");
    return (isset ($this -> _container ["relation_partner"])) ? $this -> _container ["relation_partner"]["id"] : \null;
   }

  /*
   * @return 1..9 | null
   *
    1 – communist / коммунистические
    2 – socialist / социалистические
    3 – moderate / умеренные
    4 – liberal / либеральные
    5 – conservative / консервативные
    6 – monarchist / монархические
    7 – ultraconservative / ультраконсервативные
    8 – apathetic / индифферентные
    9 – libertarian / либертарианские
   *
   * Получить политические предпочтения из раздела «Жизненная позиция»
   */
  public function getPoliticalViews ()
   {
    Api::checkPermission ($this, __FUNCTION__, "personal");
    return (isset ($this -> _container ["personal"]["political"])) ? $this -> _container ["personal"]["political"] : \null;
   }

  /*
   * @return array | null
   *
   * Получить языки из раздела «Жизненная позиция»
   */
  public function getLangs ()
   {
    Api::checkPermission ($this, __FUNCTION__, "personal");
    return (isset ($this -> _container ["personal"]["langs"])) ? $this -> _container ["personal"]["langs"] : \null;
   }

  /*
   * @return string | null
   *
   * Получить мировоззрение из раздела «Жизненная позиция»
   */
  public function getReligion ()
   {
    Api::checkPermission ($this, __FUNCTION__, "personal");
    return (isset ($this -> _container ["personal"]["religion"])) ? $this -> _container ["personal"]["religion"] : \null;
   }

  /*
   * @return string | null
   *
   * Получить источники вдохновения из раздела «Жизненная позиция»
   */
  public function getInspiredBy ()
   {
    Api::checkPermission ($this, __FUNCTION__, "personal");
    return (isset ($this -> _container ["personal"]["inspired_by"])) ? $this -> _container ["personal"]["inspired_by"] : \null;
   }

  /*
   * @return 1..6 | null
   *
    1 – intellect and creativity / ум и креативность
    2 – kindness and honesty / доброта и честность
    3 – health and beauty / красота и здоровье
    4 – wealth and power / власть и богатство
    5 – courage and persistance / смелость и упорство
    6 – humor and love for life / юмор и жизнелюбие
   *
   * Получить главное в людях из раздела «Жизненная позиция»
   */
  public function getMainInOthers ()
   {
    Api::checkPermission ($this, __FUNCTION__, "personal");
    return (isset ($this -> _container ["personal"]["people_main"])) ? $this -> _container ["personal"]["people_main"] : \null;
   }

  /*
   * @return 1..8 | null
   *
    1 – family and children / семья и дети
    2 – career and money / карьера и деньги
    3 – entertainment and leisure / развлечения и отдых
    4 – science and research / наука и исследования
    5 – improving the world / совершенствование мира
    6 – personal development / саморазвитие
    7 – beauty and art / красота и искусство
    8 – fame and influence / слава и влияние
   *
   * Получить главное в жизни из раздела «Жизненная позиция»
   */
  public function getMainInLife ()
   {
    Api::checkPermission ($this, __FUNCTION__, "personal");
    return (isset ($this -> _container ["personal"]["life_main"])) ? $this -> _container ["personal"]["life_main"] : \null;
   }

  /*
   * @return 1..5 | null
   *
    1 – very negative / резко негативное
    2 – negative / негативное
    3 – neutral / компромиссное
    4 – compromisable / нейтральное
    5 – positive / положительное
   *
   * Получить отношение к курению из раздела «Жизненная позиция»
   */
  public function getSmokingAttitude ()
   {
    Api::checkPermission ($this, __FUNCTION__, "personal");
    return (isset ($this -> _container ["personal"]["smoking"])) ? $this -> _container ["personal"]["smoking"] : \null;
   }

  /*
   * @return 1..5 | null
   *
    1 – very negative / резко негативное
    2 – negative / негативное
    3 – neutral / компромиссное
    4 – compromisable / нейтральное
    5 – positive / положительное
   *
   * Получить отношение к алкоголю из раздела «Жизненная позиция»
   */
  public function getAlcoholAttitude ()
   {
    Api::checkPermission ($this, __FUNCTION__, "personal");
    return (isset ($this -> _container ["personal"]["alcohol"])) ? $this -> _container ["personal"]["alcohol"] : \null;
   }

  /*
   * @param 
   *
   * @return array
   *
   * Получить список пользователей, которые являются подписчиками пользователя
   */
  public function getFollowers (int $count, int $offset = 0, string $fields = "") : array
   {
    Api::checkPermission ($this, __FUNCTION__, "", \true);
    $request = "users.getFollowers?user_id=".$this -> _id."&offset=".$offset."&count=".$count."&fields=";
    if ($fields == "")
     {
      $request .=(Api::hasToken ()) ? self::FULL_FIELDS_WITH_TOKEN : $request .= self::FULL_FIELDS_NO_TOKEN;
     } else $request .= $fields;
    $data = Api::sendRequest ($request);
    Api::checkAnswerErrors ($this, __FUNCTION__, $data);
    $result = [];
    foreach ($data ["response"]["items"] as $user) $result [] = new User ($user ["id"], $fields, $user);
    return $result;
   }

  /*
   * @return int | null
   *
   * Получить количество подписчиков
   */
  public function getFollowersCount ()
   {
    if (isset ($this -> _container ["counters"]["followers"])) return $this -> _container ["counters"]["followers"];
    elseif (isset ($this -> _container ["followers_count"])) return $this -> _container ["followers_count"];
    else 
     {
      Api::checkPermission ($this, __FUNCTION__, "counters");
      return \null;
     }
   }

  /*
   * @return int | null
   *
   * Получить количество друзей
   */
  public function getFriendsCount ()
   {
    Api::checkPermission ($this, __FUNCTION__, "counters", \true);
    return (isset ($this -> _container ["counters"]["friends"])) ? $this -> _container ["counters"]["friends"] : \null;
   }

  /*
   * @return int | null
   *
   * Получить количество друзей онлайн
   */
  public function getOnlineFriendsCount ()
   {
    Api::checkPermission ($this, __FUNCTION__, "counters", \true);
    return (isset ($this -> _container ["counters"]["online_friends"])) ? $this -> _container ["counters"]["online_friends"] : \null;
   }

  /*
   * @return int | null
   *
   * Получить количество общих друзей
   */
  public function getCommonFriendsCount ()
   {
    if (isset ($this -> _container ["counters"]["mutual_friends"])) return $this -> _container ["counters"]["mutual_friends"];
    elseif (isset ($this -> _container ["common_count"])) return $this -> _container ["common_count"];
    else
     {
      Api::checkPermission ($this, __FUNCTION__, "counters", \true);
      return \null;
     }
   }

  /*
   * @return int | null
   *
   * Получить количество фотоальбомов
   */
  public function getPhotoAlbumsCount ()
   {
    Api::checkPermission ($this, __FUNCTION__, "counters");
    return (isset ($this -> _container ["counters"]["albums"])) ? $this -> _container ["counters"]["albums"] : \null;
   }

  /*
   * @return int | null
   *
   * Получить количество фотографий
   */
  public function getPhotosCount ()
   {
    Api::checkPermission ($this, __FUNCTION__, "counters");
    return (isset ($this -> _container ["counters"]["photos"])) ? $this -> _container ["counters"]["photos"] : \null;
   }

  /*
   * @return int | null
   *
   * Получить количество аудиозаписей
   */
  public function getAudiosCount ()
   {
    Api::checkPermission ($this, __FUNCTION__, "counters");
    return (isset ($this -> _container ["counters"]["audios"])) ? $this -> _container ["counters"]["audios"] : \null;
   }

  /*
   * @return int | null
   *
   * Получить количество видеозаписей
   */
  public function getVideosCount ()
   {
    Api::checkPermission ($this, __FUNCTION__, "counters");
    return (isset ($this -> _container ["counters"]["videos"])) ? $this -> _container ["counters"]["videos"] : \null;
   }

  /*
   * @return int | null
   *
   * Получить количество подарков
   */
  public function getGiftsCount ()
   {
    Api::checkPermission ($this, __FUNCTION__, "counters");
    return (isset ($this -> _container ["counters"]["gifts"])) ? $this -> _container ["counters"]["gifts"] : \null;
   }

  /*
   * @return int | null
   *
   * Получить количество заметок
   */
  public function getNotesCount ()
   {
    Api::checkPermission ($this, __FUNCTION__, "counters");
    return (isset ($this -> _container ["counters"]["notes"])) ? $this -> _container ["counters"]["notes"] : \null;
   }

  /*
   * @return int | null
   *
   * Получить количество сообществ
   */
  public function getGroupsCount ()
   {
    Api::checkPermission ($this, __FUNCTION__, "counters");
    return (isset ($this -> _container ["counters"]["groups"])) ? $this -> _container ["counters"]["groups"] : \null;
   }

  /*
   * @return int | null
   *
   * Получить количество объектов в блоке «Интересные страницы»
   */
  public function getInterestingPagesCount ()
   {
    Api::checkPermission ($this, __FUNCTION__, "counters", \true);
    return (isset ($this -> _container ["counters"]["pages"])) ? $this -> _container ["counters"]["pages"] : \null;
   }

  /*
   * @return bool | null
   *
   * Проверка, может ли текущий аккаунт (Account::getInstance ()) оставлять записи на стене пользователя ($this)
   */
  public function canPost ()
   {
    Api::checkPermission ($this, __FUNCTION__, "can_post", \true);
    return (isset ($this -> _container ["can_post"])) ? (bool)($this -> _container ["can_post"]) : \null;
   }

  /*
   * @return bool | null
   *
   * Проверка, может ли текущий аккаунт (Account::getInstance ()) видеть чужие записи на стене пользователя ($this)
   */
  public function canSeeOtherPeoplesPosts ()
   {
    Api::checkPermission ($this, __FUNCTION__, "can_see_all_posts");
    return (isset ($this -> _container ["can_see_all_posts"])) ? (bool)($this -> _container ["can_see_all_posts"]) : \null;
   }

  /*
   * @return bool | null
   *
   * Проверка, может ли текущий аккаунт (Account::getInstance ()) видеть аудиозаписи пользователя ($this)
   */
  public function canSeeAudio ()
   {
    Api::checkPermission ($this, __FUNCTION__, "can_see_audio");
    return (isset ($this -> _container ["can_see_audio"])) ? (bool)($this -> _container ["can_see_audio"]) : \null;
   }

  /*
   * @return bool | null
   *
   * Проверка, будет ли отправлено уведомление пользователю ($this) о заявке в друзья от текущего аккаунта (Account::getInstance ())
   */
  public function canSendFriendRequest ()
   {
    Api::checkPermission ($this, __FUNCTION__, "can_send_friend_request");
    return (isset ($this -> _container ["can_send_friend_request"])) ? (bool)($this -> _container ["can_send_friend_request"]) : \null;
   }

  /*
   * @return bool | null
   *
   * Проверка, может ли текущий аккаунт (Account::getInstance ()) отправить личное сообщение пользователю ($this)
   */
  public function canWriteMessage ()
   {
    Api::checkPermission ($this, __FUNCTION__, "can_write_private_message");
    return (isset ($this -> _container ["can_write_private_message"])) ? (bool)($this -> _container ["can_write_private_message"]) : \null;
   }

  /*
   * @return bool | null
   *
   * Проверка, известен ли номер мобильного телефона пользователя
   */
  public function hasMobileNumber ()
   {
    Api::checkPermission ($this, __FUNCTION__, "has_mobile");
    return (isset ($this -> _container ["has_mobile"])) ? (bool)($this -> _container ["has_mobile"]) : \null;
   }
 
  /*
   * @return bool | null
   *
   * Проверка, установил ли пользователь фотографию для профиля
   */
  public function hasAvatar ()
   {
    Api::checkPermission ($this, __FUNCTION__, "has_photo");
    return (isset ($this -> _container ["has_photo"])) ? (bool)($this -> _container ["has_photo"]) : \null;
   }

  /*
   * @return bool | null
   *
   * Проверка, есть ли пользователь ($this) в закладках у текущего аккаунта (Account::getInstance ())
   */
  public function isFavourite ()
   {
    Api::checkPermission ($this, __FUNCTION__, "is_favorite", \true);
    return (isset ($this -> _container ["is_favorite"])) ? (bool)($this -> _container ["is_favorite"]) : \null;
   }

  /*
   * @return bool | null
   *
   * Проверка, скрыт ли пользователь ($this) из ленты новостей текущего аккаунта (Account::getInstance ())
   */
  public function isHiddenFromFeed ()
   {
    Api::checkPermission ($this, __FUNCTION__, "is_hidden_from_feed", \true);
    return (isset ($this -> _container ["is_hidden_from_feed"])) ? (bool)($this -> _container ["is_hidden_from_feed"]) : \null;
   }

  /*
   * @param "porn" | "spam" | "insult" | "advertisment" $type
   * @param string $comment
   *
   * @return bool
   *
   * Пожаловаться на пользователя
   */
  public function report (string $type, string $comment) : bool
   {
    Api::checkPermission ($this, __FUNCTION__, "", \true);
    $data = Api::sendRequest ("users.report?user_id=".$this -> _id."&type=".$type."&comment=".\rawurlencode ($comment));
    Api::checkAnswerErrors ($this, __FUNCTION__, $data);
    return ((isset ($data ["response"])) && ($data ["response"] == 1));
   }

 }
//ToDo: crop_photo, education, career, exports, lists, military, occupation, photos, relatives, schools, universities, status_audio
//ToDo: getNearby, isAppUser, search
