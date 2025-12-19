<?php
session_start();
require_once('identifiers.php');
require_once('rabbitMQ/RabbitClient.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || empty($input['type'])) {
            throw new Exception('Invalid request');
        }

        $client = RabbitClient::getConnection();
        $response = match ($input['type']) {
            'GET_USER' => $client->send_request([
                    'type' => RequestType::GET_USER,
                    'userid' => (int)$input['userid']
            ]),
            'GET_MESSAGES_BETWEEN_USERS' => $client->send_request([
                    'type' => RequestType::GET_MESSAGES_BETWEEN_USERS,
                    'senderuserid' => (int)$input['senderuserid'],
                    'receiveruserid' => (int)$input['receiveruserid'],
                    'limit' => $input['limit'] ?? 1000,
                    'offset' => $input['offset'] ?? 0
            ]),
            'SEND_MESSAGE' => $client->send_request([
                    'type' => RequestType::SEND_MESSAGE,
                    'senderuserid' => (int)$input['senderuserid'],
                    'receiveruserid' => (int)$input['receiveruserid'],
                    'message' => $input['message']
            ]),
            default => throw new Exception('Invalid request type'),
        };

        echo json_encode($response);
    } catch (Throwable $e) {
        echo json_encode([]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="utf-8">
    <title>Messages</title>
    <link rel="stylesheet" href="css/pico.min.css">
    <link rel="stylesheet" href="css/custom.css">
    <link rel="stylesheet" href="css/fontawesome/css/all.min.css">
</head>

<body>
<main class="main">
    <article class="bordered-article">
        <nav class="main-navigation">
            <ul>
                <li>
                    <a href="index.php"> <i class="fa-solid fa-house">
                        </i> Index
                    </a>
                </li>

                <?php if (isset($_SESSION[Identifiers::SESSION_ID]) && isset($_SESSION[Identifiers::USER_ID])): ?>
                    <li>
                        <a href="profile.php"> <i class="fa-solid fa-user"></i> Profile</a>
                    </li>
                <?php endif; ?>

                <?php if (isset($_SESSION[Identifiers::STEAM_ID])): ?>
                    <li>
                        <a href="browse.php">
                            <i class="fa-solid fa-gamepad"></i> Browse
                        </a>
                    </li>

                    <li>
                        <a href="createCatalog.php">
                            <i class="fa-solid fa-plus"></i> Create Catalog
                        </a>
                    </li>
                    <li>
                        <a href="recommendations.php">
                            <i class="fa-solid fa-lightbulb"></i> Recommendations
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (isset($_SESSION[Identifiers::SESSION_ID]) && isset($_SESSION[Identifiers::USER_ID])): ?>
                    <li>
                        <a href="viewUserCatalogs.php?userid=<?= $_SESSION[Identifiers::USER_ID] ?>"> <i
                                    class="fa-solid fa-list"></i> My Catalogs
                        </a>
                    </li>

                    <li>
                        <a href="logout.php"> <i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </article>

    <article class="bordered-article" style="display:flex; flex-direction:column; height:80vh;">
        <form id="messageForm" style="display:flex; flex-direction:column; height:100%">
            <div id="messageHeader" style="text-align:center; margin-bottom:1rem">
                <strong id="messagesTitle" style="font-size:1.1rem">Messages</strong>
            </div>

            <div id="messageDisplay"
                 style="flex:1; overflow-y:auto; margin-bottom:1rem;
                     padding:1rem; border:1px solid var(--pico-form-element-border-color);
                     border-radius:4px">
            </div>

            <label for="messageBox"></label>
            <input id="messageBox" type="text" placeholder="Enter message..." required>
            <button id="submitButton" disabled>
                <i class="fa-solid fa-paper-plane"></i> Send
            </button>
        </form>
    </article>
</main>

<script>
    const currentUserId = <?= (int)($_SESSION[Identifiers::USER_ID] ?? 0) ?>;
    const otherUserId = new URLSearchParams(window.location.search).get('userid');
    const messageDisplay = document.getElementById('messageDisplay');
    const title = document.getElementById('messagesTitle');
    const form = document.getElementById('messageForm');
    const box = document.getElementById('messageBox');
    const button = document.getElementById('submitButton');

    document.addEventListener('DOMContentLoaded', async () => {
        if (!otherUserId) {
            title.textContent = 'No user selected';
            return;
        }

        await loadUser();
        await loadMessages();
        setInterval(loadMessages, 3000);
    });

    async function loadUser() {
        const res = await fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({type: 'GET_USER', userid: otherUserId})
        });
        const user = await res.json();
        if (user?.Username) {
            title.textContent = 'Messages: ' + user.Username;
        }
    }

    async function loadMessages() {
        const res = await fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                type: 'GET_MESSAGES_BETWEEN_USERS',
                senderuserid: currentUserId,
                receiveruserid: otherUserId
            })
        });
        const messages = await res.json();
        appendMessages(Array.isArray(messages) ? messages : []);
    }

    function appendMessages(messages) {
        const atBottom = messageDisplay.scrollHeight - messageDisplay.scrollTop <= messageDisplay.clientHeight + 50;
        messageDisplay.innerHTML = '';

        if (!messages.length) {
            messageDisplay.innerHTML = '<p style="text-align:center; opacity:.5">No messages</p>';
            return;
        }

        for (const msg of messages) {
            const mine = Number(msg.SenderID) === currentUserId;
            const div = document.createElement('div');
            div.style.textAlign = mine ? 'right' : 'left';
            div.style.marginBottom = '0.75rem';

            div.innerHTML = `
            <small style="opacity:.7">${msg.SenderUsername}</small><br>
            <span style="
                display:inline-block;
                padding:.5rem 1rem;
                border-radius:8px;
                background:${mine
                ? 'var(--pico-primary-background)'
                : 'var(--pico-form-element-border-color)'};
            ">${msg.Text}</span>
        `;
            messageDisplay.appendChild(div);
        }

        if (atBottom) {
            messageDisplay.scrollTop = messageDisplay.scrollHeight;
        }
    }

    form.addEventListener('submit', async e => {
        e.preventDefault();
        if (!box.value.trim()) return;

        await fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                type: 'SEND_MESSAGE',
                senderuserid: currentUserId,
                receiveruserid: otherUserId,
                message: box.value
            })
        });

        box.value = '';
        button.disabled = true;
        await loadMessages();
    });

    box.addEventListener('input', () => {
        button.disabled = !box.value.trim();
    });
</script>
</body>
</html>