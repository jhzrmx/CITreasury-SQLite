const sendNotif = (notif_title, notif_body, notif_icon, notif_clicked_function) => {
  if ('Notification' in window) {
    Notification.requestPermission().then(function(result) {
      if (result === 'granted') {
        new Notification(notif_title,
          {
            body: notif_body,
            icon: notif_icon
          }
        ).addEventListener('click', notif_clicked_function);
      }
    });
  }
}