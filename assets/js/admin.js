toastr.options = {
  closeButton: true,
  newestOnTop: true,
  progressBar: true,
  positionClass: 'toast-top-right',
  timeOut: 4000,
  extendedTimeOut: 2000,
  preventDuplicates: true,
};

(function (root, factory) {
  if (typeof define === 'function' && define.amd) define(['jquery'], factory);
  else if (typeof module === 'object' && module.exports) module.exports = factory(require('jquery'));
  else root.MockginiImage = factory(root.jQuery);
})(this, function ($) {
  'use strict';

  // ------------------------------------------------------
  // Helpers
  // ------------------------------------------------------
  function unwrapPayload(res) {
    // Works with any of these shapes:
    // { success:true, data:{ ... } }
    // { data:{ ... } }
    // { ... }
    return res?.data || res;
  }

  function extractImages(res) {
    const payload = unwrapPayload(res);
    const candidates = payload?.candidates || payload?.data?.candidates || [];
    const images = [];

    for (const cand of candidates) {
      const parts = cand?.content?.parts || [];
      for (const p of parts) {
        // Primary (your sample): inlineData { mimeType, data }
        if (p?.inlineData?.data) {
          const mime = p.inlineData.mimeType || 'image/png';
          const b64 = String(p.inlineData.data).replace(/\s+/g, '');
          images.push({ src: `data:${mime};base64,${b64}`, mime });
        }
        // Fallbacks for other SDK shapes:
        else if (p?.inline_data?.data) {
          const mime = p.inline_data.mime_type || 'image/png';
          const b64 = String(p.inline_data.data).replace(/\s+/g, '');
          images.push({ src: `data:${mime};base64,${b64}`, mime });
        } else if (p?.fileData?.fileUri) {
          images.push({ src: p.fileData.fileUri, mime: 'image/*' });
        } else if (p?.file_data?.file_uri) {
          images.push({ src: p.file_data.file_uri, mime: 'image/*' });
        } else if (p?.url) {
          images.push({ src: p.url, mime: 'image/*' });
        }
      }
    }
    return images;
  }

  function ensureEl(selector, fallbackParent) {
    let $el = $(selector);
    if (!$el.length && fallbackParent) {
      $el = $(`<div id="${selector.replace('#', '')}" />`).appendTo(fallbackParent);
    }
    return $el;
  }

  function setMainImage(src) {
    const $img = ensureEl('#mockgini-image', 'body');
    $img.attr('src', src).css({ display: 'block', maxWidth: '100%', height: 'auto' });
  }

  function setDownloadLink(src, mime = 'image/png') {
    let $a = $('#mockgini-download');
    if (!$a.length) {
      $a = $('<a id="mockgini-download" class="button" style="margin-top:8px; display:inline-block;">Download image</a>');
      const $container = $('#mockgini-gallery').length ? $('#mockgini-gallery') : $('body');
      $container.append($a);
    }
    const ext = (mime.split('/')[1] || 'png').toLowerCase();
    $a.attr({ href: src, download: `mockgini-${Date.now()}.${ext}` });
  }

  function renderGallery(images) {
    const $wrap = ensureEl('#mockgini-gallery', 'body');
    $wrap.empty();
    images.forEach((im, i) => {
      const $thumb = $('<img>')
        .attr('src', im.src)
        .attr('alt', `Generated ${i + 1}`)
        .css({ maxWidth: '120px', margin: '6px', cursor: 'pointer', verticalAlign: 'middle', borderRadius: '6px' })
        .on('click', function () {
          setMainImage(im.src);
          setDownloadLink(im.src, im.mime);
        });
      $wrap.append($thumb);
    });
  }

  function render(res) {
    const images = extractImages(res);
    if (!images.length) {
      console.warn('No images found in response:', res);
      alert('No image returned. Try another prompt.');
      return;
    }
    setMainImage(images[0].src);
    setDownloadLink(images[0].src, images[0].mime);
    if (images.length > 1) renderGallery(images);
  }

  return { render };
});

