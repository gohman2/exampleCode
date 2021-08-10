import Popup from './modules/popup'

export default function redirectionPopup () {
  const redirectData = window.redirectData,
        userLocation = redirectData.userLocation,
        swedishLocale = redirectData.swedishLocale,
        deutschLocale = redirectData.deutschLocale,
        intLocale = redirectData.intLocale,
        intHomeUrl = redirectData.intHomeUrl,
        svHomeUrl = redirectData.svHomeUrl,
        deHomeUrl = redirectData.deHomeUrl,
        domain = redirectData.domain,
        activate = redirectData.activate;
  let redirectUrl = false,
      ajaxIsActive = false;

  if(!activate) return;

  if(!userLocation) return;

  if (userLocation == 'de' && !deutschLocale) {
     redirectUrl = deHomeUrl;
   } else if (userLocation == 'se' && !swedishLocale){
     redirectUrl = svHomeUrl;
   } else if (!intLocale && userLocation != 'de' && userLocation != 'se'){
     redirectUrl  = intHomeUrl;
  }

  if(!redirectUrl) return;
    if (checkCookie('success') || checkCookie('reject')) {
    return
  }else{
      getPopup();
  }

  document.addEventListener('click', function (e) {
    if (e.target && e.target.classList.contains('js--confirm-redirect')) {
      confirmRedirect(redirectUrl)
    } else if (e.target && e.target.classList.contains('js--reject-redirect')) {
      rejectRedirect()
    }
  }, false);

  function getPopup(){
    if (ajaxIsActive) return
    jQuery.ajax({

      type: 'POST',
      dataType: 'json',
      url: ajax_params.ajax_url,

      beforeSend: function () {
        ajaxIsActive = true
      },
      data: {
        action: 'get_redirection_popup',
        nonce: ajax_params.redirection_nonce
      },
      complete: function (response) {
        ajaxIsActive = false
        const data = response.responseJSON
        if (data.status !== 1) return
        new Popup({
          type: 'dynamic',
          content: data.content
        })
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error('Ajax request failed', jqXHR, textStatus, errorThrown)
        ajaxIsActive = false
      }
    })
  }

  function confirmRedirect(url){
    document.cookie = `neRedirect=success;domain=.${domain};path=/;max-age=86400`;
    window.location.href = url;
  }

  function rejectRedirect(){
    document.cookie = `neRedirect=reject;domain=.${domain};path=/;max-age=86400`;
  }

  function checkCookie(status){
    if(document.cookie.split(';').filter((item) => item.includes(`neRedirect=${status}`)).length){
      return true;
    }else{
      return false;
    }

  }

}
