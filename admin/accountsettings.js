$("#popup-bg, #popup-item, #chp-popup-item, #dlacc-popup-item").removeClass("hidden");
$("#popup-bg, #popup-item, #chp-popup-item, #dlacc-popup-item").hide();

const closePopup = (button_id, bg, popup_item, close_popup) => {
    $(button_id).click((event) => {
        $(bg).fadeIn(150);
        $(popup_item).delay(150).fadeIn(150);
        $(close_popup).click((event) => {
            $(bg + ", " + popup_item).fadeOut(150);
        });
    });
};

closePopup("#change-information-btn", "#popup-bg", "#popup-item", "#close-popup");
closePopup("#change-password-btn", "#popup-bg", "#chp-popup-item", "#chp-close-popup");
closePopup("#delete-account-btn", "#popup-bg", "#dlacc-popup-item", "#dlacc-close-popup");

// While editing in new and confirm password
$("#new-password, #confirm-password").on('input', () => {
    // If new password is not the same as confirm password, disable save changes button, otherwise enable it
    if ($("#new-password").val() !== $("#confirm-password").val()) {
        $("#update-password").prop('disabled', true);
    } else {
        $("#update-password").prop('disabled', false);
    }
});

$("#change-info-form").on("submit", async (event) => {
    event.preventDefault();
    const formData = new FormData(event.target);
    const data = {
        "edit_last_name": formData.get("edit-last-name"),
        "edit_first_name": formData.get("edit-first-name"),
        "edit_middle_initial": formData.get("edit-middle-initial"),
        "edit_yearsec": formData.get("edit-yearsec")
    };
    try {
        const response = await fetch("?api=change-info", {
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
                location.reload();
            });
        } else {
            swal(result.message, result.details, result.status);
        }
    } catch (error) {
        swal("An unexpected error occurred.", error, "error");
    }
});

$("#change-password-form").on("submit", async (event) => {
    event.preventDefault();
    const formData = new FormData(event.target);
    const data = {
        "old_password": formData.get("old-password"),
        "new_password": formData.get("new-password"),
        "confirm_password": formData.get("confirm-password")
    };
    try {
        const response = await fetch("?api=change-pass", {
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
                location.reload();
            });
        } else {
            swal(result.message, result.details, result.status);
        }
    } catch (error) {
        swal("An unexpected error occurred.", error, "error");
    }
});