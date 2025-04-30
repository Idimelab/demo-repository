var inicializador_tralado = 0;
var inicializador = 0;
var nombre_emisor = '';
var numero_total_mensaje = 0;

function validateFieldStringProgram(element, text_value, lengh_max = 1000) {

    //Validamos si el valor es 'null'
    if (text_value == null)
        text_value = '';

    if (text_value.trim() == null || text_value.trim().length === 0) {
        //Pintamos el 'elemento' con bordes rojos
        element.addClass('border border-danger');
        return false;
    } else if (text_value.length > lengh_max) {
        //Pintamos el 'elemento' con bordes rojos
        element.addClass('border border-danger');
        return false;
    } else {
        //Quitamos del 'elemento' los bordes rojos
        element.removeClass('border border-danger');
        return true;
    }
}

//validacion de int devuelve tru o false
function validateFieldIntProgram(element, value, max_length = 1000) {

    if (value == null || value.length === 0 || /^\s+$/.test(value) || value < 0) {
        //Pintamos el 'elemento' con bordes rojos
        element.addClass('border border-danger');
        return false;
    } else if (value.length > max_length) {
        //Pintamos el 'elemento' con bordes rojos
        element.addClass('border border-danger');
        return false;
    } else if (isNaN(value)) {
        //Pintamos el 'elemento' con bordes rojos
        element.addClass('border border-danger');
        return false;
    } else {
        //Quitamos del 'elemento' los bordes rojos
        element.removeClass('border border-danger');
        return true;
    }
}


function validateTextString(text_input, lengh_max = 1000, leyenda = 'Campo no validado') {
    if (text_input == null) {
        validationMessageError(leyenda);
        return false;
    } else if (text_input.trim() === null || text_input.trim().length === 0) {
        //llamamos la alerta para poner los bordes rojos y en ella llama la funcion para generar la alerta segun la leyenda escrita
        validationMessageError(leyenda);
        return false;
    } else if (text_input.length > lengh_max) {
        validationMessageError('No superar ' + lengh_max + ' caracteres');
        return false;
    } else {
        //llamamos la funcion para eliminar los bordes rojos
        return true;
    }
}

// validacion para tipos de texto, si es false el borde bottom se vuelve rojo y devuelve tru o false segun el csao,
// los parametros son la clase del input y la cantidad mxima de letras y la leyenda es lo que va adecir si es error

var MessageError = "Error en la validacion del campo";

function remover_clase(index, clase) {
    $(index).removeClass(clase)
}

function asignar_clase(index, clase) {
    $(index).addClass(clase)
}