// =====================================================================
// Media Library Integration + Modal Wiring
// =====================================================================
jQuery(document).ready(function ($) {
  // Run only in Media Library
  if (!$('body').hasClass('post-type-attachment')) {
    return;
  }

  // Add Mockgenie button into toolbar
  function addMockginiButton() {
    const $toolbar = $('.select-mode-toggle-button');

    // Prevent duplicates
    if ($toolbar.length && $('.mockgini-generate-btn').length === 0) {
      const $mockginiBtn = $('<button/>', {
        type: 'button',
        class: 'button mockgini-generate-btn',
        text: '✨ Generate with Mockgenie',
      });

      $toolbar.after($mockginiBtn);

      // On click — open modal
      $mockginiBtn.on('click', function () {
        const $modal = $('#mockgini-modal');
        $('#mockgini-prompt-text').val('');
        $('#mockgini-loader').hide();
        $('#mockgini-generate, #mockgini-cancel').show();

        // Disable generate button initially
        $('#mockgini-generate').prop('disabled', true);

        $modal.fadeIn(200).css('display', 'flex');
        $('#mockgini-output').html('');
        $('#mockgini-prompt-text').prop('disabled', false);
      });
    }
  }



  // Initialize button
  $(document).on('click', '.select-mode-toggle-button, .page-title-action', function () {
    addMockginiButton();
  });

  // Try to add immediately if toolbar already loaded
  addMockginiButton();

  const $mockginiModal = $('#mockgini-modal');

  // Close modal
  $(document).on('click', '.mockgini-close, #mockgini-cancel', function () {
    $mockginiModal.fadeOut(150);
  });

  // Close modal on ESC key
  $(document).on('keydown', function (e) {
    if (e.key === 'Escape' || e.keyCode === 27) {
      $mockginiModal.fadeOut(150);
    }
  });

  // Enable generate button only when prompt has text
  $('#mockgini-prompt-text').on('input', function () {
    const value = $(this).val().trim();
    $('#mockgini-generate').prop('disabled', value === '');
  });
});

jQuery(document).ready(function($) {

    // Close modal on click of either close button or cancel button
    $(document).on('click', '.mockgeni-close, .mockgini-img-close', function() {
        $('#mockgini-img-modal').fadeOut(200); // Smooth fade-out
    });

    // (Optional) Close when clicking outside the modal content
    $(document).on('click', '#mockgini-img-modal', function(e) {
        if ($(e.target).is('#mockgini-img-modal')) {
            $('#mockgini-img-modal').fadeOut(200);
        }
    });

});


