jQuery(document).ready(function($) {
    // Check if we're on a product page
    if (!$('body').hasClass('single-product')) {
        return;
    }

    const lastReplyContainer = $('#cigar-ai-last-reply-container');
    const inputContainer = $('#cigar-ai-input');
    let userInput = $('#cigar-ai-user-input');
    let initialResponse = null;
    let initialCallMade = false;

    function addMessage(message, isAI = false) {
        if (isAI) {
            lastReplyContainer.html('<div class="ai-message">' + message + '</div>');
            lastReplyContainer.show();
        }
    }

    function showProgressBar() {
        inputContainer.html('<div class="progress-bar"><div class="progress"></div></div>');
    }

    function hideProgressBar() {
        inputContainer.html('<input type="text" id="cigar-ai-user-input" placeholder="Ask about cigars...">');
        userInput = $('#cigar-ai-user-input'); // Reassign the userInput variable
        attachInputListeners(); // Reattach event listeners
    }

    function makeInitialCall() {
        console.log('makeInitialCall called');
        showProgressBar();
        $.ajax({
            url: cigar_product_insight_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cigar_product_insight_initial_call',
                nonce: cigar_product_insight_ajax.nonce,
                subscription_external_id: cigar_product_insight_ajax.api_key,
                timeZone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                caller: {},
                caller_domain: window.location.hostname,
                product_id: cigar_product_insight_ajax.product_id  // Add this line
            },
            success: function(response) {
                hideProgressBar();
                if (response.success) {
                    initialResponse = response.data;
                    console.log('Initial call successful:', initialResponse);
                    initialCallMade = true;
                } else {
                    console.error('Initial call failed:', response.data);
                    addMessage('Error initializing chat. Please try again later.', true);
                }
            },
            error: function(xhr, status, error) {
                hideProgressBar();
                console.error('Error making initial call:', error);
                addMessage('Error initializing chat. Please try again later.', true);
            }
        });
    }

    function sendMessage() {
        const message = userInput.val().trim();
        if (message === '') return;

        userInput.val('');
        showProgressBar();

        if (initialResponse === null) {
            addMessage('Please wait, initializing chat...', true);
            let checkInitialResponse = setInterval(function() {
                if (initialResponse !== null) {
                    clearInterval(checkInitialResponse);
                    proceedWithMessage(message);
                }
            }, 100);
        } else {
            proceedWithMessage(message);
        }
    }

    function proceedWithMessage(message) {
        $.ajax({
            url: cigar_product_insight_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'send_product_insight_message',
                nonce: cigar_product_insight_ajax.nonce,
                message: message,
                data: initialResponse
            },
            success: function(response) {
                hideProgressBar();
                if (response.success) {
                    addMessage(response.data.message, true);
                    initialResponse = response.data;
                } else {
                    addMessage('Error: ' + response.data, true);
                }
            },
            error: function() {
                hideProgressBar();
                addMessage('Error communicating with the server', true);
            }
        });
    }

    function attachInputListeners() {
        userInput.on('keypress', function(e) {
            if (e.which === 13) {
                sendMessage();
            }
        });

        userInput.one('focus', function() {
            if (!initialCallMade) {
                console.log('Input field focused. Making initial AI call.');
                makeInitialCall();
            }
        });
    }

    attachInputListeners();
});