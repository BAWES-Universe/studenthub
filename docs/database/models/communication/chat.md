# Chat Models

## Chat Model

### Overview
The Chat model represents communication channels between users in the system, supporting both one-on-one and group conversations.

### Table Name
`chat`

### Primary Key
- `chat_id` (integer)

### Key Attributes

#### Basic Information
- `chat_type` (string) - Type of chat
  - ONE_ON_ONE
  - GROUP
  - SUPPORT
  - ANNOUNCEMENT
- `chat_title` (string) - Chat title (for groups)
- `chat_description` (text) - Chat description
- `participant_count` (integer) - Number of participants

#### Configuration
- `is_private` (integer) - Privacy flag
- `is_archived` (integer) - Archive status
- `allow_attachments` (integer) - Attachment permission
- `max_participants` (integer) - Maximum participants
- `retention_days` (integer) - Message retention period

#### Status Information
- `chat_status` (integer) - Chat status
  - STATUS_ACTIVE (10)
  - STATUS_CLOSED (0)
  - STATUS_ARCHIVED (20)
- `last_message_at` (datetime) - Last message timestamp
- `created_at` (datetime) - Creation timestamp
- `updated_at` (datetime) - Last update timestamp

### Foreign Keys
- `created_by` -> `staff.staff_id`
- `updated_by` -> `staff.staff_id`

## Chat Message Model

### Overview
Represents individual messages within a chat conversation.

### Table Name
`chat_message`

### Key Attributes

#### Message Content
- `message_text` (text) - Message content
- `message_type` (string) - Type of message
  - TEXT
  - IMAGE
  - FILE
  - SYSTEM
  - ACTION
- `parent_message_id` (integer) - Reply reference
- `attachment_url` (string) - Attachment path
- `attachment_type` (string) - Attachment type
- `metadata` (json) - Additional message data

#### Status Information
- `is_edited` (integer) - Edit status
- `is_deleted` (integer) - Deletion status
- `delivered_at` (datetime) - Delivery timestamp
- `read_at` (datetime) - Read timestamp
- `created_at` (datetime) - Creation timestamp
- `updated_at` (datetime) - Last update timestamp

### Foreign Keys
- `chat_id` -> `chat.chat_id`
- `sender_id` -> `user.user_id`

## Chat Participant Model

### Overview
Manages chat participants and their permissions.

### Table Name
`chat_participant`

### Key Attributes
- `chat_id` (integer) - Chat reference
- `user_id` (integer) - User reference
- `role` (string) - Participant role
  - OWNER
  - ADMIN
  - MEMBER
  - GUEST
- `permissions` (json) - Specific permissions
- `joined_at` (datetime) - Join timestamp
- `last_read_at` (datetime) - Last read timestamp
- `muted_until` (datetime) - Mute duration

## Common Operations

```php
// Create new chat
$chat = new Chat([
    'chat_type' => Chat::TYPE_GROUP,
    'chat_title' => $title
]);
$chat->addParticipants($userIds);

// Get user's chats
Chat::find()
    ->joinWith('participants')
    ->where(['chat_participant.user_id' => $userId])
    ->andWhere(['chat_status' => Chat::STATUS_ACTIVE])
    ->all();

// Get chat messages
ChatMessage::find()
    ->where(['chat_id' => $chatId])
    ->orderBy(['created_at' => SORT_DESC])
    ->limit(50)
    ->all();

// Send message
$message = new ChatMessage([
    'chat_id' => $chatId,
    'message_text' => $text
]);
$message->send();
```

## Implementation Details
- Real-time message delivery
- Message read receipts
- File attachment handling
- Message threading support
- Participant management
- Message retention policies

## Business Rules
1. Message size limits
2. Attachment type restrictions
3. Participant role permissions
4. Message editing timeframe
5. Chat archival policies
6. Notification preferences

## Security Considerations
1. Message encryption
2. Participant verification
3. File scanning
4. Rate limiting
5. Access control
6. Data retention compliance 