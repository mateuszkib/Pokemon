const buttonNav = document.getElementById("search");

if (localStorage.getItem("page") === "search") {
    getSearch();
} else {
    getActions();
}

buttonNav.addEventListener("click", function() {
    container.textContent = "";
    localStorage.setItem("page", "search");
    getSearch();
});

function getPokemon(input) {
    input.addEventListener("keypress", function(e) {
        if (e.key === "Enter") {
            let removeDiv = document.querySelector(".container-pokemon");

            if (removeDiv) {
                removeDiv.remove();
            }

            fetch(
                `system.php?action=search-pokemon&name=${input.value
                    .trim()
                    .toLowerCase()}`
            )
                .then(response => {
                    return response.json();
                })
                .then(res => {
                    if (!res.success) {
                        alertDiv.style.display = "block";
                        alertSpan.classList.add("alert__span--error");
                        alertSpan.textContent = res.message;
                        hideAlert(alertDiv);
                    } else {
                        let { data } = res;
                        let div = createElement("div", {
                            class: ["container-pokemon"]
                        });
                        let nameTitle = createElement(
                            "h2",
                            {
                                class: ["container-pokemon__title"]
                            },
                            data.name
                        );
                        let movesTitle = createElement(
                            "h2",
                            {
                                class: ["container-pokemon__title"]
                            },
                            "Moves"
                        );
                        let image = createElement("img", {
                            src: data.image,
                            alt: `Image Pokemon ${data.name}`,
                            class: ["container-pokemon__image"]
                        });
                        let ul = createElement("ul", {
                            class: ["container-pokemon__list"]
                        });
                        container.appendChild(div);
                        div.appendChild(nameTitle);
                        div.appendChild(image);
                        div.appendChild(movesTitle);
                        div.appendChild(ul);

                        for (let item of data.moves) {
                            let li = createElement(
                                "li",
                                {
                                    class: ["container-pokemon__list__item"]
                                },
                                item
                            );
                            ul.appendChild(li);
                        }
                    }
                });
        }
    });
}

function createElement(type, attributes, text = "") {
    let element = document.createElement(type);
    for (let key in attributes) {
        if (key == "class") {
            element.classList.add(...attributes[key]);
        } else {
            element[key] = attributes[key];
        }
    }
    if (text) {
        element.textContent = text;
    }

    return element;
}

function getSearch() {
    let div = createElement("div", { class: ["container-search"] });
    let input = createElement("input", {
        id: "name",
        type: "text",
        name: "name"
    });
    container.appendChild(div);
    div.appendChild(input);
    getPokemon(input);
}