function validateTextAllText(text_input, lengh_max, leyenda = MessageError) {

    text = $("." + text_input).val();
    if (text == null || text.length == 0 || /[¿!"#$%&/()=?¡'{}_+´´*;:., ']/.test(text)) {
        //llamamos la alerta para poner los bordes rojos y en ella llama la funcion para generar la alerta segun la leyenda escrita
        validationErrorInput('Solo debe permitir letras, A - Z sin espacios', leyenda);
        return false;
    } else if (text.length > lengh_max) {
        validationErrorInput('No superar ' + lengh_max + ' caracteres', leyenda);

        return false;
    } else if (isNaN(text) == false) {
        validationErrorInput('No admite numeros', leyenda)

        return false;
    } else {
        //llamamos la funcion para eliminar los bordes rojos
        validateSuccess(leyenda);

        return true;
    }
}

function validateText(text_input, lengh_max, leyenda = MessageError) {

    var text = $("." + text_input).val();
    if (text == null || text.length == 0 || /[¿!"#$%&/()=?¡'{}_+´´*;:.,']/.test(text)) {
        //llamamos la alerta para poner los bordes rojos y en ella llama la funcion para generar la alerta segun la leyenda escrita
        validationErrorInput(leyenda, text_input);
        return false;
    } else if (text.length > lengh_max) {
        validationErrorInput('No superar ' + lengh_max + ' caracteres', text_input);

        return false;
    } else {
        //llamamos la funcion para eliminar los bordes rojos
        validateSuccess(text_input);

        return true;
    }
}

function validateDateTime(text_input, lengh_max, leyenda = MessageError) {
    var texto = text_input.split(' '),
        fecha = texto[0],
        hora = texto[1];
    if (validateDate(fecha, leyenda) == false || validate_time(hora, leyenda) == false) {
        validationMessageError(leyenda);
        return false;
    }

    return true;
}

function validateDate(text_input, leyenda = MessageError) {

    let date = new Date(text_input);

    if (date === "Invalid Date")
        return false;

    return true;
}

// se valida no con el css si no con el objet de el parametro
function validateDateTimeRange(Fecha_ini, Fecha_fina, leyenda = MessageError) {

    var fecha_inicial_split = Fecha_ini.split(' '),
        fecha_final_split = Fecha_fina.split(' '),
        hora_inicio = fecha_inicial_split[1],
        hora_final = fecha_final_split[1],
        Fecha_ini = new Date(Fecha_ini),
        Fecha_fina = new Date(Fecha_fina);

    // Comparamos solo las fechas => no las horas!!


    if (Fecha_ini > Fecha_fina) {
        validationMessageWarning(leyenda);
        return false;
    } else if (fecha_inicial_split[0] == fecha_final_split[0]) {

        return validate_time_diferent(hora_inicio, hora_final, 'La hora inicial no puede ser mayor a la final cuando las fechas son el mismo dia');
    } else {

        return true;
    }
}

//validameos que el input puede tener un alfanumerico pero sin caractreres especiales, si se valida algo en esta funcion, se debe de validar en la calse de validatedata
//para la validacion de contrseña pero en php
function validatePassword(text_input, lengh_max, leyenda = MessageError) {
    text = $("." + text_input).val();
    // set password variable
    //validate the length
    if (text.length < 8) {
        validationErrorInput('La contraseña debería tener 8 carácteres como mínimo', text_input);
        return false;
    }
    if (text.match(/[A-z]/) == false) {
        validationErrorInput('La contraseña al menos debería tener una letra', text_input);
        return false;
    } else if (text.match(/\d/) == false) {
        validationErrorInput('La contraseña al menos debería tener un número', text_input);
        return false;
    } else {
        //llamamos la funcion para eliminar los bordes rojos como parametro el identificador de clase
        validateSuccess(text_input);
        return true;
    }
}

function validateSomePasswords(element_pass, element_pass_repeat) {

    //Obtenemos los valores de los elementos recibidos
    let val_password = $('.' + element_pass).val();
    let val_password_repeat = $('.' + element_pass_repeat).val();

    //Validamos si son iguales
    if (val_password === val_password_repeat) {
        validateSuccess(element_pass);
        validateSuccess(element_pass_repeat);
        return true;
    } else {
        validationErrorInput('Las contraseñas no coinciden.', element_pass);
        validationErrorInput('Las contraseñas no coinciden.', element_pass_repeat);

        return false;
    }
}

// validacion de hora
function validate_time(valor, leyenda = MessageError, element) {

    if (valor.indexOf(":") != -1) {

        var hora = valor.split(":")[0];

        if (parseInt(hora) > 23) {
            //Pintamos el 'elemento' con bordes rojos
            element.addClass('border border-danger');
            return false;
        }

        var minuto = valor.split(":")[1];
        if (parseInt(minuto) > 59) {
            //Pintamos el 'elemento' con bordes rojos
            element.addClass('border border-danger');
            return false;
        }

        //Quitamos del 'elemento' los bordes rojos
        element.removeClass('border border-danger');
        return true;
    } else {
        //Pintamos el 'elemento' con bordes rojos
        element.addClass('border border-danger');
        return false;
    }
}

function countRepeatPointCharacter(string) {
    //Regular expression that searches for all the points that exist
    var regularExpression = new RegExp("[^.]", "g");
    //We look for the points that exist in the chain and, we obtain the number of times they were found
    var numberRepetitions = string.replace(regularExpression, "").length;
    //We return the number of repetitions
    return numberRepetitions;
}

// validar decimales
function validateDecimal(num) {
    // We keep the number of times a point (.) is repeated
    var numberRepetitions = countRepeatPointCharacter(num);
    // We obtain the position in which the first point is found (.)
    var numberPositionPointCharacter = num.indexOf('.');
    //Initialize variable that will validate the 'Value' field
    var validateValue = true;
    // We validate that the value does not exceed 21 characters allowed
    if (num.length > 21) {

        validateValue = false;
    }
    //We validate that the point character (.) Does not repeat more than once
    else if (numberRepetitions > 1) {
        validationMessageWarning('El precio del producto sólo debe tener un punto (.) decimal.', 3500);
        validateValue = false;
    }
    //We validate that the value is a valid number
    else if (isNaN(num)) {
        validationMessageWarning('El precio del producto debe ser un número válido.', 3500);
        validateValue = false;
    }
    // We validate that the whole part does not exceed 16 digits and that there not exist point in the first 6 digits
    else if ((numberPositionPointCharacter > 16) || (num.length > 16 && num.indexOf('.') == -1)) {
        validationMessageWarning('Sólo se permiten (16) números enteros.', 3500);
        validateValue = false;
    }
    //We return validation (True | False) to execute the AJAX request
    return validateValue;
}

// validacion de hora mayoro menor
function validate_time_diferent(inicial, final, leyenda = MessageError) {

    let inicial1 = inicial.split(":"),
        final1 = final.split(":"),
        hora_inicial = inicial1[0],
        hora_final = final1[0],
        minuto_inicial = inicial1[1],
        minuto_final = final1[1];
    if (hora_inicial == hora_final && minuto_inicial == minuto_final) {
        validationMessageWarning('Las Horas no pueden ser iguales');
        return false;
    } else if (hora_inicial > hora_final) {
        validationMessageWarning(leyenda);
        return false;
    } else if (minuto_inicial > minuto_final && hora_inicial == hora_final) {
        validationMessageWarning(leyenda);
        return false;
    } else {
        return true;
    }
}

function validar_traslado(id_operador_mensaje, nombre_operador, tipo, id_conversacion) {

    if (inicializador == 0) {

        inicializador_tralado = id_operador_mensaje;
        nombre_emisor = nombre_operador;
        inicializador = 1;
    } else {
        if (inicializador_tralado != id_operador_mensaje && tipo == 'enviado') {
            agregar_traslado(nombre_emisor, nombre_operador, id_conversacion);
            nombre_emisor = nombre_operador;
            inicializador_tralado = id_operador_mensaje;
        } else {
            nombre_emisor = nombre_operador;
        }
    }
}

function agregar_traslado(nombre_emisor, nombre_receptor, id_conversacion) {

    var html = $('.js-container-tralation');
    $('.js-container-message_' + id_conversacion).prepend(html.html());
    var index = $('.js-container-message_' + id_conversacion + ' .js-nuevo-tralation');
    if (nombre_receptor == null) {
        $(index).find('.texto span').html('MENSAJES SIN CONTESTAR');
    } else {

        $(index).find('.texto span').html('La conversación fue transferida del operador <b>(' + nombre_emisor + ')</b> a el operador <b>(' + nombre_receptor + ')</b>')
    }
    $(index).removeClass('js-nuevo-tralation');
}

function agregar_finalizado(nombre_emisor) {

    var html = $('.js-container-tralation');
    $('.js-container-message').prepend(html.html());
    var index = $('.js-container-message .js-nuevo-tralation');
    $(index).find('.texto span').html('La conversación fue finalizada por el operador <b>(' + nombre_emisor + ')</b>');
    $(index).removeClass('js-nuevo-tralation');

}

//se valida un select, la logica es simple un select tiene como value un id, asi que lo valido como int y el texto que retorna es referencia al selec
function validateSelect(index_select, leyenda = MessageError, selec = 'Selecciona') {
    var text = $(index_select).val();
    if (text === selec) {
        //llamamos la alerta para poner los bordes rojos y en ella llama la funcion para generar la alerta segun la leyenda escrita
        validationErrorInput(leyenda, index_select);
        return false;
    } else {
        //llamamos la funcion para eliminar los bordes rojos
        validateSuccess(index_select);
        return true;
    }
}

function validateRangeInt(inicio, final, valor, leyenda) {
    if (valor < inicio || valor > final) {
        validationMessageWarning(leyenda);
        return false;
    } else {
        return true;
    }
}

//validacion de int devuelve tru o false
function validateInt(text_input, lengh_max, leyenda = MessageError) {

    var text = $("." + text_input).val();

    if (text == null || text.length == 0 || /^\s+$/.test(text) || text < 0) {
        //llamamos la alerta para poner los bordes rojos y en ella llama la funcion para generar la alerta segun la leyenda escrita
        validationErrorInput(leyenda, text_input);
        return false;
    } else if (text.length > lengh_max) {
        validationErrorInput('No superar ' + lengh_max + ' caracteres', text_input);
        return false;
    } else if (isNaN(text)) {
        //llamamos la alerta para poner los bordes rojos y en ella llama la funcion para generar la alerta segun la leyenda escrita
        validationErrorInput('El campo debe conteneder solo numeros', text_input);
        return false;
    } else {
        //llamamos la funcion para eliminar los bordes rojos
        validateSuccess(text_input);
        return true;
    }
}

//validacion de int devuelve tru o false
function validateIntString(text_input, lengh_max, leyenda = MessageError) {

    var text = text_input;
    if (text == null || text.length == 0 || /^\s+$/.test(text) || text < 0) {
        //llamamos la alerta para poner los bordes rojos y en ella llama la funcion para generar la alerta segun la leyenda escrita
        // validationMessageError(leyenda);
        return false;
    } else if (text.length > lengh_max) {
        // validationMessageError('No superar ' + lengh_max + ' caracteres');
        return false;
    } else if (isNaN(text)) {
        //llamamos la alerta para poner los bordes rojos y en ella llama la funcion para generar la alerta segun la leyenda escrita
        // validationMessageError('El campo debe conteneder solo numeros');
        return false;
    } else {
        //llamamos la funcion para eliminar los bordes rojos

        return true;
    }
}

//validacion de correo devuelve tru o false
function validateMail(text_input, leyenda = MessageError) {
    text = $("." + text_input).val();
    if (text.search(/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,4})+$/)) {
        validationErrorInput(leyenda, text_input)
    } else {
        //llamamos la funcion para eliminar los bordes rojos
        validateSuccess(text_input);
        return true;
    }

}

