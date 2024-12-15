$("#edit-popup-bg, #edit-popup-item").removeClass("hidden");
$("#edit-popup-bg, #edit-popup-item").hide();
const editRow = (link) => {
    let row = link.parentNode.parentNode
    $("#edit-popup-bg").fadeIn(150);
    $("#edit-popup-item").delay(150).fadeIn(150);
    $("#edit-close-popup").click(function() {
        $("#edit-popup-bg, #edit-popup-item").fadeOut(150);
    });
    $("#edit-student-id").val(row.cells[0].innerHTML);
    $("#edit-email").val(row.cells[1].innerHTML);
    $("#edit-account-type").val(row.cells[2].innerHTML);
    if ($("#edit-student-id").val() === "<?php echo $_SESSION['cit-student-id']; ?>") {
        $("#edit-account-type").prop('disabled', true);
    } else {
        $("#edit-account-type").prop('disabled', false);
    }
}
$("#edit-privilege-form").on("submit", async (event) => {
    event.preventDefault();
    const formData = new FormData(event.target);
    const data = {
        "edit_student_id": formData.get("edit-student-id"),
        "edit_account_type": formData.get("edit-account-type")
    };
    try {
        const response = await fetch("?api=edit", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify(data)
        });
        const text = await response.text();
        const result = JSON.parse(text);
        if (result.status != "error") {
            swal(result.message, result.details, result.status).then(() => {
                window.location.href = "accountprivileges.php";
            });
        } else {
            swal(result.message, result.details, result.status);
        }
    } catch (error) {
        swal("An unexpected error occurred.", error, "error");
    }
});