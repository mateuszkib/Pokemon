let formData = new FormData();
let user = localStorage.getItem("user");

formData.append("action", "is-auth");
formData.append("user", user);

fetch("system.php", { method: "POST", body: formData })
    .then(response => {
        return response.json();
    })
    .then(data => {
        if (!data.success) {
            window.location.href = "views/login.html";
        }
    });
