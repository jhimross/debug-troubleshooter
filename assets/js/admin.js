jQuery(document).ready(function($) {
    var isTroubleshooting = debugTroubleshoot.is_troubleshooting;
    var troubleshootState = debugTroubleshoot.current_state;

    // Show custom alert modal
    function showAlert(title, message, type = 'success') {
        var modal = $('#debug-troubleshoot-alert-modal');
        $('#debug-troubleshoot-alert-title').text(title);
        $('#debug-troubleshoot-alert-message').text(message);

        if (type === 'error') {
            $('#debug-troubleshoot-alert-title').css('color', '#dc3232');
        } else {
            $('#debug-troubleshoot-alert-title').css('color', '');
        }

        modal.removeClass('hidden');
    }

    $('#debug-troubleshoot-alert-close').on('click', function() {
        $('#debug-troubleshoot-alert-modal').addClass('hidden');
    });

    // Handle toggle button for troubleshooting mode
    $('#troubleshoot-mode-toggle').on('click', function() {
        var $button = $(this);
        var enableMode = !isTroubleshooting; // Determine if we are enabling or disabling

        $button.prop('disabled', true).text(enableMode ? 'Activating...' : 'Deactivating...');

        $.ajax({
            url: debugTroubleshoot.ajax_url,
            type: 'POST',
            data: {
                action: 'debug_troubleshoot_toggle_mode',
                nonce: debugTroubleshoot.nonce,
                enable: enableMode ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    showAlert(debugTroubleshoot.alert_title_success, response.data.message);
                    isTroubleshooting = enableMode; // Update state
                    $button.text(isTroubleshooting ? debugTroubleshoot.alert_title_success + ' Mode' : 'Enter Troubleshooting Mode');
                    if (isTroubleshooting) {
                        $button.removeClass('button-primary').addClass('button-danger');
                        $('#troubleshoot-mode-controls').removeClass('hidden');
                    } else {
                        $button.removeClass('button-danger').addClass('button-primary');
                        $('#troubleshoot-mode-controls').addClass('hidden');
                    }
                    // Refresh the page to apply cookie changes immediately
                    setTimeout(function() { location.reload(); }, 500);
                } else {
                    showAlert(debugTroubleshoot.alert_title_error, response.data.message, 'error');
                }
            },
            error: function() {
                showAlert(debugTroubleshoot.alert_title_error, 'An AJAX error occurred.', 'error');
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    });

    // Populate troubleshooting controls initially if mode is active
    if (isTroubleshooting) {
        $('#troubleshoot-mode-controls').removeClass('hidden');

        // Set selected theme
        if (troubleshootState && troubleshootState.theme) {
            $('#troubleshoot-theme-select').val(troubleshootState.theme);
        }

        // Check plugins based on troubleshooting state
        $('.plugin-list input[type="checkbox"]').each(function() {
            var $checkbox = $(this);
            var pluginFile = $checkbox.val();

            var originalActive = debugTroubleshoot.active_plugins.includes(pluginFile) || debugTroubleshoot.active_sitewide_plugins.includes(pluginFile);
            var troubleshootActive = false;

            if (troubleshootState && troubleshootState.plugins && troubleshootState.plugins.includes(pluginFile)) {
                troubleshootActive = true;
            }
            if (troubleshootState && troubleshootState.sitewide_plugins && troubleshootState.sitewide_plugins.includes(pluginFile)) {
                troubleshootActive = true;
            }

            // If troubleshooting is active, set checkbox based on troubleshootState
            if (isTroubleshooting) {
                $checkbox.prop('checked', troubleshootActive);
            } else {
                // If not in troubleshooting, reflect actual active plugins
                $checkbox.prop('checked', originalActive);
            }
        });
    }

    // Handle applying troubleshooting changes
    $('#apply-troubleshoot-changes').on('click', function() {
        if (!isTroubleshooting) {
            showAlert(debugTroubleshoot.alert_title_error, 'Please enter troubleshooting mode first.', 'error');
            return;
        }

        var $button = $(this);
        $button.prop('disabled', true).text('Applying...');

        var selectedTheme = $('#troubleshoot-theme-select').val();
        var selectedPlugins = [];
        $('.plugin-list input[type="checkbox"]:checked').each(function() {
            selectedPlugins.push($(this).val());
        });

        $.ajax({
            url: debugTroubleshoot.ajax_url,
            type: 'POST',
            data: {
                action: 'debug_troubleshoot_update_state',
                nonce: debugTroubleshoot.nonce,
                theme: selectedTheme,
                plugins: selectedPlugins
            },
            success: function(response) {
                if (response.success) {
                    showAlert(debugTroubleshoot.alert_title_success, response.data.message);
                    // Refresh the page to apply cookie changes immediately
                    setTimeout(function() { location.reload(); }, 500);
                } else {
                    showAlert(debugTroubleshoot.alert_title_error, response.data.message, 'error');
                }
            },
            error: function() {
                showAlert(debugTroubleshoot.alert_title_error, 'An AJAX error occurred.', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).text('Apply Troubleshooting Changes');
            }
        });
    });
});