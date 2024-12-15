// If collect button is pressed, fade in modals for collection
const collect = (link, eventId) => {
    $("#collect-popup-bg").fadeIn(150);
    $("#collect-popup-item").delay(150).fadeIn(150);
    $("#collect-close-popup").click((event) => {
        $("#collect-popup-bg, #collect-popup-item").fadeOut(150);
        $("#collect-amount").val(null);
    });
    let row = link.parentNode.parentNode; // Get table datas
    // Transfer table data to input fields
    $("#collect-student-id").val(row.cells[0].innerHTML);
    $("#collect-student-name").val(row.cells[1].innerHTML);
    $("#collect-event-name").val(row.cells[3].innerHTML);
    $("#collect-event-id").val(eventId);
    $("#collect-total-fee").val(row.cells[5].innerHTML); 
    $("#collect-balance").val(row.cells[6].innerHTML);
    $("#collect-amount").on('input', () => { // Input change in collected amount
        let collectAmount = parseFloat($("#collect-amount").val());
        let currentBalance = parseFloat(row.cells[6].innerHTML);
        if (isNaN(collectAmount) || collectAmount > currentBalance || collectAmount <= 0) {
            // If collected amount is not valid, disable Collect Sanctions button and set balance input to default
            $("#collect-this-fee").prop('disabled', true);
            $("#collect-balance").val(currentBalance);
        } else {
            // If valid, enable Collect Sanctions button and set balance = (current balance - collected amount)
            $("#collect-this-fee").prop('disabled', false);
            $("#collect-balance").val(currentBalance - parseFloat($("#collect-amount").val()));
        }
    });
}

$("#collect-sanction-form").on("submit", async (event) => {
    event.preventDefault();
    const formData = new FormData(event.target);
    const data = {
        "collect_student_id": formData.get("collect-student-id"),
        "collect_event_id": formData.get("collect-event-id"),
        "collect_amount": formData.get("collect-amount")
    };
    try {
        const response = await fetch("?api=collect", {
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