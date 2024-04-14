<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

/** @var array $arResult */
/** @var array $arParams */
/** @var CBitrixComponent $component */

\Bitrix\Main\UI\Extension::load("ui.bootstrap4"); ?>

<form class="geoip-search-form mb-3" action="<?= $arResult['AJAX_URL']?>" method="post">
  <div class="mb-3">
    <label for="exampleInputEmail1" class="form-label">IP адрес</label>
    <input required type="text" class="form-control" id="geoip-search-ip" name="ip" aria-describedby="ipHelp">
    <div id="ipHelp" class="form-text">Введите валидный IP адрес</div>
  </div>
  <button type="submit" class="btn btn-primary">Отправить</button>
</form>

<div id="error-block-geoip" class="mb-3"></div>

<table class="table table-bordered" id="geoip-result">
    <thead>
        <th scope="col">IP</th>
        <th scope="col">Страна</th>
        <th scope="col">Регион</th>
        <th scope="col">Город</th>
        <th scope="col">Широта</th>
        <th scope="col">Долгота</th>
        <th scope="col">Временная зона</th>
    </thead>
    <tbody>
        <tr>
            <td class="name"></td>
            <td class="country"></td>
            <td class="region"></td>
            <td class="city"></td>
            <td class="lat"></td>
            <td class="lon"></td>
            <td class="timezone"></td>
        </tr>
    </tbody>
</table>

<script>
    const componentForGeoIP = <?= CUtil::PhpToJSObject($component->getName());?>;
</script>