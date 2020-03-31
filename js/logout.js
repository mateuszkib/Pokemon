const buttonLogout = document.getElementById("logout");

buttonLogout.addEventListener("click", function() {
    let formData = new FormData();
    formData.append("action", "logout");

    fetch("system.php", { method: "POST", body: formData })
        .then(response => {
            return response.json();
        })
        .then(data => {
            if (data.success) {
                localStorage.removeItem("user");
                window.location.href = "views/login.html";
            }
        });
});
