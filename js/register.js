const form = document.getElementById("register-form");
const alertDiv = document.querySelector(".alert");
const alertSpan = document.querySelector(".alert__span");

form.addEventListener("submit", function(e) {
    e.preventDefault();
    let formData = new FormData(this);
    formData.append("action", "register");

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
                alertDiv.style.display = "block";
                alertSpan.classList.add("alert__span--success");
                alertSpan.textContent = data.message;
                form.reset();
            }
        });
});
