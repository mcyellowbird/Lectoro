<?php
require dirname(dirname(__DIR__)) . '/vendor/autoload.php'; // Adjusted path

// Connect to MongoDB
$mongoClient = new MongoDB\Client("mongodb://localhost:27017");
$database = $mongoClient->selectDatabase("CSIT321Development");
$usersCollection = $database->selectCollection("users");

// Assuming user is logged in and we have their ID
$loggedInUserId = $_SESSION['_id'];
?>

<!DOCTYPE html>
<html lang="en">

<body>
    <div id="chatInterface" class="flex flex-row w-full h-full">
        <div id="conversationList" class="w-1/4 border-r border-r-textAccent p-2.5 overflow-y-auto">
            <div class="searchBar">
                <i class="searchIcon bx bx-search"></i>
                <input type="text" id="searchBar" placeholder="Search for users..." class="searchInput">
            </div>
            <div id="searchResults" class="border-textAccent rounded-lg max-h-80 overflow-y-auto"></div>
            <div id="conversations" class="flex flex-col gap-2">
                <span class="mt-8 text-center block pt-4">Current Chats</span>

            </div>
        </div>
        <div id="chatWindow" class="shadow-chatWindow flex flex-1 p-8 flex-col">
            <div id="selectedUserName"></div>
            <div id="messages" class="flex flex-1 flex-col mb-2.5 overflow-y-auto"></div>
            <div id="messageBox" class="flex flex-row mt-2.5 border-b-text">
                <input type="text" id="messageInput" placeholder="Type a message..." class="flex-1 p-2">
                <button id="sendMessageButton" class="p-2">Send</button>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            const loggedInUserId = '<?php echo $loggedInUserId; ?>';

            function searchUsers() {
                const query = $('#searchBar').val();

                $.get('./src/events/chat/searchUsers.php', { query: query })
                    .done(function (data) {
                        const searchResultsDiv = $('#searchResults');
                        searchResultsDiv.empty();

                        if (query != "") {
                            data.forEach(user => {
                                const userDiv = $('<div></div>')
                                    .addClass('cursor-pointer p-1.5 hover:bg-buttonHover')
                                    .text(`${user.first_name} ${user.last_name}`)
                                    .data('userId', user.id)
                                    .click(() => openChat(user.id));
                                searchResultsDiv.append(userDiv);
                            });
                        }
                    })
                    .fail(function (error) {
                        console.error('Error:', error);
                    });
            }

            function openChat(userId) {
                $.get('./src/events/chat/getConversation.php', { id: userId })
                    .done(function (data) {
                        data = JSON.parse(data);
                        if (data.error) {
                            console.error('Error fetching conversation:', data.error);
                            return;
                        }
                        const conversationDiv = $('#conversations');

                        if (data.conversation_id) {
                            loadMessages(data.conversation_id);

                            $('.activeConversation').removeClass('activeConversation');

                            const userDiv = conversationDiv.children().filter(function () {
                                return $(this).data('userId') === userId;
                            });
                            
                            if (userDiv.length) {
                                userDiv.addClass('activeConversation');
                            } else {
                                console.error('User not found in conversation list:', userId);
                            }
                        } else {
                            createConversation(userId).then(conversationId => {
                                loadMessages(conversationId);
                            }).catch(error => console.error('Error creating conversation:', error));
                        }
                    })
                    .fail(function (error) {
                        console.error('Error:', error);
                    });
            }

            function createConversation(userId) {

                return $.post('./src/events/chat/createConversation.php', { userId: userId })
                    .done(function (data) {
                        data = JSON.parse(data);
                        if (data.success) {
                            return data.conversation_id;
                        } else {
                            throw new Error(data.error || 'Failed to create conversation');
                        }
                    })
                    .fail(function (error) {
                        console.error('Error creating conversation:', error);
                        throw error;
                    });
            }

            function loadMessages(conversationId) {
                $.get('./src/events/chat/getMessages.php', { conversation_id: conversationId })
                    .done(function (data) {
                        data = JSON.parse(data);
                        const messagesDiv = $('#messages');
                        messagesDiv.empty();

                        let lastDate = null; // Variable to keep track of the last message date

                        data.forEach(message => {
                            // Convert UTC timestamp to local time
                            const utcDate = new Date(message.timestamp);
                            const options = { hour: '2-digit', minute: '2-digit', hour12: true };
                            const timeString = utcDate.toLocaleTimeString('en-US', options);
                            const dateString = utcDate.toLocaleDateString('en-US', { weekday: 'short', hour: '2-digit', minute: '2-digit' });

                            // Check if the message date is different from the last one
                            const messageDate = utcDate.toLocaleDateString();
                            if (lastDate !== messageDate) {
                                // Add day separator
                                $('<div></div>')
                                    .addClass('text-center block my-2.5 text-buttonHover')
                                    .text(dateString)
                                    .appendTo(messagesDiv);
                                lastDate = messageDate;
                            }

                            // Create and append message elements
                            const parentContainer = $('<div></div>')
                                .addClass('flex flex-row');

                            const messageContainer = $('<div></div>')
                                .addClass('flex flex-col !max-w-60p')
                                .addClass(message.sender_id === loggedInUserId ? 'self-end' : 'self-start');

                            const messageInner = $('<div></div>')
                                .addClass('flex flex-row mt-0.5 mb-0.5');

                            const messageDiv = $('<div></div>')
                                .addClass(message.sender_id === loggedInUserId ? 'message-from-user' : 'message-to-user');

                            messageDiv.append($('<span></span>').text(message.message));

                            const timeDiv = $('<div></div>')
                                .addClass('text-buttonHover text-xs min-w-fit pt-3')
                                .text(timeString);

                            const emptyContainer = $('<div></div>')
                                .addClass('shrink-0 flex-grow'); // This will take the remaining width

                            // Swap emptyContainer and messageContainer if the message is from the other user
                            if (message.sender_id === loggedInUserId) {
                                timeDiv.addClass('mr-2')
                                messageInner.append(timeDiv);
                                messageInner.append(messageDiv);
                                messageContainer.append(messageInner);
                                
                                parentContainer.append(emptyContainer);
                                parentContainer.append(messageContainer);
                                parentContainer.append($('<i class="bx bxs-user pl-1.5 text-xl"></i>'));
                            } else {
                                timeDiv.addClass('ml-2')
                                messageInner.append(messageDiv);
                                messageInner.append(timeDiv);
                                messageContainer.append(messageInner);

                                parentContainer.append($('<i class="bx bxs-user pr-1.5 text-xl"></i>'));
                                parentContainer.append(messageContainer);
                                parentContainer.append(emptyContainer);
                            }

                            messagesDiv.append(parentContainer);
                        });
                        messagesDiv.scrollTop(messagesDiv[0].scrollHeight);
                    })
                    .fail(function (error) {
                        console.error('Error:', error);
                    });
            }



            // setInterval(() => {
            //     const activeConversation = $('.activeConversation').data('conversationId');
            //     if (activeConversation) {
            //         loadMessages(activeConversation);
            //     }
            // }, 5000);

            $('#sendMessageButton').click(function () {
                const message = $('#messageInput').val();
                const activeConversation = $('.activeConversation').data('conversationId');

                if (activeConversation) {

                    $.post('./src/events/chat/sendMessage.php', {
                        conversation_id: activeConversation,
                        message: message
                    })
                    .done(function (data) {
                        try {
                            if (data.success) {
                                loadMessages(activeConversation);
                                $('#messageInput').val('');
                            } else {
                                console.error('Failed to send message:', data.error);
                            }
                        } catch (e) {
                            console.error('Failed to parse JSON response:', e);
                            console.error('Response:', data);
                        }
                    })
                    .fail(function (error) {
                        console.error('Error:', error);
                    });
                } else {
                    console.error('No conversation selected');
                }
            });

            $.get('./src/events/chat/getConversationList.php')
                .done(function (data) {
                    data = JSON.parse(data);
                    const conversationsDiv = $('#conversations');
                    if (conversationsDiv) {
                        const searchBar = $('#searchBar');
                        data.forEach(conversation => {
                            const userDiv = $('<div></div>')
                                .addClass('flex items-center rounded-md cursor-pointer p-1.5 hover:bg-buttonHover/60')
                                .text(conversation.display_name)
                                .data('userId', conversation.user_id)
                                .data('conversationId', conversation.conversation_id)
                                .click(() => openChat(conversation.user_id));
                                conversationsDiv.append(userDiv);
                            userDiv.prepend($('<i class="bx bxs-user pr-1.5 text-xl"></i>'))
                        });

                        $('#searchBar').on('input', searchUsers);
                    }
                })
                .fail(function (error) {
                    console.error('Error:', error);
                });
        });
    </script>
</body>

</html>
