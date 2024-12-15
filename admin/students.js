$("#popup-bg, #popup-item, #edit-popup-item").removeClass("hidden");
$("#popup-bg, #popup-item, #edit-popup-item").hide();
$("#add-student").click((event) => {
    $("#popup-bg").fadeIn(150);
    $("#popup-item").delay(150).fadeIn(150);
    $("#close-popup").click((event) => {
        $("#popup-bg, #popup-item").fadeOut(150);
    });
});

const editRow = (link) => {
    $("#popup-bg").fadeIn(150);
    $("#edit-popup-item").delay(150).fadeIn(150);
    $("#edit-close-popup").click(function() {
        $("#popup-bg, #edit-popup-item").fadeOut(150);
    });
    
    const row = $(link).closest("tr");
    const studentId = row.find("td:eq(0)").text();
    const studentData = namesArray.find((student) => {
        return student[0] === studentId;
    });
    if (studentData) {
        $("#edit-student-id").val(studentData[0]);
        $("#edit-last-name").val(studentData[1]);
        $("#edit-first-name").val(studentData[2]);
        $("#edit-middle-initial").val(studentData[3].replace(".", ""));
        $("#edit-yearsec").val(studentData[4]);
    }
}

$("#add-student-form").on("submit", async (event) => {
    event.preventDefault();
    const formData = new FormData(event.target);
    const data = {
        "student_id": formData.get("student-id"),
        "last_name": formData.get("last-name"),
        "first_name": formData.get("first-name"),
        "middle_initial": formData.get("middle-initial"),
        "yearsec": formData.get("yearsec")
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

$("#edit-student-form").on("submit", async (event) => {
    event.preventDefault();
    const formData = new FormData(event.target);
    const data = {
        "edit_student_id": formData.get("edit-student-id"),
        "edit_last_name": formData.get("edit-last-name"),
        "edit_first_name": formData.get("edit-first-name"),
        "edit_middle_initial": formData.get("edit-middle-initial"),
        "edit_yearsec": formData.get("edit-yearsec")
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

const deleteStudent = async (studentId) => {
    try {
        const response = await fetch("?api=delete", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                "student_id": studentId
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

$(".delete-student").click((event) => {
    const studentId = $(event.currentTarget).attr('name');
    swal({
        title: "Delete this student?",
        text: "This action can't be undone.",
        icon: "warning",
        buttons: true,
        buttons: {
            cancel: 'No',
            confirm : {text: "Yes", className:'bg-custom-purple'},
        },
        dangerMode: true,
    }).then((willDelete) => {
        if (willDelete) {
            deleteStudent(studentId);
        }
    });
});