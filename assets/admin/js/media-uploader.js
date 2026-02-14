jQuery(document).ready(function ($) {
    var $ams_photo_icon = $('#ams_photo_icon');
    var $ams_photo_image_preview = $('#ams_photo_image_preview')

    $(document).on('click', '.ams_photo_media_upload', function (e) {
        e.preventDefault();

        var wp_media_frame = wp.media({
            title: 'Select photo icon',
            multiple: false
        });

        // When a file is selected, run a callback
        wp_media_frame.on('select', function () {
            var attachment = wp_media_frame.state().get('selection').first().toJSON();
            // Set the input value to the attachment ID
            $ams_photo_icon.val(attachment.id);
            // Update the image preview
            $ams_photo_image_preview.attr('src', attachment.url).show();
            // Show the remove button
            $('.ams_photo_media_remove').show();
        });

        // Open the media frame
        wp_media_frame.open();
    });

    // Remove button behavior
    $(document).on('click', '.ams_photo_media_remove', function (e) {
        e.preventDefault();
        $ams_photo_icon.val('');
        $ams_photo_image_preview.hide();
        $(this).hide();
    });
});
