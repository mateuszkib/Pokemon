const buttonActions = document.getElementById("actions");
const container = document.querySelector(".container");
const buttonsDeleteAction = document.querySelectorAll(".delete");
const alertDiv = document.querySelector(".alert");
const alertSpan = document.querySelector(".alert__span");
const parseUser = user ? JSON.parse(user) : "";

buttonActions.addEventListener("click", function() {
    container.textContent = "";
    localStorage.setItem("page", "actions");
    getActions();
});

function generateTableThead(table, data) {
    let thead = table.createTHead();
    let row = thead.insertRow();
    for (let item of data) {
        let th = document.createElement("th");
        let text = document.createTextNode(item);
        th.appendChild(text);
        row.appendChild(th);
    }
}

function generateTable(table, data) {
    for (let element of data) {
        let row = table.insertRow();
        for (key in element) {
            let cell = row.insertCell();
            if (key === "Action" && element[key] !== "") {
                let button = document.createElement("button");
                button.textContent = element[key];
                button.setAttribute("data-id", element.Id);
                button.classList.add("delete");
                deleteAction(button);
                cell.appendChild(button);
            } else {
                let text = document.createTextNode(element[key]);
                cell.appendChild(text);
            }
        }
    }
}

function deleteAction(button) {
    button.addEventListener("click", async function() {
        let id = this.getAttribute("data-id");
        let formData = new FormData();
        formData.append("action", "delete-action");
        formData.append("id", +id);

        fetch("system.php", { method: "POST", body: formData })
            .then(response => {
                return response.json();
            })
            .then(data => {
                if (!data.success) {
                    alertDiv.style.display = "block";
                    alertSpan.classList.add("alert__span--error");
                    alertSpan.textContent = data.message;
                    hideAlert(alertDiv);
                } else {
                    alertDiv.style.display = "block";
                    alertSpan.classList.add("alert__span--success");
                    alertSpan.textContent = data.message;
                    getActions();
                    hideAlert(alertDiv);
                }
            });
    });
}

async function getActions() {
    container.textContent = "";
    let response = await fetch("system.php?action=get-actions");
    let { data } = await response.json();

    let dataStructureForTable = data.map((item, key) => ({
        Lp: key + 1,
        Id: item.id,
        Login: item.email,
        Name: item.name,
        Action: item.user_id == parseUser.id ? "Delete" : ""
    }));
    let table = document.createElement("table");
    table.setAttribute("id", "table");
    container.appendChild(table);
    generateTable(table, dataStructureForTable);
    generateTableThead(table, Object.keys(dataStructureForTable[0]));
}

function hideAlert(alert) {
    setTimeout(function() {
        alert.style.display = "none";
    }, 2000);
}
