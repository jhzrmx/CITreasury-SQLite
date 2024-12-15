$("#popup-bg, #popup-item, #edit-popup-item").removeClass("hidden");
$("#popup-bg, #popup-item, #edit-popup-item, #tooltip-content-date").hide();
// If (+) button is pressed, fade in modals
$("#add-event").click((event) => {
    $("#popup-bg").fadeIn(150);
    $("#popup-item").delay(150).fadeIn(150);
    $("#close-popup").click((event) => { // If closed, fade out modals
        $("#popup-bg, #popup-item").fadeOut(150);
    });
});

// If edit button is pressed, fade in modals
const editRow = (link) => {
    $("#popup-bg").fadeIn(150);
    $("#edit-popup-item").delay(150).fadeIn(150);
    $("#edit-close-popup").click((event) => { // If closed, fade out modals
        $("#popup-bg").fadeOut(150);
        $("#edit-popup-item").fadeOut(150);
    });
    let row = link.parentNode.parentNode; // Get table data
    // Transfer table data to input fields
    $("#edit-event-id").val(row.cells[0].innerHTML);
    $("#edit-event-name").val(row.cells[1].innerHTML);
    $("#edit-event-desc").val(row.cells[2].innerHTML);
    // Populate checkboxes for event-target
    const targetYears = row.cells[3].innerHTML.split(','); // Split target years by commas
    $("input[name='edit-event-target[]']").each(function() {
        if (targetYears.includes($(this).val())) {
            $(this).prop('checked', true);
        } else {
            $(this).prop('checked', false);
        }
    });
    $("#edit-event-date").val(row.cells[4].innerHTML);
    $("#edit-fee-per-event").val(row.cells[5].innerHTML);
    $("#edit-sanction-fee").val(row.cells[6].innerHTML);
}

$('#edit-event-date').hover(() => {
        $('#tooltip-content-date').fadeIn(150);
    }, () => {
        $('#tooltip-content-date').fadeOut(150);
    }
);

const today = new Date();
today.setMinutes(today.getMinutes() - today.getTimezoneOffset());
const todayString = today.toISOString().split('T')[0];
$('#event-date').attr('min', todayString);
$("#add-event-form").on("submit", async (event) => {
    event.preventDefault();
    const formData = new FormData(event.target);
    const data = {
        "event_name": formData.get("event-name"),
        "event_desc": formData.get("event-desc"),
        "event_target": formData.getAll("event-target[]"),
        "event_date": formData.get("event-date"),
        "fee_per_event": formData.get("fee-per-event"),
        "sanction_fee": formData.get("sanction-fee")
    };
    try {
        const response = await fetch("?api=add", {
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

$("#edit-event-form").on("submit", async (event) => {
    event.preventDefault();
    const formData = new FormData(event.target);
    const data = {
        "edit_event_id": formData.get("edit-event-id"),
        "edit_event_name": formData.get("edit-event-name"),
        "edit_event_desc": formData.get("edit-event-desc"),
        "edit_event_target": formData.getAll("edit-event-target[]"),
        "edit_event_date": formData.get("edit-event-date"),
        "edit_fee_per_event": formData.get("edit-fee-per-event"),
        "edit_sanction_fee": formData.get("edit-sanction-fee")
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
                location.reload();
            });
        } else {
            swal(result.message, result.details, result.status);
        }
    } catch (error) {
        swal("An unexpected error occurred.", error, "error");
    }
});

const deleteEvent = async (eventId) => {
    try {
        const response = await fetch("?api=delete", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                "event_id": eventId
            })
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
}

$(".delete-event").click((event) => {
    const eventId = $(event.currentTarget).attr('name');
    swal({
        title: "Delete this event?",
        text: "This will also delete all registrations made.",
        icon: "warning",
        buttons: true,
        buttons: {
            cancel: 'No',
            confirm : {text: "Yes", className:'bg-custom-purple'},
        },
        dangerMode: true,
    }).then((willDelete) => {
        if (willDelete) {
            deleteEvent(eventId);
        }
    });
});