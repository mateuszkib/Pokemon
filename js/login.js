const form = document.getElementById("login-form");
const alertDiv = document.querySelector(".alert");
const alertSpan = document.querySelector(".alert__span");

form.addEventListener("submit", function(e) {
    e.preventDefault();
    let formData = new FormData(this);
    formData.append("action", "login");

    fetch("../system.php", {
        method: "POST",
        body: formData
    })
        .then(response => {
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                alertDiv.style.display = "block";
                alertSpan.classList.add("alert__span--error");
                alertSpan.textContent = data.message;
            } else {
                localStorage.setItem("user", JSON.stringify(data.data));
                window.location.href = "../index.html";
            }
        });
});
