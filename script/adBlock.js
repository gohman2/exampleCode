export default function adBlockDetection () {
  jQuery(function ($) {
    const adElement = $('#AdHeader'),
          display = $(adElement).css('display'),
          mainBlock = $('#main');
    let ajaxIsActive = false;

    if (display === 'none') {
      if (readCookie('adblock_closed') !== '1') {
        getNotice()
      }
    }

    $(adElement).remove();

    function getNotice () {
      if (ajaxIsActive) return
      $.ajax({
        type: 'POST',
        dataType: 'json',
        url: ajax_params.ajax_url,
        beforeSend: function () {
          ajaxIsActive = true
        },
        data: {
          action: 'get_adblock_notice',
          nonce: ajax_params.adblock_nonce
        },
        complete: function (response) {
          ajaxIsActive = false
          const data = response.responseJSON
          if (data.status !== 1) return
          const mainPadding = $(mainBlock).css('padding-top')
          $('body').addClass('adblock-detected')
          $(mainBlock).prepend(data.content)
          $('.adblock-detected-notice').css({ 'top': mainPadding })
          checkBtnClose()
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.error('Ajax request failed', jqXHR, textStatus, errorThrown)
          ajaxIsActive = false
        }
      })
    }

    function checkBtnClose(){
      $(mainBlock).on('click', '.btn-close-adblocknotice', function () {
        $('body').removeClass('adblock-detected')
        createCookie('adblock_closed', 1, 0.5)
      })
    }

    function createCookie (name, value, days) {
      let expires = ''
      if (days) {
        let date = new Date()
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000))
        let expires = '; expires=' + date.toGMTString()
      }
      document.cookie = name + '=' + value + expires + '; path=/'
    }

    function readCookie (name) {
      let nameEQ = name + '=',
          ca = document.cookie.split(';');
      for (let i = 0; i < ca.length; i++) {
        let c = ca[i]
        while (c.charAt(0) == ' ') c = c.substring(1, c.length)
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length)
      }
      return null
    }

  })

}
