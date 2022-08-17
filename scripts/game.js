// get session when first loading page
getSession();

// attach event handlers to buttons
document.getElementById('homeButton').addEventListener('click', () => {
    window.location.href = "index.html";
})
document.getElementById('hintButton').addEventListener('click', checkHint);
document.getElementById('checkProductForm').addEventListener('submit', checkProduct);

async function getSession() {
    // call the api
    let api_url = 'api/session.php?get';
    let response = await fetch(api_url);
    let data = await response.json();

    // check if we have a successful response
    if (!response.ok) {
        console.error(response.status, response.statusText, data.message);

        let errorBox = document.getElementById('errorMessage');
        errorBox.innerHTML = data.message;
        errorBox.style.display = "block";

        return;
    }

    // get page elements and fill them with session information
    document.getElementById('teamName').innerHTML = data.sessionName;
    document.getElementById('teamPin').innerHTML = data.sessionPin;

    // check if the session is still running
    if (!data.sessionRunning) {
        // TODO: disable bingo board
        return;
    }

    // calculate the remaining time
    let finishTime = 1 * data.sessionStartTime + (15 * 60);
    let currentTime = (Math.floor(Date.now() / 1000));
    let remainingTime = finishTime - currentTime;
    document.getElementById('teamTime').setAttribute('remaining', remainingTime);
    updateTime();

    // enable timer countdown
    setInterval(updateTime, 1000);

    // get the hinted and found products
    let hintedProducts = data.sessionHints + "";
    let foundProducts = data.sessionFoundProducts + "";

    // get the bingo board
    let table = document.getElementById('bingoTable');

    // get the products
    api_url = 'api/product.php?id=' + data.sessionProducts;
    response = await fetch(api_url);
    let products = await response.json();

    for (let r = 0; r < 5; r++) {
        let row = table.insertRow();
        for (let c = 0; c < 5; c++) {
            let cell = row.insertCell();
            let product = products[(r * 5) + c];

            // add all information to the bingo cell
            cell.setAttribute('productId', product.productID);
            cell.setAttribute('productName', product.productName);
            cell.innerHTML = "<img src='" + product.productPicture + "'>";

            // check if product is already found (don't bind an eventlistener otherwise)
            if (foundProducts.includes(product.productID)) {
                cell.setAttribute('found', true);
                continue;
            } else {

                // register the callback when cell is clicked (if product is not found yet)
                cell.onclick = async () => {
                    // check if product is already found (no action needed)
                    if (cell.getAttribute('found')) {
                        return false;
                    }

                    document.getElementById('productName').innerHTML = product.productName;
                    document.getElementById('productImage').src = product.productPicture;
                    document.getElementById('checkProduct-id').value = product.productID;
                    document.getElementById('hintButton').setAttribute('productId', product.productID);
                    document.getElementById('productHint').style.display = "none";

                    // change the modal to display the hinted location
                    if (cell.getAttribute('hinted')) {
                        let location = await getProductLocation();
                        document.getElementById('productLocation').innerHTML = location;
                        document.getElementById('productHint').style.display = "block";
                    } else {
                        document.getElementById('productHint').style.display = "none";
                    }

                    showModal(true);
                }

                // check if product is already hinted
                if (hintedProducts.includes(product.productID)) {
                    cell.setAttribute('hinted', true);
                }

            }
        }
    }
}

async function checkProduct(event) {
    // prevent submitting and redirecting page
    event.preventDefault();

    // call the api
    let productId = document.getElementById('checkProduct-id').value;
    let productEan = document.getElementById('checkProduct-ean').value;
    let api_url = 'api/product.php?checkproduct=' + productId + "&ean=" + productEan;
    let response = await fetch(api_url);
    let data = await response.json();

    // check if we have a successful response
    if (!response.ok) {
        console.error(response.status, response.statusText, data.message);

        // TODO: make EAN error box
        let errorBox = document.getElementById('errorMessage');
        errorBox.innerHTML = data.message;
        errorBox.style.display = "block";

        return;
    }

    // reset the ean field
    document.getElementById('checkProduct-ean').value = "";

    // update the cell attributes to show that the product has been hinted
    document.querySelector('[productid="' + productId + '"]').setAttribute('found', true);
}

async function checkHint(event) {
    // prevent submitting and redirecting page
    event.preventDefault();

    let location = await getProductLocation();
    document.getElementById('productLocation').innerHTML = location;
    document.getElementById('productHint').style.display = "block";
}

async function getProductLocation() {
    // call the api
    let productId = document.getElementById('hintButton').getAttribute('productId');
    let api_url = 'api/product.php?hintproduct=' + productId;
    let response = await fetch(api_url);
    let data = await response.json();

    // check if we have a successful response
    if (!response.ok) {
        console.error(response.status, response.statusText, data.message);

        let errorBox = document.getElementById('errorMessage');
        errorBox.innerHTML = data.message;
        errorBox.style.display = "block";

        return;
    }

    // get the product location
    let location = data.productLocation;

    // update the cell attributes to show that the product has been hinted
    document.querySelector('[productid="' + productId + '"]').setAttribute('hinted', true);

    return location;
}

function updateTime() {
    // get the current remaining seconds (from inline attribute)
    let currentTime = document.getElementById('teamTime').getAttribute('remaining');

    // calculate the remaining time
    let remainingTime = 1 * currentTime - 1;

    // convert to minutes and seconds
    let minutes = String(Math.floor(remainingTime / 60)).padStart(2, '0');
    let seconds = String(remainingTime - (minutes * 60)).padStart(2, '0');

    // check if the time is up
    if (remainingTime > 0) {
        // set the new remaining seconds as attribute and update the timer
        document.getElementById('teamTime').setAttribute('remaining', remainingTime);
        document.getElementById('teamTime').innerHTML = minutes + ":" + seconds;
    } else {
        // reload page to force timecheck update
        location.reload();
    }
}

function showModal(state) {
    let modal = document.getElementById('modal');
    if (state) {
        modal.style.display = 'block';
    } else {
        modal.style.display = 'none';
    }
}