//cuando la funcion es false cambiamos el color a rojo del border para indicar al usuario donde esta la falla
function validationErrorInput(leyenda = "Error en la validacion", text_input) {
    //cambiamos el color de borde  a rojo
    $("." + text_input).addClass('border border-danger')

    //llamamos el mensaje de error con la leyenda
    validationMessageError(leyenda, 4000);
}

// aqui tenemos funcion para mensaje de error, le damos como parametro leyenda o lo que va a decir, y el tiempo, cada uno con su valor predefinido
function validationMessageError(leyenda = 'Accion Fallida', time = 4000) {
    $.notify.defaults({
        clickToHide: true,
        autoHide: true,
        autoHideDelay: time,
    });
    $.notify(leyenda, 'error');
}

// aqui tenemos funcion para mensaje de succes, le damos como parametro leyenda o lo que va a decir, y el tiempo, cada uno con su valor predefinido
function validationMessageSuccess(leyenda = 'Accion Realizada', time = 4000) {
    $.notify.defaults({
        clickToHide: true,
        autoHide: true,
        autoHideDelay: time,
    });
    $.notify(leyenda, 'success');
}

function validationMessageWarning(leyenda = 'Accion Realizada', time = 4000) {
    $.notify.defaults({
        clickToHide: true,
        autoHide: true,
        autoHideDelay: time,
    });
    $.notify(leyenda, 'warning');
}

//aqui es cuando es correcta la validacion eliminamos el color rojo de borde
function validateSuccess(text_input) {
    $("." + text_input).removeClass('border border-danger');
}

function focusInputIndex0(index) {
    var obj = $(index),

        // Guardamos en una variable el contenido
        val = obj.val();

    // Ponemos el foco, limpiamos el contenido y volvemos a poner
    // nuevamente el mismo contenido
    if (obj.length > 0) {

        obj.focus().val("").val(val);

        // Movemos el scroll
        obj.scrollTop(obj[0].scrollHeight);
    }
}

function positionCursor(index) {

    $(index).focus();
    var tmp = $('<span />').appendTo($(index)),
        node = tmp.get(0),
        range = null,
        sel = null;

    if (document.selection) {
        range = document.body.createTextRange();
        range.moveToElementText(node);
        range.select();
    } else if (window.getSelection) {
        range = document.createRange();
        range.selectNode(node);
        sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(range);
    }
    tmp.remove();
    return index;
}

// es metodo lo unico que hace es remplazar el total de registros en cada interface, sucursales, usuario etc.. por
// una nueva cantidad que es la que devuelve el crud de eliminar, y simplemente lo que se hace es
// hacer una consulta count  y listo
function updateNumR(numero) {
    $('.card-body h2').html(numero);
}

/**
 * @Description: Format a decimal number, add decimals, separate decimals from integers, and separate thousand units
 * @Param: Number|NumberDecimals|DecimalSeparator|ThousandUnitsSeparator
 * @return: (string) formatted number
 */
function number_format_js(number, decimals, dec_point, thousands_point) {

    if (number == null || !isFinite(number)) {
        throw new TypeError("number is not valid");
    }

    if (!decimals) {
        var len = number.toString().split('.').length;
        decimals = len > 1 ? len : 0;
    }

    if (!dec_point) {
        dec_point = '.';
    }

    if (!thousands_point) {
        thousands_point = ',';
    }

    number = parseFloat(number).toFixed(decimals);

    number = number.replace(".", dec_point);

    var splitNum = number.split(dec_point);
    splitNum[0] = splitNum[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousands_point);
    number = splitNum.join(dec_point);

    return number;
}

/**
 * @Description: Method that returns the value formatted with correct decimals
 * @Call: When el método 'addOrRemoveDecimalsToAffiliateValue()' es ejecutado
 * @Param: String (Value or Balance of afiliate)
 * @return: (sting) Number with decimals without aproximation
 */
function numberFormatNoApproximationJs(valueBalanceAfiliate) {
    //We capture the 'value of the affiliate' in a variable to facilitate access to it
    var valueBalance = valueBalanceAfiliate;
    //Formatting the value, we show 4 decimals, separate the decimals with (,) and separate with space the units of a thousand
    valueBalance = number_format_js(valueBalance, 4, ',', ' ');
    //We obtain and save the last 4 characters of the received value as parameter
    var correctDecimals = valueBalanceAfiliate.substr(valueBalanceAfiliate.length - 4);
    //We obtain and save the last 5 characters of the formatted value, also obtaining the comma (,) that separates the decimals
    var decimalsToEliminate = valueBalance.substr(valueBalance.length - 5);
    //We search and delete the last 5 characters obtained to leave only the whole part of the value
    var valueWithoutDecimals = valueBalance.replace(decimalsToEliminate, '');
    //The value of integers is concatenated with decimals of the original value
    var formattedValue = valueWithoutDecimals + ',' + correctDecimals;
    //We return the formatted value of the member's obligation and with the correct decimals
    return formattedValue;
}

