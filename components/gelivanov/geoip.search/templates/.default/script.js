$(document).ready(function() {
    const resultTable = $('#geoip-result');
    const errorBlock = $('#error-block-geoip');
    // Используется для того, чтобы блокировать соединения с сервером, пока не завершено текущее
    let inProcess = false;
    // добавление обработчика подтверждения отправки формы
    $(document).on('submit', '.geoip-search-form', function (e) {
        e.preventDefault();
        // Если других соединений нет
        if (!inProcess) {
            inProcess = true;
            // Вычищаем блок с ошибками
            errorBlock.text('');
            // скрываем таблицу
            resultTable.removeClass('has_result');
            // через объект FormData получаем ip
            let formData = new FormData(this);
            let ip = formData.get('ip');
            // отправляем на сервер запрос
            BX.ajax.runComponentAction(componentForGeoIP,
                'getIpInfo',
                {
                    mode: 'class',
                    data: {ip: ip},
                }
            ).then(function (response) {
                if (response.status === "success") {
                    let data = response.data;
                    if (data.success) {
                        for (let key of Object.keys(data.result)) {
                            resultTable.find(`td.${key}`).text(data.result[key]);
                        }
                        resultTable.addClass('has_result');
                    } else {
                        errorBlock.text(data.text);
                    }
                } else {
                    errorBlock.text('Произошла ошибка на стороне сервера. Пожалуйста, попробуйте позже');
                }
                inProcess = false;
            }).catch(function (reason) {
                errorBlock.text('Что-то пошло не так. Пожалуйста, попробуйте позже');
            });
        }
    });
})
