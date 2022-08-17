// always try to fill the leaderboard
fillLeaderboard();

// update the remaining time and check periodically if leaderboard has changed
setInterval(updateLeaderboard, 1000);
setInterval(fillLeaderboard, 15000);

// add the collapsing behaviour to the menu
let coll = document.getElementById('collapsible');
coll.addEventListener("click", function () {
    let content = document.getElementById('collapsible-content');
    if (content.style.display === "none") {
        content.style.display = "block";
    } else {
        content.style.display = "none";
    }
});

// add the year to the footer
document.getElementById("footerYear").innerHTML = new Date().getFullYear();

// add the form event listeners
document.getElementById('makeTeamForm').addEventListener('submit', createTeam);
document.getElementById('joinTeamForm').addEventListener('submit', joinTeam);

// ---------------------------------------------------- \\

async function fillLeaderboard() {
    // clear the table (if one exists)
    document.getElementById('leaderboardTable').innerHTML = "";

    // call the api
    let api_url = 'api/session.php?leaderboard'
    let response = await fetch(api_url);
    let data = await response.json();

    // check if we have a successful response
    if (!response.ok) {
        console.error(response.status, response.statusText, data.message)
        return;
    }

    // grab the different sessions
    let running = data.runningSessions;
    let stopped = data.stoppedSessions;

    // get the table element
    let table = document.getElementById('leaderboardTable');

    // add header row to table
    let breakRow = table.insertRow();
    breakRow.classList.add("header");
    breakRow.insertCell(0).innerHTML = "Running teams";
    breakRow.insertCell(1).innerHTML = "Products found";
    breakRow.insertCell(2).innerHTML = "Hints used";
    breakRow.insertCell(3).innerHTML = "Time left";

    // add all running sessions to table
    running.forEach(session => {
        let row = table.insertRow();
        row.classList.add("running")

        // fill the cells with session info
        row.insertCell(0).innerHTML = session.sessionName;
        row.insertCell(1).innerHTML = session.sessionFoundProducts;
        row.insertCell(2).innerHTML = session.sessionHints;

        // calculate the remaining time
        let finishTime = 1 * session.sessionStartTime + (15 * 60);
        let currentTime = (Math.floor(Date.now() / 1000));
        let remainingTime = finishTime - currentTime;
        row.insertCell(3).innerHTML = remainingTime;
    });

    // add all stopped sessions to table
    breakRow = table.insertRow();
    breakRow.classList.add("header");
    breakRow.insertCell(0).innerHTML = "Finished teams";
    breakRow.insertCell(1).innerHTML = "Products found";
    breakRow.insertCell(2).innerHTML = "Hints used";
    breakRow.insertCell(3).innerHTML = "Score";

    // add all stopped sessions to table
    stopped.forEach(session => {
        let row = table.insertRow();
        row.classList.add("stopped");

        // fill the cells with session info
        row.insertCell(0).innerHTML = session.sessionName;
        row.insertCell(1).innerHTML = session.sessionFoundProducts;
        row.insertCell(2).innerHTML = session.sessionHints;
        row.insertCell(3).innerHTML = session.sessionFoundProducts - (0.5 * session.sessionHints);
        // TODO: change score calculation?
    });
}

function updateLeaderboard() {
    // grab all rows with time
    let rows = document.getElementsByClassName("running");

    // loop through rows and update the time
    for (let row of rows) {
        let currentTime = row.children[3].innerHTML;
        let remainingTime = 1 * currentTime - 1;
        if (remainingTime > 0) {
            row.children[3].innerHTML = remainingTime;
        } else {
            // if one row is out of time, reload the page
            location.reload();
        }
    };
}

async function createTeam(event) {
    // prevent submitting and redirecting page
    event.preventDefault();

    // fetch the team name from the form, encode and create api url
    let teamname = encodeURI(document.getElementById('makeTeam-name').value);
    let api_url = 'api/session.php?new=' + teamname;

    // call the api
    let response = await fetch(api_url);
    let data = await response.json();

    // check if we have a successful response
    if (!response.ok) {
        console.error(response.status, response.statusText, data.message);
        alert("Error: " + data.message);
    } else {
        // if the team was successfully created, 
        console.log("Succesfully created team: " + decodeURI(teamname));
    }

    // clear the form
    document.getElementById('makeTeam-name').value = "";
}

async function joinTeam(event) {
    // prevent submitting and redirecting page
    event.preventDefault();

    // fetch the team name and pin from the form, encode and create api url
    let teamname = encodeURI(document.getElementById('joinTeam-name').value);
    let teampin = document.getElementById('joinTeam-pin').value;
    let api_url = 'api/session.php?join=' + teamname + "&pin=" + teampin;

    // call the api
    let response = await fetch(api_url);
    let data = await response.json();

    // check if we have a successful response
    if (!response.ok) {
        console.error(response.status, response.statusText, data.message);
        alert("Error: " + data.message);
    } else {
        // if the team was successfully created, 
        console.log("Succesfully joined team: " + decodeURI(teamname));
    }

    // clear the form
    document.getElementById('joinTeam-name').value = "";
    document.getElementById('joinTeam-pin').value = "";
}