/**
 * @Description: Method to validate the addition or not of the decimals of the balance
 * @Param: String (Value or Balance of afiliate)
 * @return: (sting) Number with or without decimals
 */
function addOrRemoveDecimalsToAffiliateValue(valueBalanceAffiliate) {
    //We obtain and save the total balance of the affiliate
    var valueBalance = valueBalanceAffiliate;
    //We obtain the position in which the last point is found (.)
    var positionCharacterLastPoint = valueBalance.lastIndexOf('.');
    //We get the last 4 characters of the value, which are the decimals
    var lastFourChararcters = valueBalance.substr(positionCharacterLastPoint + 1, 4);
    //Initialize variable that will contain the definitive value
    var formattedValue = '';
    //We evaluate if the different decimals of the number end in '0000', '000', '00' or '0'; and hide them respectively
    if (lastFourChararcters === '0000') {
        //We format the value and we do not show decimals
        formattedValue = number_format_js(valueBalance, 0, '', ' ');
        //We search and delete the last 3 characters to delete the comma (,) and the zeros (00) that are added by default (, 00)
        formattedValue = formattedValue.replace(formattedValue.substr(-3), '');
    } else if (lastFourChararcters.substr(-3) === '000') {
        //We format the value and we do show one decimal
        formattedValue = number_format_js(valueBalance, 1, ',', ' ');
    } else if (lastFourChararcters.substr(-2) === '00') {
        //We format the value and we do show two decimals
        formattedValue = number_format_js(valueBalance, 2, ',', ' ');
    } else if (lastFourChararcters.substr(-1) === '0') {
        //We format the value and we do show three decimals
        formattedValue = number_format_js(valueBalance, 3, ',', ' ');
    } else {
        //We save the formatted value that returns the method that takes away the approximation
        formattedValue = numberFormatNoApproximationJs(valueBalance);
    }
    //We return the value with or without decimals
    return formattedValue;
}

function reiniciar_progress_bar() {
    $('.progress-bar').css('width', '0%').attr('aria-valuenow', 0);
    $('.progress').hide();
}

function elementHide(index) {
    $('.' + index).hide();
}

function elementShow(index) {
    $('.' + index).show();
}

function anadir_html_mensaje(tipo = '', nombre_operador = '', fecha = '', estado = '', data_id_mensaje = '', mensaje = '', Act = 'no', Bd = 'no', nombre_contacto = '', id_conversacion = '') {

    if (Bd == 'si') {
        if (tipo == 'recibido') {
            var mensaje_base = $(".js-container-sent");
            $('.js-container-message_' + id_conversacion).prepend(mensaje_base.html());
        } else if (tipo == 'enviado') {

            var mensaje_base = $(".js-container-reply");
            $('.js-container-message_' + id_conversacion).prepend(mensaje_base.html());
        }
    } else {
        if (tipo == 'recibido') {
            var mensaje_base = $(".js-container-sent");
            $('.js-container-message_' + id_conversacion).append(mensaje_base.html());
        } else if (tipo == 'enviado') {

            var mensaje_base = $(".js-container-reply");
            $('.js-container-message_' + id_conversacion).append(mensaje_base.html());
        }
    }

    actualizar_datos_mensaje('.js-container-message_' + id_conversacion + ' .js-nuevo', nombre_operador, fecha, estado, data_id_mensaje, mensaje, Act, null, Bd, tipo, nombre_contacto, id_conversacion);
}

