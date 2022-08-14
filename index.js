// Execute startup functions
window.onload = function () {
    getSession();
}

async function fillBingoCard() {

    let bingoCardSize = 3;
    let table = document.getElementById('bingoTable');

    // fetch a random products
    let products = await getRandomProduct(bingoCardSize * bingoCardSize);
    let counter = 0;

    for (let column = 0; column < bingoCardSize; column++) {
        // add a column to insert cells into
        let rowObject = table.insertRow();
        for (let row = 0; row < bingoCardSize; row++) {
            // add a new cell to the column and reference its object
            let cell = rowObject.insertCell();

            // insert information into cell
            cell.innerHTML = "<a href='api/product.php?id=" + products[counter].id + "'>" + "<img src='" + products[counter].product_image + "'></a>";
            counter += 1;
        }
    }
}

async function getRandomProduct(nrRand) {
    // TODO: add check for duplicates
    let api_url = 'api/product.php?random=' + nrRand;
    const response = await fetch(api_url);
    // If we do not get the expected response
    if (response.status != 200) {
        console.error("Could not fetch random product");
        return;
    }
    const data = await response.json();
    return data;
}

async function startSession() {
    // check if no session is present
    if (document.cookie.indexOf("sessionID=") > -1) {
        console.error("There is already a session running");
        getSession();
        return;
    }

    let api_url = 'api/session.php?start';
    const response = await fetch(api_url);
    if (response.status != 200) {
        console.error("Session could not be started");
    } else {
        const data = await response.json();
        console.log(data);

        let sessionIDname = document.getElementById('sessionID');
        sessionIDname.innerHTML = data.SessionID;
        sessionIDname.style.display = "block";
        console.log("Session started");
    }

    // fill the bingo card
    fillBingoCard();
}

function getSession() {
    let sessionIDname = document.getElementById('sessionID');
    if (document.cookie.indexOf("sessionID=") > -1) {
        let cookies = document.cookie;
        sessionIDname.innerHTML = cookies.substring(cookies.indexOf("sessionID=") + 10, cookies.indexOf("sessionID=") + 42);
        sessionIDname.style.display = "block";
        console.log("Session running");
    } else {
        sessionIDname.innerHTML = "";
        sessionIDname.style.display = "none";
        console.log("No session running");
    }
}

function clearCookies() {
    document.cookie = "sessionID=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
    getSession();
}