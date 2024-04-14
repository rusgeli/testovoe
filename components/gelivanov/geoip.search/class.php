<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Web\HttpClient;

class GelivanovGeoIpSearchComponent extends CBitrixComponent implements Controllerable
{
    const GEOIP_URL = 'https://api.sypexgeo.net/json/';
    const ENTITY_NAME = 'GeoIPData';
    const EVENT_TYPE = 'GEOIP_SEARCH_ERROR';

    public $lastErrorGeoIPService = '';

    public function configureActions()
    {
        return [
            'getIpInfo' => [
                'prefilters' => [],
            ],
        ];
    }

    public function executeComponent()
    {
        $this->includeComponentTemplate();
    }

    // метод, вызывамый при ajax-запросе
    public function getIpInfoAction($ip)
    {
        // проверка ip на валидность
        $filter_var = filter_var($ip, FILTER_VALIDATE_IP, array(FILTER_FLAG_NO_RES_RANGE, FILTER_FLAG_NO_PRIV_RANGE));
        if (!$filter_var) {
            return $this->sendError('Неверный формат ip', array('IP' => $ip), true);
        }
        // подключение модуля highloadblock
        if (!\Bitrix\Main\Loader::IncludeModule("highloadblock")) {
            return $this->sendError('Не подключен модуль Highloadblock');
        }
        try {
            // получение HL-блока по имени сущности
            $dbHlBlock = Bitrix\Highloadblock\HighloadBlockTable::getList(array(
                'filter' => array(
                    'NAME' => self::ENTITY_NAME
                )
            ));
            $hlblock = $dbHlBlock->fetch();
            if (!$hlblock) {
                return $this->sendError('Отсутствует HL-блок');
            }

            $entity = Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
            $entityDataClass = $entity->getDataClass();

            // получение данных по ip из HL-блока
            $dbRow = $entityDataClass::getList(array(
                'select' => array(
                    'UF_NAME',
                    'UF_COUNTRY',
                    'UF_REGION',
                    'UF_CITY',
                    'UF_LAT',
                    'UF_LON',
                    'UF_TIMEZONE'
                ),
                'filter' => array(
                    'UF_NAME' => $ip
                ),
                'limit' => 1
            ));
            $result = array();
            // если найдены, то используем их
            if ($row = $dbRow->fetch()) {
                // приведение данных к единому виду (каждое поле преобразуется в нижний регистр и отсекается 'UF_', т.е. UF_NAME -> name)
                foreach ($row as $key => $item) {
                    $newKey = strtolower(str_replace('UF_', '', $key));
                    $result[$newKey] = $item;
                    unset($result[$key]);
                }
            }
            // если не найдены, обращаемся к сервису sypexgeo.net
            else {
                $result = $this->getGeoIPDataFromService($ip, $entityDataClass);
                if (!$result) {
                    return $this->sendError('Во время обращения к сервису произошла ошибка', array('ERROR' => $this->lastErrorGeoIPService));
                } else if (!empty($this->lastErrorGeoIPService)) {
                    $this->sendError('Произошла ошибка, не связанная с внешним сервисом', array('ERROR' => $this->lastErrorGeoIPService));
                }
            }

            return $this->sendSuccess($result);
        } catch (Exception $e) {
            return $this->sendError('Произошла ошибка на сервере', array('ERROR' => $e->getMessage()));
        }

    }


    private function getGeoIPDataFromService($ip, $entityDataClass) {
        // инициализация http-клиента bitrix
        $client = new HttpClient();
        $client->setHeader('Content-Type', 'application/json', true);

        $response = $client->get(self::GEOIP_URL . '/' . $ip);
        // Если ответ пустой
        if (empty($response)) {
            $this->lastErrorGeoIPService = 'Пустой ответ';
            return false;
        }
        // декодирование
        $decodedResponse = json_decode($response, true);
        // если не удалось декодировать
        if (!$decodedResponse) {
            $this->lastErrorGeoIPService = 'Некорректные данные получены с сервиса';
            return false;
        }


        $result = array();
        // получение необходимых данных из ответа и приведение их к необходимому виду
        // для отображения выбраны поля с городом, регионом, страной, координатами и временной зоной
        foreach (['country', 'region', 'city'] as $key) {
            if (isset($decodedResponse[$key])) {
                $result[$key] = $decodedResponse[$key]['name_ru'];
                if (!empty($decodedResponse[$key]['lat'] && !empty($decodedResponse[$key]['lon']))) {
                    [$result['lat'], $result['lon']] = [$decodedResponse[$key]['lat'], $decodedResponse[$key]['lon']];
                }
                if (!empty($decodedResponse[$key]['timezone'])) {
                    $result['timezone'] = $decodedResponse[$key]['timezone'];
                }
            }
        }
        $result['name'] = $ip;

        $arFields = array (
            'UF_NAME' => $result['name'],
            'UF_COUNTRY' => $result['country'],
            'UF_REGION' => $result['region'],
            'UF_CITY' => $result['city'],
            'UF_LAT' => $result['lat'],
            'UF_LON' => $result['lon'],
            'UF_TIMEZONE' => $result['timezone']
        );
        // добавление результата в HL блок
        $addResult = $entityDataClass::add($arFields);

        // Если не удалось добавить, то прописываем ошибку, но не обрываем, т.к. данные уже получены
        if(!$addResult->isSuccess()) {
            $this->lastErrorGeoIPService = 'Не удалось добавить данные в базу';
        }

        return $result;
    }
    // Вспомогательный метод для отправки успешного выполнения скрипта и его результата
    private function sendSuccess($result) {
        return array(
            'success' => true,
            'result' => $result
        );
    }
    // Вспомогательный метод для отправки неудачного выполнения скрипта + триггерится почтовое событие для отправки на почту ошибки
    private function sendError($text, $opts = array(), $addTextForClient = false) {
        CEvent::Send(self::EVENT_TYPE, SITE_ID, array(
            "ERROR_TEXT" => $text,
            "EXTRA_DATA" => print_r($opts, true)
        ));
        $errorText = $addTextForClient ? $text : 'Произошла ошибка на стороне сервера. Пожалуйста, попробуйте позже';
        return array(
            'success' => false,
            'text' => $errorText
        );
    }
}