function actualizar_datos_mensaje(index, nombre_operador = '', fecha, estado = '', data_id_mensaje = '', mensaje = '', Act = 'no', actualizado = 'no', Bd = 'no', tipo = '', nombre_contacto = '', id_conversacion = '') {


    var index = $(index);
    var fecha_formateada, fecha_hora;

    if (fecha == null) {
        fecha = '';
        fecha_formateada = '';
    } else {
        if (Bd == 'si') {
            fecha_formateada = fecha.substr(0, 19).replace('T', ' ');
        } else {
            fecha_formateada = fecha.substr(0, 19).replace('T', ' ');
        }
    }

    if (nombre_contacto == null) {
        nombre_contacto = id_phone;
    }

    if (tipo == 'recibido') {

        if (nombre_operador != null) {
            $(index).attr('title', nombre_contacto + ' (' + fecha_formateada + ')');
        } else {
            $(index).attr('title', id_phone + ' (' + fecha_formateada + ')');
        }
    } else if (tipo == 'enviado') {

        $(index).attr('title', nombre_operador + ' (' + fecha_formateada + ')');
    }

    $(index).attr('data_time', fecha).attr('estado', estado).attr('data_id_mensaje', data_id_mensaje);

    if (mensaje.substr(mensaje.length - 3, mensaje.length) == "ogg" || mensaje.substr(mensaje.length - 4, mensaje.length) == "opus" || mensaje.substr(mensaje.length - 3, mensaje.length) == "mp3" || mensaje.substr(mensaje.length - 3, mensaje.length) == "wav") {

        $(index).find('p').html(` <audio class="form-control-plaintext" controls>
                                                                                                        <source src="${mensaje}" type="audio/ogg">
                                                                                                    </audio> `);
    } else if (mensaje.substr(mensaje.length - 3, mensaje.length) == "png" || mensaje.substr(mensaje.length - 3, mensaje.length) == 'jpg' || mensaje.substr(mensaje.length - 4, mensaje.length) == 'jpeg' || mensaje.substr(mensaje.length - 3, mensaje.length) == 'gif' || mensaje.substr(mensaje.length - 3, mensaje.length) == 'tiff' || mensaje.substr(mensaje.length - 3, mensaje.length) == 'tif' || mensaje.substr(mensaje.length - 3, mensaje.length) == 'raw' || mensaje.substr(mensaje.length - 3, mensaje.length) == 'bmp' || mensaje.substr(mensaje.length - 3, mensaje.length) == 'psd') {
        $(index).find('p').html(`<a target="_blank" href="${mensaje}"><img style="height: 300px;width: 500px;border-radius: 0" class="img img-thumbnail" src="${mensaje}" alt=""></a>`);
    } else if (mensaje.substr(mensaje.length - 3, mensaje.length) == "mp4" || mensaje.substr(mensaje.length - 3, mensaje.length) == 'avi' || mensaje.substr(mensaje.length - 3, mensaje.length) == 'mov' || mensaje.substr(mensaje.length - 3, mensaje.length) == 'ogg') {

        $(index).find('p').html(`<video controls style="height: 300px; width: 500px; border-radius: 0" class="img img-thumbnail"><source src="${mensaje}" type="video/${mensaje.substr(mensaje.length - 3, mensaje.length)}"></video>`);

    } else if (mensaje.substr(mensaje.length - 3, mensaje.length) === "pdf") {

        $(index).find('p').html(`<img type_document = "pdf" modal_document= "${mensaje}" style="height: 100px;width: 100px;border-radius: 0" class="img rounded puntero" src="${FULL_WEB_URL}assets/img/pdf.webp" alt="PDF">`);
    } else if (mensaje.substr(mensaje.length - 4, mensaje.length) === "docx" || mensaje.substr(mensaje.length - 3, mensaje.length) === "doc") {
        $(index).find('p').html(`<a target="_blank" href="https://view.officeapps.live.com/op/view.aspx?src=${mensaje}"><img style="height: 100px;width: 100px;border-radius: 0" class="img rounded" src="https://miracomosehace.com/wp-content/uploads/2020/07/app-word-logo.jpg" alt="WORD"></a>`);
    } else if (mensaje.substr(mensaje.length - 4, mensaje.length) === "pptx") {
        $(index).find('p').html(`<a target="_blank" href="https://view.officeapps.live.com/op/view.aspx?src=${mensaje}"><img style="height: 100px;width: 100px;border-radius: 0" class="img rounded" src="https://e7.pngegg.com/pngimages/980/706/png-clipart-microsoft-powerpoint-presentation-slide-show-microsoft-office-365-ppt-text-rectangle.png" alt="POWERT POINT"></a>`);
    } else if (mensaje.substr(mensaje.length - 4, mensaje.length) === "xlsx" || mensaje.substr(mensaje.length - 3, mensaje.length) === "csv") {
        $(index).find('p').html(`<a target="_blank" href="https://view.officeapps.live.com/op/view.aspx?src=${mensaje}"><img style="height: 100px;width: 100px;border-radius: 0" class="img rounded" src="https://iceicapacitacion.com/wp-content/uploads/2020/08/Excel-Formula-1.jpg" alt="EXCEL"></a>`);
    } else if (mensaje.substr(0, 9) === 'Location:') {
        var data_maps = mensaje.substr(9),
            array_data_maps = data_maps.split("/");
        $(index).find('p').html(`<a target="_blank" href="https://www.google.com/maps?q=${array_data_maps[0]},${array_data_maps[1]}&z=17&hl=es"><img style="height: 200px;width: 170px;border-radius: 0" class="img " src="https://hipertextual.com/files/2020/04/hipertextual-mas-facil-durante-cuarentena-google-maps-muestra-que-restaurantes-envian-domicilio-2020815281.jpg" alt="UBICACIÓN"></a>`);

    } else if (mensaje.substr(0, 5) == "https" || mensaje.substr(0, 3) == "www" || mensaje.substr(0, 4) == "http") {
        var nuevo_mensaje = validate_text_long(mensaje, 20);
        $(index).find('p').html(` <a style="color: #4dc71f" target="_blank" href="${mensaje}">${nuevo_mensaje}</a>`);
    } else {

        //Obtenemos los dígitos que corresponden a las horas (Ej: 15:10)
        var date_hour = fecha_formateada.substr(10, 6);

        // ** Proceso para mostrar la hora de la fecha correctamente **

        //Obtenemos los últimos 3 caracteres de la hora (Ej: :10)
        let last_digits = date_hour.substr(-3);

        //Obtenemos los dígitos que corresponde a la hora, es decir, lo que está antes de los dos puntos(:) (Ej: 15)  le restamos una hora
        //Nota: Este problema fue reportado por soporte, así que se soluciona de esta manera (por el  momento), restando una hora
        let first_digits = date_hour.replace(last_digits, '');

        //Unificamos las dos partes para conservar la fecha correcta sin una hora de más (pues ese era el problema) (Ej: 14:10)
        date_hour = first_digits + last_digits;

        var ultimo = date_hour.charAt(date_hour.length - 1);

        if (ultimo == ':') {
            date_hour = date_hour.substr(0, 5);
        }
        $(index).attr('title', nombre_operador + ' (' + fecha_formateada.substr(0, 10) + ')');
        $(index).find('p').html(mensaje + ' <span style="position:relative;display:block;right:0%;text-align: end"><sub  style="font-weight: bold; font-size: 10px;">' + date_hour + '</sub></span>');
    }

    if (Act == 'si') {
        $(index).addClass('Act');
    }
    if (actualizado == 'si') {
        $(index).removeClass('Act');
        $(index).find('.leido').show();
    }
    if (Bd == 'si') {

        $(index).find('.leido').show();
    }

    $('[modal_document]').off('click');
    $('[modal_document]').attr('title', nombre_operador + ' (' + fecha_formateada.substr(0, 10) + ')');

    $('[modal_document]').click(function () {
        $('#modal-document').modal('show');
        var url_document = $(this).attr('modal_document');
        var type_document = $(this).attr('type_document');

        $('#container_document').attr('src', url_document);
        $('#container_document').attr('type_document', 'aplication/' + type_document);
    })


    $(index).removeClass('js-nuevo');
    bajar_scroll();


}

function notificacion_sound() {

    audio = new Audio(FULL_WEB_URL + 'assets/audio/iphone-notificacion.mp3');
    audio.play();
}

function bajar_scroll() {

    $(".messages").animate({
        scrollTop: $('.messages').prop("scrollHeight")
    }, 0.0001);
}

function notificacion_push(title = 'Notificacion', text = 'Accion realizada', time = 3000) {
    Push.create(title, {
        body: text,
        icon: "https://lowcarbon.city/wp-content/uploads/2018/03/company-icon-300.png"
    });
}

/**
 * @Description: Método que valida si nuna fecha es mayor que otra
 */
