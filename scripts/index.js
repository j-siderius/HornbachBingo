// always try to fill the leaderboard
fillLeaderboard();

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

// add the form event listeners
document.getElementById('makeTeamForm').addEventListener('submit', createTeam)
document.getElementById('joinTeamForm').addEventListener('submit', joinTeam)

// ---------------------------------------------------- \\

async function fillLeaderboard() {
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

    // add all running sessions to table
    let breakRow = table.insertRow();
    breakRow.classList.add("running");
    breakRow.insertCell().innerHTML = "Running sessions";
    running.forEach(session => {
        let row = table.insertRow();

        // fill the cells with session info
        row.insertCell(0).innerHTML = session.sessionName;
        row.insertCell(1).innerHTML = session.sessionFoundProducts;
        row.insertCell(2).innerHTML = session.sessionHints;
        row.insertCell(3).innerHTML = session.sessionStartTime;
    });

    // add all stopped sessions to table
    breakRow = table.insertRow();
    breakRow.classList.add("stopped");
    breakRow.insertCell().innerHTML = "Finished sessions";
    stopped.forEach(session => {
        let row = table.insertRow();

        // fill the cells with session info
        row.insertCell(0).innerHTML = session.sessionName;
        row.insertCell(1).innerHTML = session.sessionFoundProducts;
        row.insertCell(2).innerHTML = session.sessionHints;
        row.insertCell(3).innerHTML = session.sessionStartTime;
    });
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