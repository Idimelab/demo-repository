function redirectToPage(pageType, lastPage = 1) {

    //Se obtiene la url completa
    let completeUrl = Url.getFullRaw();

    //Se carga el numero de la pagina
    var pageNumber = Url.getUrlGetParam('page');

    //Se setea en 1 si no existe la pagina
    if (isNaN(pageNumber) || pageNumber === undefined || pageNumber === false)
        pageNumber = 1;

    //Se elimina el parametro pagina de la url
    let completeUrlWitouthPage = Url.getUrlDeleteOneParameter(completeUrl, 'page');

    //Se cargan los numeros de pagina siguiente y anterior
    let nextPage = parseInt(pageNumber) + parseInt(1);
    let previousPage = parseInt(pageNumber) - parseInt(1);

    //Declaramos variable que contendrá el número de la paginación
    var pageNumberCalculated = '';

    //Se selecciona uno de los numeros de pagina dependiendo del tipo de peticion
    if (pageType === 'next')
        pageNumberCalculated = nextPage;

    else if (pageType === 'previous')
        pageNumberCalculated = previousPage;

    else if (pageType === 'first')
        pageNumberCalculated = 1;

    else if (pageType === 'last')
        pageNumberCalculated = lastPage;

    else
        pageNumberCalculated = 1;

    //Si por alguna razon falla el calculo de la pagina, se pone en 1
    if (pageNumberCalculated <= 0)
        pageNumberCalculated = 1;

    //Se crea la url final
    let finalUrl = Url.addParam(completeUrlWitouthPage, 'page=' + pageNumberCalculated);

    //Se hace la redireccion
    Url.redirect(finalUrl);
}