function validateIsGreaterDate(elementInitialDate, elementFinalDate, initial_date, final_date) {

    if (Date.parse(initial_date) > Date.parse(final_date)) {
        validationErrorInput('La "Fecha Inicial" debe se menor que la "Fecha Final"', elementInitialDate);
        validationErrorInput('', elementFinalDate);
    } else {
        $('.' + elementInitialDate).removeClass('border border-danger');
        $('.' + elementFinalDate).removeClass('border border-danger');
    }
}

/**
 * @Description: Método que ordena un array de forma descendente
 */
function sortArray(a, b) {

    a = a.tiempo;
    b = b.tiempo;

    if (a > b)
        return 1;

    if (a < b)
        return -1;

    return 0;
}

/**
 * @Description: Método que suma o resta días a una fecha
 */
function modifyDays(fecha, numDias) {
    fecha.setDate(fecha.getDate() + numDias);
    return fecha;
}

/**
 * Este cdigo hace funcionar correctamente las paginaciones
 */
$('document').ready(function () {

    $(".jsPreviousPageButon").click(function () {
        redirectToPage('previous');
    });

    $(".jsNextPageButon").click(function () {
        redirectToPage('next');
    });

    $(".jsLastPageButon").click(function () {
        //Se obtiene el numero de la ultima pagina
        lastPageNumber = $(this).attr("data-pagination-lastpage");

        redirectToPage('last', lastPageNumber);
    });

    $(".jsFirstPageButon").click(function () {
        redirectToPage('first');
    });

});

/**
 * @Description: Para ejecutar métodos con 'convinación de teclas' (Ej: Ctrl+O)
 */

/*var map = {48: false, 49: false};
$(document).keydown(function(e) {
    if (e.keyCode in map) {
        map[e.keyCode] = true;
        if (map[48] && map[49]) {


        }
    }
}).keyup(function(e) {
    if (e.keyCode in map) {
        map[e.keyCode] = false;
    }
});*/


function validate_text_long(texto, long) {
    if (texto.length > long)
        var nuevo_texto = texto.substr(0, long);
    else
        nuevo_texto = texto;

    return nuevo_texto + '......';
}


$('#record').click(function () {
    validationMessageSuccess('Grabando....', 2500);
    $('#stop').removeClass('elementHide');
    $('#stop_1').removeClass('elementHide');
    $(this).addClass('elementHide');
    // var stop = $('#stop');
    // var play = $('#play');
    // var save = $('#save');
});

$('#stop, #stop_1').click(function () {
    $('#record').removeClass('elementHide');
    $('#stop_1').addClass('elementHide');
    $('#stop').addClass('elementHide');
});


function value_multi_select(element) {
    return element.selectpicker('val');
}

function attr_multi_select(element) {
    return element.selectpicker('attr');
}

function obtener_ciudades(element_select_state, element_select_ciudad, id_ciudad_select = null) {
    var id_estado = value_multi_select(element_select_state);
    $.ajax({
        type: 'POST',
        url: FULL_WEB_URL + 'ajax/admin/ciudadCrud.php',
        data: ({
            Action: 'SEARCH_CIUDADES',
            id_estado: id_estado
        }),
        success: function (response) {

            //Parseamos a formato JSON la respuesta
            var json_obj = $.parseJSON(response);

            //Validamos si el estado de la sesión es '1', para saber si se inició alguna sesión
            if (json_obj.state == 500) {
                return false;
            } else {
                // vacio el contenido del contenedor
                element_select_ciudad.html('');

                // recorro el array con los productos filtrados
                var ciudades = json_obj.data;
                var htmlTags = $('#select_oculto');
                for (const campo in ciudades) {
                    // agrego o clono el html en el contenedor vacio
                    element_select_ciudad.append(htmlTags.html());
                    var index = element_select_ciudad.find('.nuevo');
                    $(index).val(ciudades[campo]['id_ciudad']);
                    $(index).text(ciudades[campo]['nombre']);
                    $(index).removeClass('nuevo');
                }

                if (id_ciudad_select !== null) {
                    element_select_ciudad.val(id_ciudad_select);
                }

                element_select_ciudad.selectpicker('refresh');


            }

        },
        error: function (xhr, status) {

            return false;
        },
    });
}

function obtener_servicios(element_select_state, element_select_ciudad, id_ciudad_select = null, type = "normal") {
    var id_estado = element_select_state.val()
    $.ajax({
        type: 'POST',
        url: FULL_WEB_URL + 'ajax/admin/servicioCrud.php',
        data: ({
            Action: 'SEARCH_PROFESIONALES',
            data_id: id_estado
        }),
        success: function (response) {

            //Parseamos a formato JSON la respuesta
            var json_obj = $.parseJSON(response);

            //Validamos si el estado de la sesión es '1', para saber si se inició alguna sesión
            if (json_obj.state == 500) {
                return false;
            } else {
                // vacio el contenido del contenedor
                element_select_ciudad.html('');

                // recorro el array con los productos filtrados
                var ciudades = json_obj.data;
                var htmlTags = $('#select_oculto');
                for (const campo in ciudades) {
                    // agrego o clono el html en el contenedor vacio
                    element_select_ciudad.append(htmlTags.html());
                    var index = element_select_ciudad.find('.nuevo');
                    $(index).val(ciudades[campo]['id_profesional']);
                    $(index).text(ciudades[campo]['nombre_profesional'] + ' ' + ciudades[campo]['apellidos'] + ' ' + ciudades[campo]['tipo_documento'] + ': ' + ciudades[campo]['num_doc']);
                    $(index).removeClass('nuevo');
                }


                if (type === "multiple") {
                    element_select_ciudad.selectpicker('render');
                    element_select_ciudad.selectpicker('refresh');

                    if (id_ciudad_select !== null) {
                        var array = id_ciudad_select.split(",");
                        element_select_ciudad.selectpicker('val', array);
                    }
                }

                if (id_ciudad_select !== null && type === "normal") {
                    element_select_ciudad.val(id_ciudad_select);
                }

            }

        },
        error: function (xhr, status) {

            return false;
        },
    });
}

$('li.li_padre').click(function () {
    var index = $(this).find('.ul_hijo');

    // valido para cerrar todos los li.padre

    if (index.attr('data_visible') === 'hide') {
        index.slideDown("slow");
        index.attr('data_visible', 'show')
    } else {
        index.attr('data_visible', 'hide');
        index.slideUp("slow");
    }
    //
    // $('.li_padre').each(function (){
    //     var elemento  = $(this).find('.ul_hijo');
    //
    //     if (elemento.attr('data_visible') === 'show'){
    //         elemento.attr('data_visible','hide')
    //         index.slideUp( "slow" );
    //     }
    // })

})

