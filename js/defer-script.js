const clickToShowHide = (button_id, target_show_hide) => {
    const target = $(target_show_hide);
    target.hide();
    let isOpened = false;
    $(button_id).click((e) => {
        isOpened = !isOpened;
        if (isOpened) {
            target.slideDown();
        } else {
            target.slideUp();
        }
    });
}

clickToShowHide("#mdi-menu", "#menu-items-mobile");
clickToShowHide("#mdi-menu", "#menu-user-items-mobile");
// clickToShowHide("#menu-items #account-set", "#menu-items #account-set-items");
// clickToShowHide("#menu-items-mobile #account-set", "#menu-items-mobile #account-set-items");

const logoutAction = (button_id) => {
    $(button_id).click((e) => {
        swal({
            title: "Logout?",
            text: "You can log into your account at anytime.",
            icon: "info",
            buttons: true,
            buttons: {
                cancel: 'No',
                confirm : {text: "Yes", className:'bg-custom-purple'},
            },
            dangerMode: false,
        }).then((willLogout) => {
            if (willLogout) {
                window.location.href = "../logout.php";
            }
        });
    });
}

logoutAction("#menu-items-mobile #logout");
logoutAction("#menu-items #logout");
logoutAction("#menu-user-items-mobile #logout");
logoutAction("#menu-user-items #logout");