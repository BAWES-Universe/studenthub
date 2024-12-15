# Story & Activity Models

## Story Model

### Overview
The Story model represents work items, tasks, and activities that need to be tracked.

### Table Name
`story`

### Primary Key
- `story_id` (integer)

### Key Attributes

#### Basic Information
- `story_title` (string) - Story title
- `story_description` (text) - Story details
- `story_type` (string) - Type of story
  - TASK
  - BUG
  - FEATURE
  - IMPROVEMENT
- `story_priority` (integer) - Priority level
- `story_points` (integer) - Story points

#### Assignment
- `assigned_to` (integer) - Assignee reference
- `created_by` (integer) - Creator reference
- `team_id` (integer) - Team reference
- `project_id` (integer) - Project reference

#### Status & Timeline
- `story_status` (integer) - Current status
  - STATUS_NEW (0)
  - STATUS_STARTED (10)
  - STATUS_COMPLETED (20)
  - STATUS_BLOCKED (30)
- `start_date` (datetime) - Start date
- `due_date` (datetime) - Due date
- `completed_at` (datetime) - Completion time

## Story Activity Model

### Overview
Tracks activities and updates related to stories.

### Table Name
`story_activity`

### Key Attributes

#### Activity Details
- `story_id` (integer) - Story reference
- `activity_type` (string) - Activity type
  - UPDATE
  - COMMENT
  - STATUS_CHANGE
  - ASSIGNMENT
- `activity_description` (text) - Activity details
- `activity_data` (json) - Additional data

#### Time Tracking
- `activity_date` (datetime) - Activity date
- `time_spent` (integer) - Time in minutes
- `time_remaining` (integer) - Remaining time
- `activity_status` (integer) - Activity status

#### Changes
- `old_value` (text) - Previous value
- `new_value` (text) - Updated value
- `change_reason` (text) - Change reason
- `change_impact` (text) - Change impact

## Story Comment Model

### Overview
Manages comments and discussions on stories.

### Table Name
`story_comment`

### Key Attributes

#### Comment Content
- `story_id` (integer) - Story reference
- `comment_text` (text) - Comment content
- `comment_type` (string) - Comment type
  - GENERAL
  - REVIEW
  - QUESTION
  - BLOCKER
- `parent_comment_id` (integer) - Parent reference

#### Metadata
- `created_by` (integer) - Author reference
- `created_at` (datetime) - Creation time
- `edited_at` (datetime) - Last edit time
- `edit_reason` (text) - Edit reason

## Story Attachment Model

### Overview
Manages files and attachments related to stories.

### Table Name
`story_attachment`

### Key Attributes

#### File Details
- `story_id` (integer) - Story reference
- `file_name` (string) - Original filename
- `file_path` (string) - Storage path
- `file_type` (string) - MIME type
- `file_size` (integer) - File size

#### Metadata
- `uploaded_by` (integer) - Uploader reference
- `upload_date` (datetime) - Upload time
- `description` (text) - File description
- `is_private` (integer) - Privacy flag

## Common Operations

```php
// Create new story
$story = new Story([
    'story_title' => $title,
    'story_type' => 'TASK',
    'assigned_to' => $userId
]);
$story->save();

// Log activity
$activity = new StoryActivity([
    'story_id' => $storyId,
    'activity_type' => 'STATUS_CHANGE',
    'old_value' => $oldStatus,
    'new_value' => $newStatus
]);
$activity->save();

// Get story timeline
StoryActivity::find()
    ->where(['story_id' => $storyId])
    ->orderBy(['activity_date' => SORT_DESC])
    ->all();

// Get user's active stories
Story::find()
    ->where([
        'assigned_to' => $userId,
        'story_status' => Story::STATUS_STARTED
    ])
    ->orderBy(['due_date' => SORT_ASC])
    ->all();
```

## Implementation Details
- Activity tracking
- Time management
- File handling
- Comment threading
- Status workflow
- Assignment tracking

## Business Rules
1. Status transitions
2. Time tracking rules
3. Assignment policies
4. Comment moderation
5. File restrictions
6. Priority handling

## Reporting Features
1. Activity timelines
2. Time tracking reports
3. Progress tracking
4. Workload analysis
5. Status distribution
6. Completion rates

## Security Considerations
1. File access control
2. Comment moderation
3. Activity logging
4. Permission checks
5. Data privacy