$('.multi_Select').selectpicker({
        deselectAllText: 'Deseleccionar todos',
        selectAllText: 'Seleccionar todos',
        multipleSeparator: ', ',
        noneSelectedText: 'Seleccionar',
        noneResultsText: 'Sin seleccion',
        styleBase: 'form-control'

    }
);


// $('.etiquetas_td').each(function (){
//     var index = $(this);
//     var id_etiqueta = index.attr('data_id_etiquetas');
//     var array_etiqueta = index.text().split(',');
//     var array_id_etiqueta = id_etiqueta.split(',');
//
//     index.html('');
//     array_etiqueta.each(function (){
//         console.log(this)
//     })
// })

var max_elements=5;
$('#JSFile').fileinput({
    theme: 'fas',
    language: 'es',
    maxFileSize: 1000, // tamaño máximo de archivo en KB
    maxFilesNum: max_elements, // número máximo de archivos
    showUpload: false,
    showCaption: false,
    showPreview: true,
    showRemove: true,
    showCancel: true,
    showUploadedThumbs: false,
    allowedFileExtensions: ['jpg', 'jpeg', 'png', 'gif'], // extensiones de archivo permitidas
    browseClass: "btn btn-sm btn-success",
    browseLabel: 'Seleccionar archivo',
    browseIcon: '<i class="fa fa-audio-description"></i>',
    removeIcon: '<i class="fa fa-close"></i>',
    removeLabel: 'Quitar archivo',
    removeClass: "btn btn-sm btn-danger",
    previewFileIconSettings: { // configura los íconos para las extensiones de archivo
        'doc': '<i class="fas fa-file-word text-primary"></i>',
        'xls': '<i class="fas fa-file-excel text-success"></i>',
        'ppt': '<i class="fas fa-file-powerpoint text-danger"></i>',
        'pdf': '<i class="fas fa-file-pdf text-danger"></i>',
        'zip': '<i class="fas fa-file-archive text-muted"></i>',
        'htm': '<i class="fas fa-file-code text-info"></i>',
        'txt': '<i class="fas fa-file-alt text-info"></i>',
        'mov': '<i class="fas fa-file-video text-warning"></i>',
        'mp3': '<i class="fas fa-file-audio text-warning"></i>',
        'jpg': '<i class="fas fa-file-image text-danger"></i>',
        'gif': '<i class="fas fa-file-image text-muted"></i>',
        'png': '<i class="fas fa-file-image text-primary"></i>'
    },
    initialPreview: [], // Lista de URLs para previsualización inicial (vacío para nuevos archivos)
    initialPreviewAsData: true,
    initialPreviewConfig: [
        {caption: "Imagen 1", url: "http://example.com/image1.jpg", key: 1, extra: {id: 1}},
        {caption: "Imagen 2", url: "http://example.com/image2.jpg", key: 2, extra: {id: 2}}
    ],
    overwriteInitial: false, // Permitir agregar más archivos en lugar de sobrescribir
    initialCaption: "Seleccionar archivos", // Título inicial
    fileActionSettings: {
        showZoom: true, // Mostrar el botón de zoom
        showDrag: true, // Mostrar el botón de arrastrar para reordenar
        showRemove: true, // Mostrar el botón de eliminar
        showUpload: false // Ocultar el botón de cargar
    },
    // Habilitar reordenar los archivos
    sortable: true,
    // Habilitar marcar archivos como principales
    showUploadedThumbs: true,
    // Configurar el estilo del botón principal
    fileActionSettings: {
        showDrag: true,
        dragIcon: '<i class="fas fa-arrows-alt"></i>',
        indicatorNew: '<i class="fas fa-star text-warning"></i>', // Icono para marcar como nuevo/principal
        indicatorSuccess: '<i class="fas fa-check-circle text-success"></i>', // Icono para marcado exitoso
        indicatorError: '<i class="fas fa-exclamation-circle text-danger"></i>', // Icono para errores
        indicatorLoading: '<i class="fas fa-hourglass text-muted"></i>', // Icono para cargando
    }
});



function upload_file(data_id = null, tipo_asignacion, redireccion) {
    var imagenes = $('#JSFile')[0].files;

    var formData = new FormData();
    for (const imagen in imagenes) {
        formData.append('archivo' + imagen, imagenes[imagen]);
    }

    formData.append('data_id', data_id);
    formData.append('archivo', imagenes);
    formData.append('Action', 'UPLOAD_FILES');
    formData.append('tipo_asignacion', tipo_asignacion);

    $('.progress').removeClass('elementHide');

    $.ajax({
        // funcion para la progress bar
        xhr: function () {
            var xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener("progress", function (evt) {
                if (evt.lengthComputable) {
                    var percentComplete = evt.loaded / evt.total;
                    percentComplete = parseInt(percentComplete * 100);
                    $('.progress-bar').css('width', percentComplete + "%");
                    $('.progress-bar').html(percentComplete + "%");
                    if (percentComplete === 100) {

                    }
                }
            }, false);
            return xhr;
        },
        // aqui termina la funcion de progress bar
        type: 'POST',
        url: FULL_WEB_URL + 'ajax/admin/files_upload.php',
        cache: false,
        contentType: false,
        processData: false,
        data: formData,
        success: function (response) {

            //Parseamos a formato JSON la respuesta
            // var json_obj = $.parseJSON(response);
            var json_obj = '';
            if (json_obj.return == 'All_Mp3') {


            } else if (json_obj.return == 'archivo_no_movido') {

            } else
                //Validamos si el estado de la sesión es '1', para saber si se inició alguna sesión
            if (json_obj.return == false) {


            } else {
                validationMessageSuccess('Subida exitosa!');
                $(location).attr('href', FULL_WEB_URL + redireccion + '/');
            }
        },
        error: function (data) {

        }
    });
}

$('.btn-close-modal').click(function () {
    $('#UploadFile').modal('hide');
})
$('.btn-show-modal').click(function () {
    $('#UploadFile').modal('show');
})

