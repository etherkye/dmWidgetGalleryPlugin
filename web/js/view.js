(function($) {

  $('#dm_page div.dm_widget.content_gallery').live('dmWidgetLaunch', function()
  {
    var $gallery = $(this).find('ol.dm_widget_content_gallery');

    // only if elements in gallery
    if(!$gallery.find('>li').length)
    {
      return;
    }

    // get options from gallery metadata
    var options = $gallery.metadata();

    if (options.animation == 'slideshow')
    {
      // launch jQuery cycle
      $gallery.cycle({
        timeout:     options.delay * 1000,                      // convert to ms
        height:      $gallery.find('img:first').attr('height')  // use first image height
      });
    }
    else if (options.animation == 'simplyScroll')
    {
      // launch jQuery smoothDivScroll
      $gallery.simplyScroll({
          pauseOnHover: false,
          autoMode: 'loop',
          speed: options.delay
      });

      $simplyscroll = $(this).find('.simply-scroll');
      $simplyscroll.width(options.width);
      $simplyscroll.height(options.height);

      $simplyscrollclip = $(this).find('.simply-scroll .simply-scroll-clip');
      $simplyscrollclip.width(options.width);
      $simplyscrollclip.height(options.height);

    }
    else if(options.animation == 'custom')
    {
      if ($.isFunction($.dm.customGallery))
      {
        $.dm.customGallery($gallery, options);
      }
      else
      {
        alert('You must create a $.dm.customGallery(element, options) function in your front.js to use custom animation');
      }
    }
    else
    {
      alert('Unknown animation '+options.animation);
    }
  });

})(jQuery);