// =====================================================================
// Generation / Regeneration / Save Flow
// =====================================================================
jQuery(document).ready(function ($) {
  let lastPrompt = ''; // store last prompt
  let lastImageData = ''; // store last generated image base64

  function getImageDataFromResponse(response) {
    if (!response || !response.data || !response.data.api_response) {
      return null;
    }

    const candidates = response.data.api_response.data?.candidates;
    if (!candidates || !Array.isArray(candidates) || candidates.length === 0) {
      return null;
    }

    const parts = candidates[0].content?.parts;
    if (!parts || !Array.isArray(parts) || parts.length === 0) {
      return null;
    }

    // Try parts[0], if undefined then parts[1]
    let imageData = parts[0]?.inlineData?.data || parts[1]?.inlineData?.data || null;

    return imageData;
  }

  // Generate image via AJAX
  function generateImage(prompt) {
    const $btn = $('#mockgini-generate');
    const $promptInput = $('#mockgini-prompt-text');

    const lockUI = (locked) => {
      $btn.prop('disabled', locked);
      $promptInput.prop('disabled', locked);
      $('#mockgini-loader').toggle(locked);
    };

    if (!prompt) {
      if (window.toastr) toastr.warning('Please enter a prompt.');
      return;
    }

    lockUI(true);

    $.ajax({
      url: MOCKGENIE.ajax_url,
      method: 'POST',
      dataType: 'json',
      data: {
        action: 'mockgini_generate_image',
        prompt: prompt,
        _wpnonce: MOCKGENIE.nonce,
      },
      success: function (response) {

        console.log( response ); 
        $('#mockgini-modal').hide();

        if (response && response.success) {
          const imageData = getImageDataFromResponse(response);
          if (!imageData) {
            if (window.toastr) toastr.warning('No image found in API response.');
            return;
          }

          lastImageData = imageData;
          $('#mockgini-generated-image').attr('src', 'data:image/png;base64,' + imageData);
          $('.mockgeni-img-modal').fadeIn();
          if (window.toastr) toastr.success('Image generated successfully.');
        } else {
          const msg = response?.data?.message || 'Error generating image.';
          if (window.toastr) toastr.error(msg);
        }
      },
      error: function (xhr) {
        let msg = 'An error occurred while generating the image.';

        if (xhr?.responseJSON?.data?.message) {
            try {
                // Parse the JSON string
                const parsed = JSON.parse(xhr.responseJSON.data.message);

                // Use the 'message' property from the parsed object
                msg = parsed.message || msg;
            } catch (e) {
                // fallback if parsing fails
                msg = xhr.responseJSON.data.message;
            }
        } else if (xhr?.status) {
            msg = `Request failed (${xhr.status}${xhr.statusText ? ' ' + xhr.statusText : ''}).`;
        }

        $('#mockgini-modal').hide();
        $('#mockgini-prompt-text').val('');

        if (window.toastr) {
            toastr.error(msg);
        } else {
            alert(msg);
        }
    }
,
      complete: function () {
        lockUI(false);
      }
    });
  }


  // Initial generate button
  $('#mockgini-generate').on('click', function (e) {
    e.preventDefault();
    lastPrompt = $('#mockgini-prompt-text').val().trim();
    generateImage(lastPrompt);
  });

  // Regenerate button
  $(document).on('click', '#mockgini-regenerate', function () {
    if (!lastPrompt) return;
    $('#mockgini-img-modal').hide();
    $('#mockgini-modal').show();
    $('#mockgini-generated-image').attr('src', '');
    $('#mockgini-prompt-text').val(lastPrompt).focus();
  });

  // Save button (send AJAX to save image)
  $(document).on('click', '#mockgini-save', function () {
    if (!lastImageData || !lastPrompt) {
      alert('No image to save!');
      return;
    }

    $('.mockgeni-img-modal-content').hide();
    $('#mg_loader').show();

    $.ajax({
      url: MOCKGENIE.ajax_url,
      method: 'POST',
      dataType: 'json',
      data: {
        action: 'mockgini_save_image',
        image_base64: lastImageData,
        prompt: lastPrompt,
        _wpnonce: MOCKGENIE.nonce,
      },
      success: function () {
        $('#mg_loader').hide();
        window.location.reload();
      },
      error: function () {
        alert('AJAX error: could not save image.');
      },
    });
  });

  // Close image modal
  $(document).on('click', '.mockgini-close', function () {
    $('.mockgeni-img-modal').fadeOut();
  });
});

