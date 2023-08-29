(function($){
  $(document).on('heartbeat-send', function(e, data) {
    data['wp_fail2ban_addon_blocklist'] = 'wp_fail2ban_addon_blocklist_summary';
  });
  $(document).on('heartbeat-tick', function(e, data) {
    if (data['wp-fail2ban-addon-blocklist-summary']) {
      function updateLi(item, data, queued) {
        item.find('span.last-entries').html(data['last']['entries']);
        item.find('span.total-entries').html(data['total']['entries']);
        item.find('span.last-dt').html(data['last']['dt']);
        item.find('span.total-requests').html(data['total']['requests']);
        if (queued) {
          item.find('span.queued').html(queued);
        }
      }
      updateLi($('#wp_fail2ban_addon_blocklist_summary li.upload'), data['wp-fail2ban-addon-blocklist-summary']['upload'], data['wp-fail2ban-addon-blocklist-summary']['upload']['queued']);
      updateLi($('#wp_fail2ban_addon_blocklist_summary li.download'), data['wp-fail2ban-addon-blocklist-summary']['download']);
    }
  });
}(jQuery));
