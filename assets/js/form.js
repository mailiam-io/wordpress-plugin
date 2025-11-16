/**
 * Mailiam Form Handler - Frontend JavaScript
 */

(function($) {
    'use strict';

    /**
     * Handle form submission
     */
    function handleFormSubmit(e) {
        e.preventDefault();

        const $form = $(this);
        const $button = $form.find('.mailiam-submit-button');
        const $message = $form.find('.mailiam-message');
        const formId = $form.data('form-id');
        const redirectUrl = $form.data('redirect');

        // Disable button and show loading state
        $button.prop('disabled', true);
        const originalButtonText = $button.text();
        $button.text('Sending...');

        // Hide previous messages
        $message.hide().removeClass('mailiam-success mailiam-error');

        // Serialize form data
        const formData = $form.serializeArray();

        // Submit via AJAX
        $.ajax({
            url: mailiamSettings.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mailiam_submit',
                nonce: mailiamSettings.nonce,
                form_id: formId,
                form_data: formData
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $message
                        .addClass('mailiam-success')
                        .html('<p>' + escapeHtml(response.data.message) + '</p>')
                        .fadeIn();

                    // Reset form
                    $form[0].reset();

                    // Redirect if URL specified
                    if (redirectUrl) {
                        setTimeout(function() {
                            window.location.href = redirectUrl;
                        }, 1500);
                    }
                } else {
                    // Show error message
                    showError(response.data.message || mailiamSettings.errorMessage);
                }
            },
            error: function(xhr) {
                let errorMessage = mailiamSettings.errorMessage;

                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMessage = xhr.responseJSON.data.message;
                }

                showError(errorMessage);
            },
            complete: function() {
                // Re-enable button
                $button.prop('disabled', false).text(originalButtonText);
            }
        });

        function showError(message) {
            $message
                .addClass('mailiam-error')
                .html('<p>' + escapeHtml(message) + '</p>')
                .fadeIn();
        }
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Initialize forms
     */
    function initForms() {
        $('.mailiam-form').on('submit', handleFormSubmit);
    }

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        initForms();

        // Re-initialize after AJAX content loads (for page builders, etc.)
        $(document).on('ajaxComplete', function() {
            initForms();
        });
    });

})(jQuery);
