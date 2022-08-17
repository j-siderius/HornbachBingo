// get session when first loading page
getSession();

// attach event handlers to buttons
document.getElementById('homeButton').addEventListener('click', () => {
    window.location.href = "index.html";
})
document.getElementById('hintButton').addEventListener('click', getProductHint);
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
            cell.onclick = () => {
                document.getElementById('productName').innerHTML = product.productName;
                document.getElementById('productImage').src = product.productPicture;
                document.getElementById('checkProduct-id').value = product.productID;
                document.getElementById('hintButton').setAttribute('productId', product.productID);
                document.getElementById('productHint').style.display = "none";
                showModal(true);
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

async function getProductHint(event) {
    // prevent submitting and redirecting page
    event.preventDefault();

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
    document.getElementById('productLocation').innerHTML = location;
    document.getElementById('productHint').style.display = "block";

    // update the cell attributes to show that the product has been hinted
    document.querySelector('[productid="' + productId + '"]').setAttribute('hinted', true);

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