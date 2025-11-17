/**
 * Gravity Page Link View - Admin JavaScript
 * @author Mahmud Farooque
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // Handle form item click in sidebar
        $('.gplv-form-item').on('click', function() {
            var formId = $(this).data('form-id');

            // Remove active class from all items
            $('.gplv-form-item').removeClass('active');

            // Add active class to clicked item
            $(this).addClass('active');

            // Hide all usage sections
            $('.gplv-usage-section').addClass('hidden');

            // Show the selected form's usage section
            $('.gplv-usage-section[data-form-id="' + formId + '"]').removeClass('hidden');

            // Scroll to the usage section smoothly
            var usageSection = $('.gplv-usage-section[data-form-id="' + formId + '"]');
            if (usageSection.length) {
                $('.gplv-content').animate({
                    scrollTop: usageSection.position().top - 20
                }, 400);
            }
        });

        // Initialize: Hide all sections except the first one
        if ($('.gplv-form-item').length > 0) {
            $('.gplv-usage-section').addClass('hidden');

            // Activate first form by default
            $('.gplv-form-item').first().addClass('active');
            var firstFormId = $('.gplv-form-item').first().data('form-id');
            $('.gplv-usage-section[data-form-id="' + firstFormId + '"]').removeClass('hidden');
        }

        // Add smooth scrolling for better UX
        $('.gplv-content').css('scroll-behavior', 'smooth');

        // Optional: Add keyboard navigation
        $(document).on('keydown', function(e) {
            if (!$('.gplv-wrap').length) return;

            var $activeItem = $('.gplv-form-item.active');
            var $nextItem, $prevItem;

            // Arrow Down - Next form
            if (e.keyCode === 40) {
                e.preventDefault();
                $nextItem = $activeItem.next('.gplv-form-item');
                if ($nextItem.length) {
                    $nextItem.click();
                }
            }

            // Arrow Up - Previous form
            if (e.keyCode === 38) {
                e.preventDefault();
                $prevItem = $activeItem.prev('.gplv-form-item');
                if ($prevItem.length) {
                    $prevItem.click();
                }
            }
        });

        // Add hover effect information
        $('.gplv-form-item').hover(
            function() {
                $(this).css('transform', 'translateX(5px)');
            },
            function() {
                $(this).css('transform', 'translateX(0)');
            }
        );

    });

})(jQuery);