// =====================================================================
// Upgrade Modal + Auth Flip + Tabs + Pagination + Image Popup
// =====================================================================
jQuery(document).ready(function ($) {
  // Upgrade confirm modal
  $('.mg_upgrade-btn').on('click', function (e) {
    e.preventDefault();
    $('#mg-upgrade-confirm').fadeIn(200);
  });

  $('#mg-confirm-no, .mg-confirm-close').on('click', function () {
    $('#mg-upgrade-confirm').fadeOut(200);
  });

  $('#mg-confirm-yes').on('click', function () {
    const url = MOCKGENIE.upgrade_url;
    window.open(url, '_blank');
    $('#mg-upgrade-confirm').fadeOut(200);
  });

  // Flip Login / Create Account / Forgot Password
  function flipCard(tab) {
  const $btn = $('#mg_button_create_reset');

  $('.mg_flip-card').addClass('mg_flipped');

  if (tab === 'create') {
    $('#mg_form-heading').text('Create Account');
    $('.mg_form-para').text(
      'Enter your email and we’ll send you the credentials to generate and edit images inside WordPress.'
    );
    $btn.text('Create Account').data('tab', 'create'); // use .data()
  } else if (tab === 'reset') {
    $('#mg_form-heading').text('Reset Password');
    $('.mg_form-para').text('We will send Reset Password Link to your email.');
    $btn.text('Reset Account').data('tab', 'reset'); // use .data()
  }
}

$('#mg_show-create').on('click', function (e) {
  e.preventDefault();
  flipCard('create');
});

$('#mg_show-reset').on('click', function (e) {
  e.preventDefault();
  flipCard('reset');
});

$('#mg_show-login').on('click', function (e) {
  e.preventDefault();
  $('.mg_flip-card').removeClass('mg_flipped');
  $('#mg_create_message').hide().html('');
});

  // Main Navigation Tab Switching
  function activateTab(tab) {
    $('.mg_nav-tab').removeClass('mg_active');
    $(`.mg_nav-tab[data-tab="${tab}"]`).addClass('mg_active');

    $('.mg_tab-section').hide();
    $(`.mg_tab-section[data-tab="${tab}"]`).show();
  }

  $('.mg_nav-tab').on('click', function () {
    var tab = $(this).data('tab');
    activateTab(tab);
    localStorage.setItem('activeTab', tab);
  });

  var activeTab = localStorage.getItem('activeTab') || 'generation';
  activateTab(activeTab);
// =====================================================================
// Pagination for Image Grid
// =====================================================================
jQuery(document).ready(function ($) {
  var itemsPerPage = MOCKGENIE.number_of_image; 
  var $realImages = $('.mg_image-grid .mg_image-item'); // only real images
  var totalItems = $realImages.length;
  var totalPages = Math.ceil(totalItems / itemsPerPage);
  var maxVisibleButtons = 5;
  var currentPage = 1;

  function showPage(page) {
    currentPage = page;

    // Hide all real images
    $realImages.hide();

    // Calculate start/end index
    var startIndex = (page - 1) * itemsPerPage;
    var endIndex = startIndex + itemsPerPage;

    // Show only images for the current page
    $realImages.slice(startIndex, endIndex).show();

    renderPagination(page);
  }

  function renderPagination(page) {
    var $pagination = $('.mg_pagination');
    $pagination.empty();

    // Previous button
    $pagination.append('<button class="mg_page-btn mg_prev"' + (page === 1 ? ' disabled' : '') + '>&lt;</button>');

    // Page range
    var startPage = Math.max(1, page - Math.floor(maxVisibleButtons / 2));
    var endPage = Math.min(totalPages, startPage + maxVisibleButtons - 1);
    startPage = Math.max(1, endPage - maxVisibleButtons + 1);

    if (startPage > 1) {
      $pagination.append('<button class="mg_page-btn" data-page="1">1</button>');
      if (startPage > 2) $pagination.append('<span class="mg_page-dots">...</span>');
    }

    for (var i = startPage; i <= endPage; i++) {
      $pagination.append(
        '<button class="mg_page-btn' + (i === page ? ' mg_active' : '') + '" data-page="' + i + '">' + i + '</button>'
      );
    }

    if (endPage < totalPages) {
      if (endPage < totalPages - 1) $pagination.append('<span class="mg_page-dots">...</span>');
      $pagination.append('<button class="mg_page-btn" data-page="' + totalPages + '">' + totalPages + '</button>');
    }

    // Next button
    $pagination.append('<button class="mg_page-btn mg_next"' + (page === totalPages ? ' disabled' : '') + '>&gt;</button>');
  }

  // Click events
  $(document).on('click', '.mg_page-btn', function () {
    if ($(this).hasClass('mg_prev') && currentPage > 1) showPage(currentPage - 1);
    else if ($(this).hasClass('mg_next') && currentPage < totalPages) showPage(currentPage + 1);
    else if ($(this).data('page')) showPage(parseInt($(this).data('page')));
  });

  // Initial
  showPage(1);
});





  // Image Popup Modal
  jQuery(document).ready(function($){

    // Click thumbnail to show full image in modal
    $(document).on('click', '.mg_image-item img', function() {
        const fullSrc = $(this).attr('src');
        const promptText = $(this).siblings('.mg_image-prompt').text();

        $('#mockgenie-full-image').attr('src', fullSrc);
        $('#mockgenie-full-prompt').text(promptText);

        $('#mockgenie-full-modal').fadeIn();
    });

    // Close modal on X
    $(document).on('click', '.mockgini-close', function(){
        $(this).closest('.mockgenie-img-modal').fadeOut();
    });

    // Close modal on clicking outside
    $(window).on('click', function(e){
        if($(e.target).hasClass('mockgenie-img-modal')){
            $(e.target).fadeOut();
        }
    });

     // Close modal on pressing ESC key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' || e.keyCode === 27) {
            $('.mockgenie-img-modal').fadeOut();
        }
    });

});


  $('.mg_close-popup, .mg_image-popup').on('click', function (e) {
    if (e.target !== this) return; // Only close if clicking overlay or X
    $('.mg_image-popup').fadeOut();
  });
});

