window.addEventListener('load', function() {
    // buscaRegistro();
});

function samplesCallApi(){
    // fact in 2022 - Over 80% of websites are now written in PHP

    // fetch api substitui a velha api de ajax
    // 1. How to get data with Fetch
    fetch('https://phpenthusiast.com/dummy-api/')
        .then((res) => res.json())
        .then((data) => console.log(data))
        .catch((error) => console.log(error));

    // 2 - Posting data with the Fetch
    fetch('https://phpenthusiast.com/dummy-api/', {
        method : 'post',
        mode:    'cors',
        headers: {
            'Content-Type': 'application/json',  // sent request
            'Accept':       'application/json'   // expected data sent back
        },
        body: JSON.stringify({min: 1, max: 100})
    })
        .then((res) => res.json())
        .then((data) => console.log(data))
        .catch((error) => console.log(error));

}

function callApi() {
    let url1 = 'https://registrocivil.org.br:8443/api/usuarios/usuario/';
    let url2 = 'https://factor-erp-api.herokuapp.com/';
    let urls = 'https://phpenthusiast.com/dummy-api/';
    let url_local = 'http://127.0.0.1/factor-erp-api/api/api.php';


    let url = 'https://factor-erp-api.herokuapp.com/api.php/sistema';

    const headers = {
        'Content-Type': 'application/json',
        'apikey': '7CojClx9l62Mz6SJcEHFWZfK2NtSHXgI',
        'Authorization': `Bearer eyJ0eXAiOi`
    };

    // QUANDO PASSA HEADERS ESTA DANDO ERRO DE CORS!!!!!
    fetch(url, {
            method: 'GET'
        })
        .then((res) => res.json())
        .then((data) => console.log(data))
        .catch((error) => console.log(error));
}

