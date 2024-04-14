<?php

namespace Sprint\Migration;


class PostEventTypeForGeoIPComponent20240414184850 extends Version
{
    protected $description = "";

    protected $moduleVersion = "4.6.1";

    /**
     * @throws Exceptions\HelperException
     * @return bool|void
     */
    public function up()
    {
        $helper = $this->getHelperManager();
        $helper->Event()->saveEventType('GEOIP_SEARCH_ERROR', array (
  'LID' => 'ru',
  'EVENT_TYPE' => 'email',
  'NAME' => 'Ошибка в работе компонента geoip',
  'DESCRIPTION' => '',
  'SORT' => '150',
));
            $helper->Event()->saveEventMessage('GEOIP_SEARCH_ERROR', array (
  'LID' => 
  array (
    0 => 's1',
  ),
  'ACTIVE' => 'Y',
  'EMAIL_FROM' => '#DEFAULT_EMAIL_FROM#',
  'EMAIL_TO' => '#DEFAULT_EMAIL_FROM#',
  'SUBJECT' => 'Ошибка в работе компонента geoip',
  'MESSAGE' => 'Произошла ошибка при работе компонента geoip.search.

Ошибка:

#ERROR_TEXT#

Подробности:

#EXTRA_DATA#',
  'BODY_TYPE' => 'text',
  'BCC' => '',
  'REPLY_TO' => '',
  'CC' => '',
  'IN_REPLY_TO' => '',
  'PRIORITY' => '',
  'FIELD1_NAME' => '',
  'FIELD1_VALUE' => '',
  'FIELD2_NAME' => '',
  'FIELD2_VALUE' => '',
  'SITE_TEMPLATE_ID' => '',
  'ADDITIONAL_FIELD' => 
  array (
  ),
  'LANGUAGE_ID' => '',
  'EVENT_TYPE' => '[ GEOIP_SEARCH_ERROR ] Ошибка в работе компонента geoip',
));
        }

    public function down()
    {
        //your code ...
    }
}