// =====================================================================
// Create / Reset / Login AJAX
// =====================================================================
jQuery(document).ready(function ($) {
  function showToast(type, message) {
    toastr.options = {
      closeButton: true,
      progressBar: true,
      positionClass: 'toast-top-right',
      timeOut: '3000',
    };

    if (type === 'success') toastr.success(message);
    else if (type === 'error') toastr.error(message);
  }

  // Create or Reset
  $('#mg_button_create_reset').on('click', function (e) {
    e.preventDefault();

    var email = $('input[name="email"]').val().trim();
    var tab = $(this).data('tab');

    if (email === '') {
      showToast('error', 'Email is required.');
      return;
    }
    

    $('#mg_loader').fadeIn(150);

    if (tab === 'create') {
      $.ajax({
        url: MOCKGENIE.ajax_url,
        method: 'POST',
        data: {
          action: 'mg_create_user',
          email: email,
          tab: tab,
          _wpnonce: MOCKGENIE.nonce,
        },
        success: function (response) {
          $('#mg_loader').fadeOut(150);

          if (response.success) {
            var status = response.data.data.status;

            if (status == 2) {
              showToast('error', response.data.data.message || 'User already exists.');
            } else {
              showToast('success', response.data.data.message || 'User created successfully.');

              var form = $('.mg_create-form');
              if (form.length) form[0].reset();
            }
          }
        },
        error: function () {
          $('#mg_loader').fadeOut(150);
          showToast('error', 'An unexpected error occurred.');
        },
      });
    } else if (tab === 'reset') {
      $.ajax({
        url: MOCKGENIE.ajax_url,
        method: 'POST',
        data: {
          action: 'mg_reset_user',
          email: email,
          tab: tab,
          _wpnonce: MOCKGENIE.nonce,
        },
        success: function (response) {
          $('#mg_loader').fadeOut(150);
          if (response.success) {
            showToast('success', response.data.data.message || 'Password reset successfully.');
          } else {
            showToast('error', response.data.message || 'Password reset successfully.');
          }
        },
        error: function () {
          $('#mg_loader').fadeOut(150);
          showToast('error', 'An unexpected error occurred.');
        },
      });
    }
  });

  // Login
  $('#mg_button_login').on('click', function (e) {
    e.preventDefault();

    var username = $('input[name="mg_username"]').val().trim();
    var password = $('input[name="mg_password"]').val().trim();

    if (username === '' || password === '') {
      toastr.error('Both username and password are required.');
      return;
    }

    $('#mg_loader').fadeIn(150);

    $.ajax({
      url: MOCKGENIE.ajax_url,
      method: 'POST',
      data: {
        action: 'mg_login_user',
        mg_username: username,
        mg_password: password,
        _wpnonce: MOCKGENIE.nonce,
      },
      success: function (response) {
        $('#mg_loader').fadeOut(150);
        if (response.success) {
          toastr.success(response.data.message || 'Login successful!');

          var form = $('.mg_login-form');
          if (form.length) form[0].reset();

          window.location.reload();
        } else {
          toastr.error(response.data.message || 'Login failed.');
        }
        localStorage.removeItem('activeTab');

      },
      error: function () {
        $('#mg_loader').fadeOut(150);
        toastr.error('An unexpected error occurred.');
      },
    });
  });
});

jQuery(document).ready(function ($) {

    // On Save Changes button click
    $('.mg_btn-save').on('click', function (e) {
        e.preventDefault();

        var imagesPerPage = parseInt($('#mg_images_per_page').val(), 10) || 8;

        // Disable button while saving
        var $btn = $(this);
        $btn.prop('disabled', true);

        console.log( imagesPerPage );

        $.ajax({
            url: MOCKGENIE.ajax_url,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'mg_update_images_per_page', // PHP action
                images_per_page: imagesPerPage,
                _wpnonce: MOCKGENIE.nonce
            },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.data.message);
                } else {
                    toastr.error(response.data.message || 'Could not save setting.');
                }

                window.location.reload();
            },
            error: function (xhr) {
                var msg = 'An error occurred.';
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    msg = xhr.responseJSON.data.message;
                }
                toastr.error(msg);
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
    });

});