// este evento devuelve un array con los id de cada uno
function recorrer_checbox(element) {
    var array_final = [],
        contador = 0;
    $(element).each(function () {
        var index = $(this),
            id = index.parents('tr').attr('data-id-checbox'),
            carnet = index.parents('tr').find('.js-carnet').val(),
            pago = index.parents('tr').find('.js-pago').val();
        if (index.is(":checked")) {
            array_final.push({
                'id': id,
                'carnet': carnet,
                'pago': pago
            });
            contador++;
        }

    });
    if (contador !== 0)
        return array_final;
    else
        return false;
    // if (checbox.attr('checked'))
}


// $('#sidenav-main').removeClass('navbar-expand-md');
// $('#sidenav-main').addClass('navbar-collapse-md');


function copy_html(html, destino) {
    $(destino).append(html);
}

function delete_html(detino) {
    $(detino).remove();
}


$('.modal_logo').click(function () {
    $("#modal_logo").modal("show")
})
$('.btn_subir_logo').click(function () {
    upload_file(null, 'logo', '');

})

function formatNumber(nStr) {
    return nStr;
}

asignar_funciones_mail();

function asignar_funciones_mail() {
    $('.modal_mail').off("click");
    $('.send_mail').off("click");
    $('.js-template-mail').off("change");
    $('[type_send_event]').off("click");


    $('.send_mail').click(function () {
        var index_btn = $(this);
        $("#modal_mails").modal("hide");
        //se realiza un swal de confirmacion
        Swal.fire({
            title: 'Desea enviar el correo electrónico?',
            text: 'Esta accion no se puede revertir',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Si, enviar'
        }).then((result) => {
            if (result.value) {
                //aqui se agrea la accion de eliminar eusuario
                //se obtiene el data-typeIp que es el id del user que vamos a eliminar
                //hay un error al obtener el atributo por eso se comenta y se deja estatico


                var type_mail = $(".type_mail_send").val();
                var template_mail = $(".js-template-mail").val();
                if (type_mail === "cliente") {
                    let criterio = $('.JScriterio').val();
                    let id_tipo_cliente = $('.js-filtro_1').val();
                    let pago = $('.js-filtro_2').val();
                    let vigencia = $('.js-filtro_3').val();

                    var data_general = {
                        criterio: criterio,
                        id_tipo_cliente: id_tipo_cliente,
                        pago: pago,
                        template_mail: template_mail,
                        type_mail: type_mail,
                        vigencia: vigencia
                    }
                } else if (type_mail === "evento") {
                    var type_send_event = index_btn.attr("attr_adicional");
                    if (type_send_event === "all") {
                        var id_evento = $('.data-id').val();
                        var id_cliente = null;
                    } else {
                        var id_evento = $('.data-id').val();
                        var id_cliente = type_send_event;
                    }
                    var data_general = {
                        template_mail: template_mail,
                        type_mail: type_mail,
                        id_evento: id_evento,
                        id_cliente: id_cliente
                    }
                }

                validationMessageWarning('Enviando mensajes por favor espere.....');
                $.ajax({
                    type: 'POST',
                    url: FULL_WEB_URL + 'scripts/mails/sendMailClient.php',
                    data: (data_general),
                    success: function (response) {

                        //Parseamos a formato JSON la respuesta
                        var json_obj = $.parseJSON(response);

                        //Validamos si el estado de la sesión es '1', para saber si se inició alguna sesión
                        if (json_obj === true) {
                            validationMessageSuccess('Mensajes enviados!');
                        } else {
                            validationMessageError(json_obj);
                        }
                    },
                    error: function (xhr, status) {
                        showMessageErrors(status);
                        return false;
                    },
                });
            }
        })
    })

    $("[type_send_event]").click(function () {
        $(".send_mail").attr("attr_adicional", $(this).attr("type_send_event"))
    })
    $(".modal_mail").click(function () {
        $("#modal_mails").modal("show");
        mostrarHtmlTemplateMail();
    })
    $(".js-template-mail").change(function () {
        mostrarHtmlTemplateMail();
    })

}

function mostrarHtmlTemplateMail() {
    var index = $(".js-template-mail");
    var index_option = index.find("option:selected");
    var html = index_option.attr("attr_html");
    $(".container-html").html(html)

}


function prefijos_list() {
    $(".prefijos_list").each(function () {
        var index = $(this);

        // Verifica si el elemento ya ha sido procesado
        if (index.attr('data-processed') === 'true') {
            return; // Si ya fue procesado, salimos de la función para este elemento
        }

        var array_list = index.text().split(",");
        var fragment = $(document.createDocumentFragment());

        array_list.forEach(value => {
            var badge = $('<span>').addClass('badge badge-success').text(value);
            fragment.append(badge).append('&nbsp;');
        });

        index.html(fragment);

        // Marca el elemento como procesado
        index.attr('data-processed', 'true');
    });
}


prefijos_list();


// Obtén la fecha actual
var fechaActual = new Date();
var fechaFinal = new Date();

// La fecha final es el día de mañana
fechaFinal.setDate(fechaFinal.getDate() + 1);

// Formatea las fechas en formato YYYY-MM-DD
var fechaHoy = fechaActual.toISOString().split('T')[0];
var fechaManana = fechaFinal.toISOString().split('T')[0];

// Establece las fechas en los elementos input
// $("input[type='date'].JScriterio").val(fechaHoy);
$("input[type='date'].js-fecha").val(fechaHoy);
// $("input[type='date'].JScriterio_2").val(fechaManana);
document.addEventListener("DOMContentLoaded", function() {
    const toggleSwitches = document.querySelectorAll(".toggle-switch");

    toggleSwitches.forEach(toggleSwitch => {
        const switchLabel = toggleSwitch.nextElementSibling;
        const switchText = switchLabel.querySelector(".switch-text");

        function updateSwitch() {
            const onColor = toggleSwitch.getAttribute("data-on-color");
            const offColor = toggleSwitch.getAttribute("data-off-color");
            const onText = toggleSwitch.getAttribute("data-on-text");
            const offText = toggleSwitch.getAttribute("data-off-text");

            switchLabel.style.setProperty('--on-color', onColor);
            switchLabel.style.setProperty('--off-color', offColor);

            if (toggleSwitch.checked) {
                switchText.textContent = onText;
                switchLabel.style.backgroundColor = onColor; // Set background color for checked state
            } else {
                switchText.textContent = offText;
                switchLabel.style.backgroundColor = offColor; // Set background color for unchecked state
            }
        }

        toggleSwitch.addEventListener("change", updateSwitch);
        updateSwitch(); // Initial call to set colors and text on page load
    });
});






