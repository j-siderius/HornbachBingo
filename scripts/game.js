getSession();

// attach event handlers to buttons
document.getElementById('homeButton').addEventListener('click', () => {
    window.location.href = "index.html";
})

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
    }

    console.log(data);

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
}

async function checkProduct() {

}

async function getProductHint() {

}

function updateTime() {
    // get the current remaining seconds (from inline attribute)
    let currentTime = document.getElementById('teamTime').getAttribute('remaining');

    // calculate the remaining time
    let remainingTime = 1 * currentTime - 1;

    // convert to minutes and seconds
    let minutes = String(Math.floor(remainingTime/60)).padStart(2, '0');
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